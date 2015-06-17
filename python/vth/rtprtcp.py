#!/usr/local/bin/python

"""
This module includes RTP/RTCP session controler and codec
"""

import random
import select
import socket
import sys
import struct
import threading
import time
from copy import deepcopy

import vththr
from vthlogger import VTHDebugLogger
from vthutil import getNtpTimestamp, ntpTimestamp2Time, txt2hex, hexdump

class RTCP_PKT_TYPE:
    (SR, RR, SDES, BYE, APP) = range(200, 205)

class RTCP_SDES_TYPE:
    (CNAME, NAME, EMAIL, PHONE, LOC, TOOL, NOTE, PRIV)  = range(1, 9)

class CRtpSrcStat:
    """
    RTP per source state info
    """
    def __init__(self):
        self.lastSeq = random.randint(0, 0xffff)    # last rtp seq
        self.lastCycle = 0  # last rtp seq cycle
        self.pktCnt = 0     # packet count
        self.bytCnt = 0     # cumulative byte count of RTP pay load
        self.lastMsw = 0    # NTP timestamp MSW in last SR
        self.lastLsw = 0    # NTP timestamp LSW in last SR
        self.lastRtpTs = 0  # last RTP timestamp
        self.jitter = 0
        self.lock = threading.Lock()

    def getAttrb(name):
        self.lock.acquire()
        v = getattr(self, name)
        self.lock.release()
        return v

    def setAttrb(self, name, v):
        self.lock.acquire()
        setattr(self, name, v)
        self.lock.release()

    def nextSeq(self):
        self.lock.acquire()
        self.lastSeq += 1
        if self.lastSeq > 0xffff:
            self.lastSeq = 0x0000
            self.lastCycle += 1
        seq = self.lastSeq
        self.lock.release()
        return seq

    def nextTs(self, step):
        self.lock.acquire()
        if self.lastRtpTs == 0:
            self.lastRtpTs = random.randint(1, 0xffff)
        self.lastRtpTs += step
        self.lastRtpTs &= 0xffffffff
        ts = self.lastRtpTs
        self.lock.release()
        return ts

class CRtcpAppData:
    """
    Class of RTCP Application dependent data
    """
    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.name = 'APP1'
        self.data = 'data'

    def dec(self, rawStr):
        self.name = rawStr[:4]
        self.data = rawStr[4:]
        return len(rawStr)

    def enc(self):
        # encode app name and data
        if self.name and len(self.name) >= 4:
            rawStr = self.name[:4]
        else:
            rawStr = 'APP1'
        if self.data:
            md4 = len(self.data)%4
            if md4 != 0:
                # padding with 0x00
                self.data += '\x00'*(4 - md4)
            rawStr += self.data
        else:
            rawStr += 'data'
        return rawStr

    def getPrtTxt(self):
        pstr = ('== APP name: %s ==\n' +\
                '== APP data: ==\n%s')%(self.name, hexdump(self.data))
        return pstr

class CRtcpSSID:
    """
    Class of RTCP SSRC ID
    """
    fmt = struct.Struct('!L')
    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.ssid = 0

    def dec(self, rawStr):
        stru = CRtcpSSID.fmt
        offset = stru.size
        self.ssid = stru.unpack(rawStr[:offset])
        self.len = offset
        return offset

    def enc(self):
        # encode ssid
        rawStr = CRtcpSSID.fmt.pack(self.ssid)
        return rawStr

    def getPrtTxt(self):
        pstr = ('== SSRC ID: %X ==\n')%(self.ssid)
        return pstr

