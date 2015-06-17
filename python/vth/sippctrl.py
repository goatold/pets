#!/usr/local/bin/python

"""
This module provides SIPP controler classes:
CSipp3pccClnt
CSippUa
CSipCtrl
"""

from string import Template
import sys, time, os, re, socket
from random import randint
from threading import Lock
from subp import CSubP
from vthutil import *
from vthlogger import VTHDebugLogger
from vththr import CRcvThr

class CSipp3pccClnt:
    """
    This class works as sipp 3pcc socket client class to
    interact with sipp instance via 3pcc TCP socket
    """

    cmdEndChr = '\x1b'
    optns={'ctrlip':'127.0.0.1',
           'ctrlport':50081}
    def __init__(self, rcvCmdProc, optns={}):
        for key in self.__class__.optns.keys():
            if not optns.has_key(key):
                optns[key] = self.__class__.optns[key]
        self.optns = optns
        self.cidint = 0
        self.rcvRawBuf = ''
        self.bufLock = Lock()
        self.rcvCmdProc = rcvCmdProc
        self.creSock()
        self.rcvThr = vththr.CRcvThr(self.sock, self.rcvCmdProc)

    def creSock(self):
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_NODELAY, 1)

    def conn(self, tmo = 5):
        if self.sock is None:
            self.creSock()
            self.rcvThr.rcvr = self.sock
        wtm = time.clock()
        while (time.clock() - wtm) < tmo:
            try:
                self.sock.connect((self.optns['ctrlip'],
                                   self.optns['ctrlport']))
                VTHDebugLogger.debug("3pcc TCP sock(%s:%s) connected"%
                                     (self.optns['ctrlip'],
                                      self.optns['ctrlport']))
                self.rcvThr.start()
                VTHDebugLogger.debug("3pcc TCP rcv thread started")
                return True
            except socket.error, arg:
                (errno, err_msg) = arg
                VTHDebugLogger.debug("3pcc TCP sock conn failed: %s, errno=%d"%
                                     (err_msg, errno))
                time.sleep(1)
                continue
        return False

    def sendCmd(self, cid, cmd = ''):
        if self.sock is None: return
        try:
            sndstr = 'Call-ID: %s%s%s'%\
                     (cid, cmd, '\n' + self.__class__.cmdEndChr)
            self.sock.sendall(sndstr)
            VTHDebugLogger.debug("3pcc TCP sock(%s:%s) sent:\n%s"%
                                 (self.optns['ctrlip'],
                                  self.optns['ctrlport'],
                                  sndstr))
        except socket.error, arg:
            (errno, err_msg) = arg
            VTHDebugLogger.debug("socket send failed: %s, errno=%d"%
                                 (err_msg, errno))

    def getRcvCmds(self):
        self.bufLock.acquire()
        self.rcvRawBuf += self.sock.recv(1024)
        cmds = self.rcvRawBuf.split(self.__class__.cmdEndChr)
        self.rcvRawBuf = cmds[-1]
        self.bufLock.release()
        return cmds[:-1]
        
    def close(self):
        self.rcvThr.reqStop()
        self.rcvThr.join()
        VTHDebugLogger.debug("3pcc TCP rcv thread stopped")
        if self.sock is None: return
        self.sock.close()
        self.sock = None
        VTHDebugLogger.debug('3pcc TCP sock(%s:%s) closed'%
                             (self.optns['ctrlip'],
                              self.optns['ctrlport']))

class CSippUa(CSubP):
    """
    This class inherited from CSubP to start and stop SIPP instance
    sipp command line:
    $SIPPPATH/sipp $siprip:$siprport $scen $mi $mp $inf $3pcc $log $other
    """
    defCmd = 'sipp'
    defOptns={'sn':'uas',
              'log':'-trace_msg -trace_err'}
    qtmr = 5
    pathOp = 'SIPPPATH'
    # dict of cmd arg names and corresponding format + option keys
    argDic = {'remote': {'fmt':'%s:%s', 'ops':('siprip', 'siprport')},
              'locali': {'fmt':'-i %s', 'ops':('siplip',)},
              'localp': {'fmt':'-p %s', 'ops':('siplport',)},
              'mi': {'fmt':'-mi %s'},
              'mp': {'fmt':'-mp %s'},
              'sf': {'fmt':'-sf %s'},
              'sn': {'fmt':'-sn %s'},
              'inf': {'fmt':'-inf %s'},
              'log': {'fmt':'%s'},
              'cc': {'fmt':'-3pcc %s:%s', 'ops':('ctrlip', 'ctrlport')},
              'other': {'fmt':'%s'}
             }

    def validOptns(cls, optns):
        if not isinstance(optns, dict): return False
        if optns.has_key('sf') and optns.has_key('sn'): del optns['sn']
        return True

    validOptns = classmethod(validOptns)

    def bldinstop(self):
        if not self.isRunning():
            self.dbglog("ua might not even have been started yet")
            return
        retc = None;
        try:
            stptm = time.time()
            self.subp.stdout.close()
            self.subp.stdin.write('q')
            while (time.time()-stptm) < self.__class__.qtmr:
                self.dbglog("stopping ua (pid:%d)"%(self.subp.pid))
                time.sleep(1)
                retc = self.subp.poll()
                if retc != None: return
                if not self.subp.stdin.closed: self.subp.stdin.write('q')
        except:
            retc = self.subp.poll()
            raise
        finally:
            if retc != None:
                self.dbglog("ua stopped (ret:%d)"%retc)
            else:
                self.dbglog("failed to stop ua")

