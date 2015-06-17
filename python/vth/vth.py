#!python

"""
This module contains Voip Test Harness main logic
"""

import random
import sys
import time
import getopt
from copy import deepcopy


import megacomgc
import rtprtcp
import sippctrl
import vthutil

def test():
    from config import optns
# ignore sip and h.248 part
    #cctrl = CSipCtrl(optns)
    #cctrl.setupCalls()
    #cctrl.waitCallsReady4RTP()
    #cctrl.dumpCallInfo()

    rs = rtprtcp.CRtpSession(0, optns['rtp'])
#####################
# some sample code of rtp/rtcp session control
# run rtp/rtcp session for 5 sec
    #rs.start()
    #time.sleep(5)
    #rs.stop()
# turn on receive only
    #rs.optns['ro'] = True
# get ssid from incoming msg of first stream
    #if len(rs.inSstat):
    #    rs.ssid = rs.inSstat.keys()[0]
    #else:
    #    rs.ssid = random.randint(1,0xffffff)
# compose a SD packet in to rtcp msg
    #sdi =  rtprtcp.CRtcpSDItem()
    #sdi.sdType = rtprtcp.RTCP_SDES_TYPE.NAME
    #sdi.txt = 'kajin'
    #rs.rtcpMsgTemp.pkts[1].items.append(sdi)
# send rtp msg in random interval (10~100 ms)
    #rs.rtpSndThr.randIntvl(10,100)
#####################

#####################
# read app name & data form stdin
    try:
        opts, args = getopt.getopt(sys.argv[1:], "", ["appn=", "appd=", "appxd="])
        appn = 'APP1'
        appd = 'data'
        for opt, arg in opts:
            if opt in ('--appn'):
                appn = arg
            elif opt in ('--appd'):
                appd = arg
            elif opt in ('--appxd'):
                appd = vthutil.txt2hex(arg)
    except getopt.GetoptError:
        print "invalid command line opts"
        exit(2)
# compose app data packet and send out in rtcp msg
    rs.start()
    msg = deepcopy(rs.rtcpMsgTemp)
    AppPkt = rtprtcp.CRtcpPacket()
    app = rtprtcp.CRtcpAppData()
    app.name = appn
    app.data = appd
    AppPkt.pktType = rtprtcp.RTCP_PKT_TYPE.APP
    AppPkt.items = [app]
    msg.pkts.append(AppPkt)
    rs.rtcpMsgOut(msg)

    rs.stop()
    #cctrl.stop()

#####################
# unit test of sender/receiver report en/decode
def testUpdSrRr():
    stat = rtprtcp.CRtpSrcStat()
    stat.lastSeq += 1
    stat.pktCnt += 2
    stat.bytCnt += 10
    stat.lastRtpTs += 100
    rr = rtprtcp.CRtcpRRBlk()
    sr = rtprtcp.CRtcpSdrInfo()
    msg = rtprtcp.CRtcpMsg()
    sr.updBySrcStat(stat)
    msg.pkts[0].sdrInfo = sr
    from vthutil import hexdump
    print "== raw ==\n" + hexdump(msg.enc()) + "== == =="
    print msg.getPrtTxt()
    sdrInfo = msg.pkts[0].sdrInfo
    stat.lastSeq += 1
    stat.pktCnt += 2
    stat.bytCnt += 10
    stat.lastRtpTs += 100
    sdrInfo.updBySrcStat(stat)
    # compose receiption report blocks
    msg.pkts[0].items = []
    rrblk = rtprtcp.CRtcpRRBlk()
    rrblk.updBySrcStat(stat)
    msg.pkts[0].items.append(rrblk)
    from vthutil import getNtpTimestamp
    (msw, lsw) = getNtpTimestamp()
    (sdrInfo.ntpMsw, sdrInfo.ntpLsw) = (msw, lsw)
    print "== raw ==\n" + hexdump(msg.enc()) + "== == =="
    print msg.getPrtTxt()


if __name__ == '__main__':
    try:
        test()
        #testUpdSrRr()
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)