class CRtcpRRBlk:
    """
    Class of RTCP Receiption Report block
    """
    rrBlkStruct = struct.Struct('!LLLLLL')
    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.ssid = 0
        self.fractLost = 0
        self.pktLost = 0
        self.lastRtpSeq = 0
        self.jitter = 0
        self.lastSRTmStmp = 0
        self.lastSRDelay = 0

    def dec(self, rawStr):
        stru = CRtcpRRBlk.rrBlkStruct
        offset = stru.size
        (self.ssid, lost, self.lastRtpSeq, self.jitter,
         self.lastSRTmStmp, self.lastSRDelay) = stru.unpack(rawStr[:offset])
        # 1 bytes of fraction lost 3 bytes of packets lost
        self.fractLost = lost >> 24
        self.pktLost = lost & 0xffffff
        self.len = offset
        return offset

    def enc(self):
        lost = (self.fractLost << 24) + self.pktLost
        rawStr = CRtcpRRBlk.rrBlkStruct.pack(self.ssid, lost, self.lastRtpSeq,
                                             self.jitter, self.lastSRTmStmp,
                                             self.lastSRDelay)
        return rawStr

    def updBySrcStat(self, stat):
        stat.lock.acquire()
        self.lastRtpSeq = (stat.lastCycle<<16) + stat.lastSeq
        self.jitter = stat.jitter
        self.lastSRTmStmp = (stat.lastMsw<<16) + (stat.lastLsw>>16)
        stat.lock.release()

    def getPrtTxt(self):
        pstr = ('== RTCP RR Block ==\n' +\
                'SSRC ID: %X, Fraction Lost: %d, Packets Lost: %d,\n' +\
                'latest RTP Seq:%d, Jitter: %d,\n' +\
                'Last Sender report Timestamp: %d, Delay since last SR: %d\n')%\
               (self.ssid, self.fractLost, self.pktLost, self.lastRtpSeq,
                self.jitter, self.lastSRTmStmp, self.lastSRDelay)
        return pstr

class CRtcpSdrInfo:
    """
    Class of RTCP sender info
    """
    sdrInfStruct = struct.Struct('!LLLLL')
    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.ntpMsw = 0
        self.ntpLsw = 0
        self.rtpTmStmp = 0
        self.sndrPktCnt = 0
        self.sndrBytCnt = 0

    def dec(self, rawStr):
        stru = CRtcpSdrInfo.sdrInfStruct
        (self.ntpMsw, self.ntpLsw, self.rtpTmStmp,
         self.sndrPktCnt, self.sndrBytCnt) = stru.unpack(rawStr[:stru.size])
        return stru.size

    def enc(self):
        # encode sender info
        rawStr = CRtcpSdrInfo.sdrInfStruct.pack(self.ntpMsw, self.ntpLsw,
                                                self.rtpTmStmp, self.sndrPktCnt,
                                                self.sndrBytCnt)
        return rawStr

    def updBySrcStat(self, stat):
        stat.lock.acquire()
        self.rtpTmStmp = stat.lastRtpTs
        self.sndrPktCnt = stat.pktCnt
        self.sndrBytCnt = stat.bytCnt
        stat.lock.release()

    def getPrtTxt(self):
        pstr = ('== RTCP sender Info ==\n' +\
                'NTP MSW: %d, NTP LSW:%d, RTP Timestamp: %d,\n' +\
                'Send Packets: %d, Send Octets: %d\n')%\
               (self.ntpMsw, self.ntpLsw, self.rtpTmStmp,
                self.sndrPktCnt, self.sndrBytCnt)
        return pstr

class CRtcpSDItem:
    """
    Class of RTCP SDES (Source Desctiption) Item
    """
    hdrStruct = struct.Struct('!BB')
    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.sdType = 0
        self.txtlen = 0
        self.txt = ''

    def dec(self, rawStr):
        stru = CRtcpSDItem.hdrStruct
        offset = stru.size
        (self.sdType, self.txtlen) = stru.unpack(rawStr[:offset])
        # SD text
        self.txt = rawStr[offset:offset+self.txtlen]
        return offset+self.txtlen

    def enc(self):
        self.txtlen = len(self.txt)
        rawStr = CRtcpSDItem.hdrStruct.pack(self.sdType, self.txtlen) + self.txt
        return rawStr

    def getPrtTxt(self):
        pstr = ('== SDES item ==\n' +\
                'SDES Type: %d, len:%d,\ntext: %s\n')%\
               (self.sdType, self.txtlen, self.txt)
        return pstr