class CSipCtrl:
    """
    This class controls SIPP to setup and terminate SIP calls
    """
    rcvCmdPtn = re.compile('^Call-ID: (?P<cid>[\w\@\-\.]*)[\r\n]' +\
                           '(?P<cmd>.*)',
                           re.IGNORECASE|re.MULTILINE|re.DOTALL)
    rtpPtn = re.compile('.*c=IN IP4\s*(?P<rmi>[\d\.]+).*m=audio\s*' +\
                        '(?P<rmp>\d+)\s*RTP/AVP.*' +\
                        'lmi::(?P<lmi>[\d\.]*).*' +\
                        'lmp::(?P<lmp>\d+).*',
                        re.IGNORECASE|re.MULTILINE|re.DOTALL)
    optns={'uas':{},
           'uac':{'siplip':'127.0.0.1'}}

    def __init__(self, optns={}):
        for key in self.__class__.optns.keys():
            if not optns.has_key(key):
                optns[key] = self.__class__.optns[key]
        self.uac = CSippUa(optns['uac'])
        self.uas = CSippUa(optns['uas'])
        self.clnt3pcc = CSipp3pccClnt(self.updCallinfo, optns['uac'])
        self.optns = optns
        self.clst = {}
        self.cseq = 1

    def setupCalls(self, nc = 1):
        clst = []
        cid = None
        try:
            self.uas.run()
            self.uac.run()
            self.clnt3pcc.conn()
            for i in range(0, nc):
                cid = self.newcid(self.uac.subp.pid)
                self.clnt3pcc.sendCmd(cid)
                clst.append(cid)
        except:
            self.termCall(cid)
            VTHDebugLogger.debug('failed to setup call: %s'%(cid))
        finally:
            return clst

    def callCtrl(self, op, args={}, rets={}, cidlst=None):
        if cidlst is None: cidlst = self.clst.keys()
        for cid in cidlst:
            if self.clst.has_key(cid):
                if args.has_key(cid):
                    rets[cid] = op(cid, args[cid])
                else:
                    rets[cid] = op(cid)

    def sndCallCmd(self, cid, cmd = ''):
        if self.clst.has_key(cid):
            self.clnt3pcc.sendCmd(cid, cmd)

    def areCallsReady4RTP(self):
        cstats = {}
        self.callCtrl(self.isCallReady4RTP, rets=cstats)
        VTHDebugLogger.debug("calls state:%s"%(cstats))
        for cid in cstats.keys():
            if not cstats[cid]: return False
        return True

    def isCallReady4RTP(self, cid):
        return self.clst.has_key(cid) and \
               self.clst[cid].has_key('stat') and \
               self.clst[cid]['stat'] == 'TALKING'

    def updCallinfo(self):
        for cmd in self.clnt3pcc.getRcvCmds():
            mat = self.__class__.rcvCmdPtn.match(cmd)
            if mat is not None:
                (cid,contnt) = mat.groups()
                VTHDebugLogger.debug("cid:%s, info:%s"%(cid, contnt))
                mat = re.search('internal-cmd: abort_call', contnt)
                if mat is not None:
                    VTHDebugLogger.debug("abort cid:%s"%(cid))
                    self.clst[cid]['stat'] = 'ABORT'
                    return
                rtpmat = self.__class__.rtpPtn.match(contnt)
                if rtpmat is not None:
                    dic = rtpmat.groupdict()
                    self.clst[cid]['rmi'] = dic['rmi']
                    self.clst[cid]['rmp'] = dic['rmp']
                    self.clst[cid]['lmi'] = dic['lmi']
                    self.clst[cid]['lmp'] = dic['lmp']
                    self.clst[cid]['stat'] = 'TALKING'
                    self.clst[cid]['info'].append(contnt)


    def termCall(self, cid):
        if self.clst.has_key(cid):
            self.sndCallCmd(cid)
            return self.clst.pop(cid, None)

    def waitCallsReady4RTP(self, tmo=10):
        sttm = time.time()
        while not self.areCallsReady4RTP() and time.time()-sttm < tmo:
            time.sleep(1)

    def dumpCallInfo(self):
            VTHDebugLogger.debug("--dump call info--\n%s"%self.clst)

    def stop(self):
        cstat = {}
        self.callCtrl(self.termCall, rets=cstat)
        VTHDebugLogger.debug("--term calls--\n%s"%cstat)
        self.clnt3pcc.close()
        self.uac.stop()
        self.uas.stop()
        self.clst.clear()

    def newcid(self, pid):
        cid = "%d-%s@%s"%(self.cseq,
                          pid,
                          self.optns['uac']['siplip'])
        self.cseq += 1
        self.clst[cid]={'info':[],'stat':''}
        return cid

def test():
    optns = loadf('tbed.conf')
    cctrl = CSipCtrl(optns)
    cctrl.setupCalls()
    cctrl.dumpCallInfo()
    cctrl.waitCallsReady4RTP()
    cctrl.dumpCallInfo()
    time.sleep(5)
    cctrl.stop()
    return

if __name__ == '__main__':
    try:
        test()
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)
