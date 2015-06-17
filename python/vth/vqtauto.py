#!c:/Python26/python

# to do
# - batching tshark task
# - get option from cmd
# - config file
# - use dict to store port,rtcp pkg lst
# - OO

import subprocess
import string,re
import sys,getopt
from string import Template

plist = []
plist_rtp = []
pkmap = {}
options={'pcap_file':'rtp_rx.pcap'};
cmdGetPort = Template('tshark -r $pcap_file -T fields -e udp.srcport -R \"udp\"')
cmdGetPCount = Template('tshark -dudp.port==$rtcpPort,rtcp -R \"udp.srcport==$rtcpPort\" -r $pcap_file -T fields -e frame.time -e frame.number -e rtcp.sender.packetcount')
cmdCountP = Template('tshark -dudp.port==$rtpPort,rtp -r $pcap_file -q -z io,phs,\"udp.srcport==$rtpPort&&frame.number>$sfn&&frame.number<$efn\"')

def uAdd(lst, e):
	if e not in lst:
		lst.append(e)

def readPort():
	p = subprocess.Popen(cmdGetPort.safe_substitute(options), shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
	for line in p.stdout.readlines():
		aPort=int(line);
		# chech range:if aPort
		uAdd(plist, aPort)
	retval = p.wait()
	for aPort in plist:
		if aPort%2 == 0 and (aPort+1) in plist:
			uAdd(plist_rtp, aPort)
	#for aPort in plist_rtp:
	#rtcpps=

def GetPCount(rtcpP, pkList):
	p = subprocess.Popen(cmdGetPCount.safe_substitute(options, rtcpPort=rtcpP), shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
	for line in p.stdout.readlines():
		pkList.append(line.strip().split("\t"))
	retval = p.wait()

def chkCount(rtpP, pk1, pk2):
	p = subprocess.Popen(cmdCountP.safe_substitute(options, rtpPort=rtpP, sfn=pk1[1], efn=pk2[1]), shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
	rtpcnt = 0;
	for line in p.stdout.readlines():
		if ((string.find(line, "rtp") >= 0) and (string.find(line, "frames:") >= 0)):
			rtpcnt = int((line.split("frames:")[-1]).split()[0])
			break
	rtcpcnt = int(pk2[2]) - int(pk1[2])
	print "time: " + pk1[0] + " ~ " + pk2[0]
	if rtcpcnt == rtpcnt:
		rslt = " eq"
	else:
		rslt = " NOT eq"
	print "rtcp.sender.packetcount: " + pk2[2] + "-" + pk1[2] + "=" + str(rtcpcnt) + rslt + " rtp count: " + str(rtpcnt)
	retval = p.wait()


if __name__ == '__main__':
    try:
        readPort()
        for aPort in plist_rtp:
        	print "RTP Port: " + str(aPort)
        	print "======================="
        	rtcpPks = []
        	GetPCount(aPort+1, rtcpPks)
        	for indx in range(len(rtcpPks)-1):
        		chkCount(aPort, rtcpPks[indx], rtcpPks[indx+1])
        	break
        sys.exit(0)
    except KeyboardInterrupt:
        sys.exit(1)