class CRtcpSDChnk:
    """
    Class of RTCP SDES (Source Desctiption) Chunk
    """
    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.ssid = 0
        self.items = []

    def dec(self, rawStr):
        offset = 4
        (self.ssid) = struct.unpack('!L', rawStr[:offset])
        # SD items
        self.items = []
        # first byte is item type, 0 means END of chunk
        while rawStr[offset] != '\x00':
            item = CRtcpSDItem(rawStr[offset:])
            self.items.append(item)
            # len of item is txt len + 2 bytes header len
            offset += item.txtlen + 2
        # last byte is 0 to end chunk
        offset += 1
        # chunk len should be multiple of 4 bytes
        mod = offset%4
        if mod:
            offset += (4-mod)
        self.len = offset
        return offset

    def enc(self):
        rawStr = struct.pack('!L', self.ssid)
        for item in self.items:
            rawStr += item.enc()
        # end chunk with '\x00'
        rawStr += '\x00'
        chnklen = len(rawStr)
        mod = chnklen % 4
        if mod:
            rawStr += '\x00' * (4 - mod)
        return rawStr

    def getPrtTxt(self):
        pstr = ('== SDES Chunk ==\nSSRC ID: %X\n')%(self.ssid)
        for item in self.items:
            pstr += item.getPrtTxt()
        return pstr

