#!/usr/local/bin/python
"""
This module contain the class of class CSubP, which wraps python
buildin subprocess to run and stop a external subprocess
"""

from subprocess import Popen, PIPE, STDOUT
import time, sys
from vthlogger import VTHDebugLogger

class CSubP:
    """
    This class wrapps subprocess to ru and stop external command in subprocess
    """
    defCmd = 'tshark'
    defOptns={'ARGS':'-h'}
    qtmr = 5
    pathOp = 'PATH'
    # dict of cmd arg names and corresponding format + option keys
    argDic = {'ARGS':
              {'fmt':'%s'}
             }

    def popDefOptns(cls, optns):
        """
        populate missing optn values with default values in class.optns
        """
        for key in cls.defOptns.keys() :
            if not optns.has_key(key) :
                optns[key] = cls.defOptns[key]

    popDefOptns = classmethod(popDefOptns)

    def validOptns(cls, optns):
        """
        validate given options
        """
        return isinstance(optns, dict)

    validOptns = classmethod(validOptns)

    def __init__(self, optns={}, cmd=None):
        # populate cmd with default value in class.cmd
        if cmd is None: cmd = self.__class__.defCmd
        self.optns = optns.copy()
        # populate missing optn values with default values in class.optns
        self.popDefOptns(self.optns)
        # validate options
        self.validOptns(self.optns)
        self.subp = None
        self.pname = cmd
        if self.optns.has_key('wkdir'):
            self.wkdir = self.optns['wkdir']
        else:
            self.wkdir = None
        self.compCmd()

    def compCmd(self, optns=None):
        """
        compose cmd array according to given options
        """
        # use object options if input None optns
        if optns is None : optns = self.optns.copy()
        # populate missing optn values with default values in class.optns
        self.popDefOptns(optns)
        # validate options
        if not self.validOptns(optns):
            VTHDebugLogger.debug("given options invalid.\n%s"%(optns))
            return
        cmdArray = []
        # add path before cmd if given in optns
        cmd = self.pname
        if optns.has_key(self.__class__.pathOp) and\
           optns[self.__class__.pathOp]:
            cmd = optns[self.__class__.pathOp] + '/' + cmd
        cmdArray.append(cmd)
        # populate arguments from given optns according to class.argDic
        for arg in self.__class__.argDic.keys():
            ops = []
            opvs = []
            if self.__class__.argDic[arg].has_key('ops'):
                ops = self.__class__.argDic[arg]['ops']
            else:
                ops = (arg,)
            for op in ops:
                if optns.has_key(op) and optns[op]:
                    opvs.append(optns[op])
                else:
                    opvs = []
                    break
            if opvs != []:
                argv = ''
                try:
                    argv = self.__class__.argDic[arg]['fmt']%tuple(opvs)
                except Exception, err:
                    VTHDebugLogger.debug("get argv from options err:%s"%(err))
                    argv = ''
                if argv:
                    cmdArray.extend(argv.split())
        if cmdArray: self.cmdArray = cmdArray
        return cmdArray

    def dbglog(self, str):
        VTHDebugLogger.debug("(subp:%s) %s"%(self.pname, str))

    def isRunning(self):
        return self.subp != None and self.subp.poll() == None

    def run(self):
        if self.isRunning():
            self.dbglog("still running (pid:%s)"%(self.subp.pid))
            return
        self.dbglog("subp cmd line:%s"%(' '.join(self.cmdArray)))
        self.subp = Popen(self.cmdArray, cwd=self.wkdir,
                          stdin=PIPE, stdout=PIPE, stderr=STDOUT)
        self.dbglog("subp started (pid:%s)"%self.subp.pid)

    def prtstdo(self):
        if self.subp == None or self.subp.stdout.closed: return
        for line in self.subp.stdout.readlines():
            print line

    def bldinstop(self):
        pass

    def stop(self):
        if not self.isRunning():
            self.dbglog("already stopped")
            return
        self.bldinstop()
        if not self.isRunning(): return
        retc = None;
        try:
            self.dbglog("terminating (pid:%d)"%(self.subp.pid))
            self.subp.stdout.close()
            self.subp.terminate()
            time.sleep(1)
            retc = self.subp.poll()
            if retc != None:
                return
            self.dbglog("killing (pid:%d)"%(self.subp.pid))
            self.subp.kill()
            time.sleep(1)
            retc = self.subp.poll()
            if retc != None:
                self.dbglog("killed (ret:%d)"%retc)
                return
        except:
            retc = self.subp.poll()
            raise
        if retc != None:
            self.dbglog("stopped (ret:%d)"%retc)
        else:
            self.dbglog("failed to stop")

def test():
    aSubp = CSubP()
    print aSubp.isRunning()
    aSubp.stop()
    aSubp.run()
    aSubp.run()
    aSubp.prtstdo()
    aSubp.stop()
    aSubp.stop()
    aSubp.prtstdo()

if __name__ == '__main__':
    try:
        test()
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)
