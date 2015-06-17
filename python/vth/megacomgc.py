#!/usr/local/bin/python

"""
This module provides megaco mgc classes:
CMegacoMgc
"""

import re
import select
import socket
import string
import sys
import threading
import time
from vthlogger import VTHDebugLogger

"""
to do
 - change codec
 - change thread
 - call setup
 - compact mode
 - get ip, port via recvfrom()
"""

class CMgcRcvThr(threading.Thread):
    """
    This thread receive megaco msg from MG and reply SERVICECHANGE and NOTIFY
    """
    def __init__(self, mgc, intvl=5):
        threading.Thread.__init__(self)
        self.mgc = mgc
        self.reqst = False
        self.intvl = intvl

    def reqStop(self):
        VTHDebugLogger.debug("request stop megrcv thr")
        self.reqst = True

    def run(self):
        while True:
            if self.reqst: break
            # Await a read event
            rlist, wlist, elist = select.select([self.mgc.sock], [], [],
                                                self.intvl)
            if self.reqst: break
            # Test for timeout
            if [rlist, wlist, elist] == [[], [], []]:
                print "no msg in last %d sec"%self.intvl
            else:
                msg, (addr, port) = self.mgc.sock.recvfrom(1024)
                VTHDebugLogger.debug("==rcv msg from (%s:%s)==\n%s"%
                                     (addr, port, msg))
                msgDic = self.mgc.decMegMsg(msg)
                if not self.mgc.optns.has_key('mgip'):
                    self.mgc.optns['mgip'] = addr
                else:
                    if self.mgc.optns['mgip'] != addr:
                        VTHDebugLogger.debug("ignor meg msg: " +
                                             "inconsis MG IP cur %s new %s"%
                                             (self.mgc.optns['mgip'], addr))
                        continue
                if not self.mgc.optns.has_key('mgport'):
                    self.mgc.optns['mgport'] = port
                else:
                    if self.mgc.optns['mgport'] != port:
                        VTHDebugLogger.debug("ignor meg msg: " +
                                             "inconsis MG Port cur %s new %s"%
                                             (self.mgc.optns['mgport'], port))
                        continue
                if msgDic['msgType'] == 'TRANSACTION':
                    msgDic['msgType'] = 'REPLY'
                    msgDic['megIp'] = self.mgc.optns['mgcip']
                    msgDic['megPort'] = self.mgc.optns['mgcport']
                    mgIS = False
                    if msgDic['megCmd'] == 'NOTIFY':
                        if not re.match('OBSERVEDEVENTS = 0 { ' +
                                        'CHP/MGCON { REDUCTION = 0' +
                                        '} }', msgDic['msgLeft']) is None:
                            mgIS = True
                        msgDic['msgLeft'] = '\n  }\n}'
                    retmsg = self.mgc.encMegMsg(msgDic)
                    self.mgc.sock.sendto(retmsg,(addr,port))
                    VTHDebugLogger.debug("==send msg==\n%s"%(retmsg))
                    self.mgc.mgIS = mgIS
                    if not self.mgc.optns.has_key('mgip'):
                        self.mgc.optns['mgip'] = addr
                    if not self.mgc.optns.has_key('mgport'):
                        self.mgc.optns['mgport'] = port

class CMegacoMgc:
    """
    This class works as MGC interact with MG via Megaco
    """

    megMsgPtn = re.compile('MEGACO/1 \[(?P<megIP>[\d\.]*)\]:' +
                           '(?P<megPort>\d*)\s*' +
                           '(?P<msgType>\w*)\s*=\s*' +
                           '(?P<transID>\d*)\s*{\s*CONTEXT\s*=\s*' +
                           '(?P<contxID>[-\*\d\$]*)\s*{\s*' +
                           '(?P<megCmd>\w*)\s*=\s*' +
                           '(?P<termID>[^\s{]*)\s*(?P<msgLeft>.*)',
                           re.DOTALL)
    megMsgTemp = string.Template('MEGACO/1 [$megIP]:$megPort\n' +
                                 '$msgType = $transID {\n' +
                                 '  CONTEXT = $contxID {\n' +
                                 '    $megCmd = $termID ' +
                                 '$msgLeft')

    optns={'mgcip':'135.252.136.97',
           'mgcport':3000}

    def __init__(self, optns={}):
        for key in self.__class__.optns.keys():
            if not optns.has_key(key):
                optns[key] = self.__class__.optns[key]
        self.mgIS = False
        self.mgcTransID = 0
        self.optns = optns
        self.creSock()
        self.Lock = threading.Lock()
        self.rcvThr = CMgcRcvThr(self)

    def getTransID(self):
        tid = self.mgcTransID
        self.mgcTransID += 1
        return tid

    def creSock(self):
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def wait4MgIs(self, tmo=5):
        tw = time.time()
        while time.time()-tw < tmo and not self.mgIS:
            time.sleep(1)

    def sndRawMsg(self, msg):
        if not self.mgIS: return
        try:
            msg = 'MEGACO/1 [%s]:%s\n%s'%(self.optns['mgip'],
                                          self.optns['mgport'],
                                          msg)
            self.sock.sendto(msg,(self.optns['mgip'], self.optns['mgport']))
            VTHDebugLogger.debug("==send msg==\n%s"%(retmsg))
        except Exception, err:
            VTHDebugLogger.debug("snd Raw Meg msg err:%s"%(err))

    def decMegMsg(self, msg):
        mat = self.__class__.megMsgPtn.match(msg)
        return mat.groupdict()

    def encMegMsg(self, msgDic):
        return self.__class__.megMsgTemp.safe_substitute(msgDic)

    def start(self, tmo = 5):
        if self.sock is None: self.creSock()
        self.sock.bind((self.optns['mgcip'], self.optns['mgcport']))
        self.rcvThr.start()

    def stop(self):
        self.rcvThr.reqStop()
        self.rcvThr.join()
        self.sock.close()
        self.mgIS = False

def test():
    mgc = CMegacoMgc()
    mgc.start()
    msg = \
'''TRANSACTION = 4182 {
  CONTEXT = $ {
    ADD = T/15/84/1 {
      MEDIA {
        LOCALCONTROL { TDMC/EC = ON,
                       TDMCEX/ETL = 32,
                       TDMCEX/NLP = ON,
                       TDMCEX/ECDIS = G168,
                       MODE = SENDONLY }
      }
    },
    ADD = I/$ {
      MEDIA {
        LOCAL {
v=0

c=IN IP4 $

m=audio $ RTP/AVP 0 96 97 98

a=ptime:20

a=rtpmap:0 PCMU/8000

a=rtpmap:96 Cisco-clear-channel/8000

a=rtpmap:97 X-CCD/8000

a=rtpmap:98 clearmode/8000

a=silenceSupp:off - - - -
        },
        LOCALCONTROL { MODE = RECEIVEONLY,
                       RESERVEDVALUE = ON }
      }
    }
  }
}'''
    mgc.wait4MgIs()
    mgc.sndRawMsg(msg)
    time.sleep(60)
    mgc.stop()
    return

if __name__ == '__main__':
    try:
        test()
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)