class CRtcpPacket:
    """
    This is the generalized class of RTCP packets
    """
    genHdr = struct.Struct('!BBH')
    pktSchm = {RTCP_PKT_TYPE.SR: ((CRtcpSSID, CRtcpSdrInfo), CRtcpRRBlk),
               RTCP_PKT_TYPE.RR: ((CRtcpSSID, ), CRtcpRRBlk),
               RTCP_PKT_TYPE.SDES: ((), CRtcpSDChnk),
               RTCP_PKT_TYPE.BYE: ((), CRtcpSSID),
               RTCP_PKT_TYPE.APP: ((CRtcpSSID,), CRtcpAppData)
              }

    def __init__(self, rawStr=None):
        if rawStr is not None:
            self.dec(rawStr)
            return
        self.ver = 2
        self.padding = False
        self.count = 0
        self.pktType = RTCP_PKT_TYPE.SR
        self.len = 0
        self.ssid = None
        self.sdrInfo = None
        self.addOptRsnTxt = False
        self.items = []

    def dec(self, rawStr):
        # decode fixed rtcp packet header
        stru = CRtcpPacket.genHdr
        offset = stru.size
        (b1, self.pktType, self.len) = stru.unpack(rawStr[:offset])
        # first 2 bits is version
        self.ver = b1 >> 6
        # third bit is padding flag
        self.padding = bool(b1 & 0b00100000)
        # bit 3~7 is count
        self.count = b1 & 0b11111
        if not CRtcpPacket.pktSchm.has_key(self.pktType):
            VTHDebugLogger.debug(('unsupported packet type: %d\n' +\
                                  'raw string:\n%s\n')%\
                                 (self.pktType, hexdump(rawStr)))
            return offset
        schm = CRtcpPacket.pktSchm[self.pktType]
        if CRtcpSSID in schm[0]:
            self.ssid = struct.unpack('!L', rawStr[offset:offset+4])
            offset += 4
        else:
            self.ssid = None
        if CRtcpSdrInfo in schm[0]:
            self.sdrInfo = CRtcpSdrInfo(rawStr[offset:])
            offset += CRtcpSdrInfo.sdrInfStruct.size
        else:
            self.sdrInfo = None
        codec = schm[1]
        self.items = []
        if self.pktType != RTCP_PKT_TYPE.APP:
            for i in range(self.count):
                item = codec(rawStr[offset:])
                self.items.append(item)
                offset += item.len
        else:
            appData = CRtcpAppData(rawStr[offset:])
            self.items.append(appData)
            offset = len(rawStr)
        pktlen = (self.len+1)*4
        if offset < pktlen:
            VTHDebugLogger.debug('undecoded raw data:\n%s'%(hexdump(rawStr[offset:])))
        return offset

    def enc(self):
        rawStr = ''
        if not CRtcpPacket.pktSchm.has_key(self.pktType):
            VTHDebugLogger.debug('Can NOT encode packet type: %d\n'%\
                                 (self.pktType))
            return rawStr
        schm = CRtcpPacket.pktSchm[self.pktType]
        if CRtcpSSID in schm[0]:
            if self.ssid is None:
                self.ssid = 0
            rawStr += struct.pack('!L', self.ssid)
        if CRtcpSdrInfo in schm[0]:
            if self.sdrInfo is None:
                self.sdrInfo = CRtcpSdrInfo()
            rawStr += self.sdrInfo.enc()
        self.count = 0
        # for APP pkt, self.count represent as subtype, put as 0 now
        if self.pktType != RTCP_PKT_TYPE.APP:
            for item in self.items:
                if isinstance(item, schm[1]):
                    rawStr += item.enc()
                    self.count += 1
        else:
            if len(self.items) and isinstance(self.items[0], CRtcpAppData):
                rawStr += self.items[0].enc()
            else:
                rawStr += 'APP1data'
        b1 = (self.ver << 6)+ (self.padding << 5) + self.count
        # add optional reason txt
        if self.addOptRsnTxt:
            if self.pktType == RTCP_PKT_TYPE.BYE or \
               self.pktType == RTCP_PKT_TYPE.SR:
                rawStr += struct.pack('!B', 3) + 'rsn'
        pktlen = len(rawStr) + CRtcpPacket.genHdr.size
        (div, mod) = divmod(pktlen, 4)
        if mod:
            self.len = div
            # pading
            if self.padding: rawStr += '\x00' * (4 - mod)
        else:
            self.len = div - 1
        rawStr = CRtcpPacket.genHdr.pack(b1, self.pktType, self.len) + rawStr
        return rawStr

    def getPrtTxt(self):
        pstr = ('== RTCP packet header ==\n' +\
                'ver:%d, pad flag: %s, item count:%d, ' +\
                'pkt type: %d, len: %d\n')%\
               (self.ver, self.padding, self.count,
                self.pktType, self.len)
        if self.ssid is not None:
            pstr += '== SSRC ID: %X ==\n'%(self.ssid)
        if self.sdrInfo is not None:
            pstr += self.sdrInfo.getPrtTxt()
        if self.items is not None:
            for item in self.items:
                pstr += item.getPrtTxt()
        return pstr

class CRtcpMsg:
    """
    This class encode/decode rtcp msg
    """
    def __init__(self, msg=None,
                 pktLst=(RTCP_PKT_TYPE.SR, RTCP_PKT_TYPE.SDES)):
        # init RTCP msg by decoding given msg
        if msg is not None:
            self.dec(msg)
            return
        # Otherwise init RTCP msg by given paket type list
        self.pkts = []
        for pt in pktLst:
            pkt = CRtcpPacket()
            pkt.pktType = pt
            self.pkts.append(pkt)

    def dec(self, msg):
        self.pkts = []
        rawlen = len(msg)
        offset = 0
        while offset < rawlen:
            try:
                pkt = CRtcpPacket(msg[offset:])
                self.pkts.append(pkt)
                offset += (pkt.len+1) * 4
            except Exception, err:
                VTHDebugLogger.debug(('decode RTCP msg err:%s\n' +\
                                      'raw msg:\n%s')%(err, hexdump(msg)))
                break
        return offset

    def enc(self):
        msg = ''
        for pkt in self.pkts:
            try:
                msg += pkt.enc()
            except Exception, err:
                VTHDebugLogger.debug('encode RTCP msg err:%s'%(err))
                break
        return msg

    def getPrtTxt(self):
        pstr = ''
        for pkt in self.pkts:
            try:
                pstr += pkt.getPrtTxt()
            except Exception, err:
                VTHDebugLogger.debug('print RTCP msg err:%s'%(err))
                break
        return pstr

