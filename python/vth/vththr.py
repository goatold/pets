#!/usr/bin/python

import re
import select
import threading
import time
from random import randint
from vthlogger import VTHDebugLogger

class CTaskThr(threading.Thread):
    """
    This is a task thread exec input task
    at given interval (ms) and repeat for given times (0 means infinite)
    """
    def __init__(self, task, intvl=20, maxrep=0):
        threading.Thread.__init__(self)
        self.task = task
        self.reqst = False
        self.intvl = intvl
        self.startTime = 0
        self.maxrep = maxrep
        self.repeat = 0
        self.rand = False
        self.randmin = intvl
        self.randmax = intvl        

    def chkStop(self):
        if self.reqst: return True
        if (self.maxrep > 0) and (self.repeat >= self.maxrep): return True
        return False
        
    def reqStop(self):
        VTHDebugLogger.debug("request stop task thr")
        self.reqst = True
    
    def randIntvl(self, min=10, max=100):   
        self.rand = True
        self.randmin = min
        self.randmax = max
    
    def constIntvl(self, intvl=20):   
        self.rand = False
        self.intvl = intvl
    
    def run(self):
        self.startTime = time.time()
        lasttm = time.clock()
        while True:
            if self.chkStop(): break
            if self.rand:
                delay = randint(self.randmin, self.randmax)/1000.0
            else:
                delay = self.intvl/1000.0
            intvl = time.clock() - lasttm
            if intvl < delay:
                time.sleep(delay-intvl)
            self.task()
            lasttm = time.clock()
            self.repeat += 1

class CRcvThr(threading.Thread):
    """
    This thread receive from receiver and run given proc
    """
    def __init__(self, rcvr, proc, intvl=5):
        threading.Thread.__init__(self)
        self.rcvr = rcvr
        self.proc = proc
        self.reqst = False
        self.intvl = intvl

    def reqStop(self):
        VTHDebugLogger.debug("request stop rcv thr")
        self.reqst = True

    def run(self):
        w = 0
        while True:
            if self.reqst: break
            # Await a read event
            rlist, wlist, elist = select.select([self.rcvr], [], [], 1)
            if self.reqst: break
            w += 1
            # Test for timeout
            if [rlist, wlist, elist] == [[], [], []]:
                if w >= self.intvl:
                    w = 0
                    print "rcv none in last %d sec"%self.intvl
                else:
                    continue
            else:
                self.proc()
                w = 0

def test():
    from time import clock, sleep
    def task():
        print clock()
        
    tth = CTaskThr(task, 820)
    tth.start()
    sleep(3)
    print '---- rand ----'
    tth.randIntvl(500,1000)
    sleep(3)
    print '---- const ----'
    tth.constIntvl(950)
    sleep(3)
    tth.reqStop()
    tth.join()
    
if __name__ == '__main__':
    try:
        test()
    except KeyboardInterrupt:
        sys.exit(1)
