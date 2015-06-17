#!/usr/local/bin/python
"""
VTH utilities functions
"""
from random import randint
import re
import socket
import struct
from subprocess import Popen, PIPE
from time import sleep, time

NTP_TIME_DIFF = 2209017600

def passFunc(*args):
    """passive function, do nothing"""
    pass

def randDelay(min, max):
    """delay random ms in range min and max"""
    sleep(randint(min,max)/1000.0)

def getNtpTimestamp(tm=None):
    """
    return 2 int (MSW, LSW) of NTP 64bit time stamp of give time or now
    """
    if tm is None:
        tm = time()
    msw = int(tm) + NTP_TIME_DIFF
    lsw = int((tm - int(tm)) * 0xffffffff)
    return (msw, lsw)

def ntpTimestamp2Time(msw, lsw):
    """
    return float time if given NTP 64bit time stamp
    """
    return (float(msw - NTP_TIME_DIFF) + (float(lsw)/0xffffffff))

def txt2hex(txt):
    """
    convert given hex text to hex string
    """
    hex = ''
    for i in range(0, len(txt), 2):
        hex += chr(int(txt[i:i+2], 16))
    return hex

def getTsharkIf():
    """
    get list interface name tshark can capture
    """
    ptn = re.compile('^(\d+)\.\W+(\w*)\W*.*')
    outpt = Popen(['tshark', '-D'], stdout=PIPE).stdout
    ifl = {}
    for line in outpt.readlines():
        print line
        try:
            (idx,ifn) = ptn.search(line).groups()
            ifl[idx] = ifn
        except:
            pass
    return ifl

def if2ip(ifname):
    import os
    if os.name != 'posix': return None
    import fcntl
    """
    return IP address of given interface name
    """
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        return inet_ntoa(fcntl.ioctl(s.fileno(), 0x8915, struct.pack('256s', ifname[:15]))[20:24])
    except:
        return None

def hexdump(data, llen=16):
    i = 0
    hexDump = '%04d\t'%i
    for c in data:
        hexDump += '%02x '%ord(c)
        i += 1
        if i%llen == 0:
            hexDump += '\n%04d\t'%i
    return hexDump

if __name__ == '__main__':
    print if2ip('lo')
    print if2ip('any')
    print getTsharkIf()