class CRtpMsg:
    """
    This class encode/decode rtp msg
    """

    hdrFmt = struct.Struct('!BBHLL')
    def __init__(self, msg=None, plen=160):
        if msg is not None:
            self.dec(msg)
            return
        self.ver = 2
        self.padding = False
        self.ext = False
        self.csrcCnt = 0
        self.marker = False
        self.ptype = 0
        self.seq = 0
        self.tmStmp = 0
        self.ssid = 0
        self.csrcs = []
        self.pload = '\xff'*plen

    def dec(self, msg):
        offset = CRtpMsg.hdrFmt.size
        hdr = msg[:offset]
        (b1, b2, self.seq,
         self.tmStmp, self.ssid) = CRtpMsg.hdrFmt.unpack(hdr)
        # first 2 bit is version
        self.ver = b1 >> 6
        # third bit is padding flag
        self.padding = bool(b1 & 0b00100000)
        # 4th bit is extension flag
        self.ext = bool(b1 & 0b00010000)
        # last 4 bits are count
        self.csrcCnt = bool(b1 & 0b00001111)
        # first bit of 2nd byte is marker flag
        self.marker = bool(b2 >> 7)
        self.ptype = b2 & 0x7f
        #decode csrc
        self.csrcs = []
        for i in range(self.csrcCnt):
            csrc = struct.unpack('!L', msg[offset:offset+4])
            self.csrcs.append(csrc)
            offset += 4
        # payload after csrc list
        self.pload = msg[offset:]
        return self

    def enc(self):
        # encode header
        b1 = (self.ver << 6) + (self.padding << 5) +\
             (self.ext << 4) + self.csrcCnt
        b2 = ((self.marker<<7) | self.ptype)
        rawStr = CRtpMsg.hdrFmt.pack(b1, b2, self.seq, self.tmStmp, self.ssid)
        # encode csrc
        for csrc in self.csrcs:
            rawStr += struct.pack('!L', csrc)
        return rawStr + self.pload

    def getPrtTxt(self):
        pstr = ('== RTP msg header ==\n' +\
                'ver:%d, pad flag: %s, Extension Flag:%d, ' +\
                'contrib src count: %d, marker: %s, payload type: %d\n' +\
                'seq: %d, timestamp: %d, SSRC ID: %X\n')%\
               (self.ver, self.padding, self.ext, self.csrcCnt, self.marker,
                self.ptype, self.seq, self.tmStmp, self.ssid)
        return pstr

