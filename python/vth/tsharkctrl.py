#!/usr/local/bin/python
"""
This module provide tshark controler class inherited from CSubP to
start and stop tshark instance
"""

import sys, time
from subp import CSubP

class CTShark(CSubP):
    defCmd = 'tshark'
    defOptns={'if':'1'}
    qtmr = 5
    pathOp = 'SHARKPATH'
    # dict of cmd arg names and corresponding format + option keys
    argDic = {'if': {'fmt':'-i%s'},
              'inpf': {'fmt':'-r%s'},
              'outf': {'fmt':'-w%s'},
              'cfltr': {'fmt':'-f%s'},
              'dfltr': {'fmt':'-R%s'}
             }

    def validOptns(cls, optns):
        if not isinstance(optns, dict): return False
        if optns.has_key('if') and optns.has_key('inpf'): del optns['inpf']
        return True

    validOptns = classmethod(validOptns)

def test():
    ts = CTShark(optns={'outf':'a.pcap'})
    print ts.isRunning()
    ts.stop()
    ts.run()
    ts.run()
    #ts.prtstdo()
    time.sleep(5)
    ts.stop()
    ts.stop()
    ts.prtstdo()

if __name__ == '__main__':
    try:
        test()
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)