class CRtpSession:
    """
    This class handle RTP/RTCP session
    """

    optns={'loci':'127.0.0.1',
           'locp':3000,
           'rmti':'127.0.0.1',
           'rmtp':3000,
           'codec': 0,
           'ptime': 20,
           }

    def __init__(self, ssid, optns={}):
        self.optns = optns.copy()
        for key in self.__class__.optns.keys():
            if not self.optns.has_key(key):
                self.optns[key] = self.__class__.optns[key]
        self.rtpSock = self.creSock()
        self.rtcpSock = self.creSock()
        self.__initRcv()
        if (not self.optns.has_key('ro')) or (not self.optns['ro']):
            self.__initSnd(ssid)
        else:
            self.__sndNeedReIn = True
            self.__sndRunning = False

    def __initSnd(self, ssid):
        # out going SSRC properties
        self.ssid = ssid
        self.sstat = CRtpSrcStat()
        # outgoing RTP properties
        self.rtpTsIncr = self.optns['ptime']*8
        # compose out going rtp msg template
        self.oRtpMsg = CRtpMsg(plen=self.optns['ptime']*8)
        self.oRtpMsg.ssid = ssid
        self.oRtpMsg.ptype = self.optns['codec']
        # compose out going rtcp msg template
        # default CRtcpMsg construct with pkts[0]:SR, pkts[1]:SDES
        self.rtcpMsgTemp = CRtcpMsg()
        sdrInfo = CRtcpSdrInfo()
        sdrInfo.updBySrcStat(self.sstat)
        self.rtcpMsgTemp.pkts[0].ssid = self.ssid
        self.rtcpMsgTemp.pkts[0].sdrInfo = sdrInfo
        chnk = CRtcpSDChnk()
        chnk.ssid = self.ssid
        itm = CRtcpSDItem()
        itm.sdType = RTCP_SDES_TYPE.CNAME
        itm.txt = '%x@%s'%(self.ssid, self.optns['loci'])
        chnk.items.append(itm)
        self.rtcpMsgTemp.pkts[1].items.append(chnk)
        # create RTP/RTCP send threads
        self.rtpSndThr = vththr.CTaskThr(self.rtpMsgOut,
                                         self.optns['ptime'])
        self.rtcpSndThr = vththr.CTaskThr(self.rtcpMsgOut, 5000)
        self.__sndNeedReIn = False
        self.__sndRunning = False

    def __initRcv(self):
        # incoming SSRC properties
        self.inSstat = {}
        # create RTP/RTCP receive threads
        self.rtpRcvThr = vththr.CRcvThr(self.rtpSock, self.rtpMsgIn)
        self.rtcpRcvThr = vththr.CRcvThr(self.rtcpSock, self.rtcpMsgIn)
        self.__rcvNeedReIn = False
        self.__rcvRunning = False


    def rtpMsgIn(self):
        msg = self.rtpSock.recv(1024)
        rtpmsg = CRtpMsg(msg)
        ssid = rtpmsg.ssid
        if not self.inSstat.has_key(ssid):
            self.inSstat[ssid] = CRtpSrcStat()
        stat = self.inSstat[ssid]
        stat.lock.acquire()
        # if seq leap too much, we consider it recycled
        if abs(rtpmsg.seq - stat.lastSeq) > 0xff:
            stat.lastCycle += 1
        stat.lastSeq = rtpmsg.seq
        stat.pktCnt += 1
        stat.bytCnt += len(rtpmsg.pload)
        stat.lastRtpTs = rtpmsg.tmStmp
        # !!!! to do: jitter calc
        stat.lock.release()
        # following are for debug purpose only
        if rtpmsg.seq%30 == 0:
            VTHDebugLogger.debug(rtpmsg.getPrtTxt())

    def rtpMsgOut(self):
        msg = ''
        self.oRtpMsg.seq = self.sstat.nextSeq()
        self.oRtpMsg.tmStmp = self.sstat.nextTs(self.rtpTsIncr)
        msg = self.oRtpMsg.enc()
        # drop rtp msg every 100
        if self.oRtpMsg.seq%100 == 0:
            VTHDebugLogger.debug("skip 1 rtp msg")
        else:
            self.rtpSock.sendto(msg, (self.optns['rmti'], self.optns['rmtp']))
        self.sstat.lock.acquire()
        if self.oRtpMsg.seq%30 == 0:
            VTHDebugLogger.debug(self.oRtpMsg.getPrtTxt())
        self.sstat.bytCnt += len(self.oRtpMsg.pload)
        self.sstat.pktCnt += 1
        self.sstat.lock.release()

    def rtcpMsgIn(self):
        msg = self.rtcpSock.recv(1024)
        rtcpmsg = CRtcpMsg(msg)
        VTHDebugLogger.debug(rtcpmsg.getPrtTxt())
        for pkt in rtcpmsg.pkts:
            if pkt.pktType == RTCP_PKT_TYPE.SR:
                if self.inSstat.has_key(pkt.ssid):
                    stat = self.inSstat[pkt.ssid]
                    stat.setAttrb('lastMsw', pkt.sdrInfo.ntpMsw)
                    stat.setAttrb('lastLsw', pkt.sdrInfo.ntpLsw)
            elif pkt.pktType == RTCP_PKT_TYPE.BYE:
                for itm in pkt.items:
                    if self.inSstat.has_key(itm.ssid):
                        self.inSstat.pop(itm.ssid)

    def rtcpMsgOut(self, msg=None):
        (msw, lsw) = getNtpTimestamp()
        if msg is None or not isinstance(msg, CRtcpMsg):
            msg = self.rtcpMsgTemp
            # update sender info
            sdrInfo = msg.pkts[0].sdrInfo
            sdrInfo.updBySrcStat(self.sstat)
            # compose receiption report blocks
            msg.pkts[0].items = []
            for stat in self.inSstat.values():
                rrblk = CRtcpRRBlk()
                rrblk.updBySrcStat(stat)
                msg.pkts[0].items.append(rrblk)
            (sdrInfo.ntpMsw, sdrInfo.ntpLsw) = (msw, lsw)
        raw = msg.enc()
        VTHDebugLogger.debug(msg.getPrtTxt())
        self.rtcpSock.sendto(raw, (self.optns['rmti'], self.optns['rmtp']+1))
        self.sstat.setAttrb('lastMsw', msw)
        self.sstat.setAttrb('lastLsw', lsw)

    def creSock(self):
        return socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def sendBye(self):
        msg = deepcopy(self.rtcpMsgTemp)
        byePkt = CRtcpPacket()
        ssrc = CRtcpSSID()
        byePkt.pktType = RTCP_PKT_TYPE.BYE
        ssrc.ssid = msg.pkts[0].ssid
        byePkt.items = [ssrc]
        msg.pkts.append(byePkt)
        self.rtcpMsgOut(msg)

    def sendAppdata(self):
        msg = deepcopy(self.rtcpMsgTemp)
        AppPkt = CRtcpPacket()
        app = CRtcpAppData()
        AppPkt.pktType = RTCP_PKT_TYPE.APP
        AppPkt.items = [app]
        msg.pkts.append(AppPkt)
        self.rtcpMsgOut(msg)

    def start(self):
        if self.rtpSock is None: self.rtpSock = self.creSock()
        self.rtpSock.bind((self.optns['loci'], self.optns['locp']))
        if self.rtcpSock is None: self.rtcpSock = self.creSock()
        self.rtcpSock.bind((self.optns['loci'], self.optns['locp']+1))
        self.__startRcv()
        if (not self.optns.has_key('ro')) or (not self.optns['ro']):
            self.__startSnd()

    def __startRcv(self):
        if self.__rcvRunning:
            VTHDebugLogger.debug('RTP/RTCP rcv already running')
            return
        # reinit if already started before
        if self.__rcvNeedReIn:
            self.__initRcv()
        self.rtpRcvThr.start()
        self.rtcpRcvThr.start()
        self.__rcvNeedReIn = True
        self.__rcvRunning = True

    def __startSnd(self):
        if self.__sndRunning:
            VTHDebugLogger.debug('RTP/RTCP snd already running')
            return
        # reinit if already started before
        if self.__sndNeedReIn:
            self.__initSnd(self.ssid)
        self.rtpSndThr.start()
        self.rtcpSndThr.start()
        self.__sndNeedReIn = True
        self.__sndRunning = True

    def stop(self):
        if self.__sndRunning:
            self.rtcpSndThr.reqStop()
            self.rtpSndThr.reqStop()
            self.rtcpSndThr.join()
            self.rtpSndThr.join()
            self.sendBye()
            self.__sndRunning = False
        if self.__rcvRunning:
            self.rtpRcvThr.reqStop()
            self.rtcpRcvThr.reqStop()
            self.rtpRcvThr.join()
            self.rtcpRcvThr.join()
            self.__rcvRunning = False
        self.rtpSock.close()
        self.rtcpSock.close()
        self.rtpSock = None
        self.rtcpSock = None

def codecRtcpTest():
    txt = '81c8000c832f3adeccafd4d0d60400000005da17000009280005' +\
          '8b90033151de00000000000042cd00000000d5f4ced90001d4fe' +\
          '81ca0007833c24ad01123931373530384031302e31382e31332e' +\
          '313100000000'
    msg = txt2hex(txt)
    sr1 = CRtcpMsg(msg)
    print '== decode rtcp ==\n' + txt + '\n== == =='
    print sr1.getPrtTxt()
    txt = '81c8000c832f3adeccafd5537b6400000015ccc7000022ac0015' +\
          'ab80833151de0000000000005c5100000000d676ced900027a5e' +\
          '81ca0007832f3ade01123931373530354031302e31382e31332e' +\
          '31310000000081cb0001832f3ade'
    msg = txt2hex(txt)
    sr1 = CRtcpMsg(msg)
    print '== decode rtcp ==\n' + txt + '\n== == =='
    print sr1.getPrtTxt()
    print '== encode def rtcp =='
    msg1 = CRtcpMsg()
    rr = CRtcpRRBlk()
    msg1.pkts[0].items.append(rr)
    sdi = CRtcpSDItem()
    sdi.sdType = RTCP_SDES_TYPE.CNAME
    sdi.txt = 'aaa@ss.dd.ss'
    sdc =  CRtcpSDChnk()
    sdc.ssid = 123
    sdc.items.append(sdi)
    msg1.pkts[1].items.append(sdc)
    print '== raw ==\n' + hexdump(msg1.enc()) + '\n== == =='
    print msg1.getPrtTxt()
    print '== encode rtcp app pkt =='
    msg = CRtcpMsg(pktLst=(RTCP_PKT_TYPE.APP,))
    app = CRtcpAppData()
    msg.pkts[0].items.append(app)
    print '== raw ==\n' + hexdump(msg.enc()) + '\n== == =='
    print msg.getPrtTxt()
    print '== encode rtcp bye pkt =='
    msg = CRtcpMsg(pktLst=(RTCP_PKT_TYPE.BYE,))
    ssrc = CRtcpSSID()
    ssrc.ssid = 345
    msg.pkts[0].items.append(ssrc)
    print '== raw ==\n' + hexdump(msg.enc()) + '\n== == =='
    print msg.getPrtTxt()
    print '== encode complicate rtcp pkt =='
    import copy
    msg = copy.deepcopy(msg1)
    msg.pkts[0].ssid = 456
    pkt1 = CRtcpPacket()
    pkt1.pktType = RTCP_PKT_TYPE.BYE
    ssrc.ssid = 666
    pkt1.items.append(ssrc)
    msg.pkts.append(pkt1)
    pkt2 = CRtcpPacket()
    pkt2.pktType = RTCP_PKT_TYPE.APP
    pkt2.items.append(app)
    msg.pkts.append(pkt2)
    print '== raw ==\n' + hexdump(msg.enc()) + '\n== == =='
    print msg.getPrtTxt()

def codecRtpTest():
    txt = '808020b700002107832f3ade'
    msg = txt2hex(txt) + ('\xff'*160)
    rtpmsg = CRtpMsg(msg)
    print '== decode rtp ==\n' + txt + '\n== == =='
    print rtpmsg.getPrtTxt()
    txt = '800020c2000027e7832f3ade'
    msg = txt2hex(txt) + ('\xff'*160)
    rtpmsg = CRtpMsg(msg)
    print '== decode rtp ==\n' + txt + '\n== == =='
    print rtpmsg.getPrtTxt()
    print "== creat and encode rtp =="
    rtpmsg = CRtpMsg(plen=16)
    rtpmsg.ptype = 2
    rtpmsg.seq = 5
    rtpmsg.ssid = 100
    print hexdump(rtpmsg.enc())
    print rtpmsg.getPrtTxt()


def testRTPSessn():
    rs = CRtpSession(random.randint(1,0xffffff))
    rs.start()
    time.sleep(2)
    rs.sendAppdata()
    rs.stop()
    rs.start()
    time.sleep(5)
    rs.stop()
    return

if __name__ == '__main__':
    try:
        #codecRtpTest()
        #codecRtcpTest()
        testRTPSessn()
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)
