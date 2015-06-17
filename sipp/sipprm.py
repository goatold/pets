#!/usr/local/bin/python

#workdir: ls -l /proc/pid#/cwd|cut -f2 -d">"
#process info: ps -C sipp -o pid,user,etime,args
import socket
from optparse import OptionParser
SIPP_CMD_MAN = """
sipp command table:
'+'\tincrease call rate by 1 * rate_scale
'-'\tdecrease call rate by 1 * rate_scale
'*'\tincrease call rate by 10 * rate_scale
'/'\tdecrease call rate by 10 * rate_scale
'p'\tpause the traffic
'q'\tquit sipp instance after all calls complete
'Q'\tquit sipp instance immediately
's'\tDump screens to the log file
\t<scenario_name>_<pid>_cenaris.log
\t(if -trace_screen is passed when start sipp)
"""

SIPP_ADV_CMD_MAN = """
advanced sipp command (add 'c' before following cmd):
dump tasks\tPrints a list of active tasks to the error log
set rate X\tSets the call rate
set rate-scale X\tSets the rate scale
set users X\tSets the number of users
set limit X\tSets the open call limit
set hide <true|false>\trespected the hide XML attribute?
set index <true|false>\tDisplay msg indexes in the scenario screen
set display <main|ooc>\tdisplay either the main or the out-of-call scenario
trace <log> <on|off>\tTurns log on or off at run time.
\tValid values for log are 'error', 'logs', 'messages', and 'shortmessages'
"""

def prsopt():
    usage = "usage: %prog [-i IP] [-p PORT] [-c|-r CMD]\n" + SIPP_CMD_MAN + SIPP_ADV_CMD_MAN
    parser = OptionParser(usage=usage)
    parser.set_defaults(ip='127.0.0.1', port=8888, cmd='q')
    parser.add_option("-i", "--ip",
                      action="store", dest="ip",
                      help="IP address to send command to", metavar="IP")
    parser.add_option("-p", "--port",
                      action="store", dest="port", type='int',
                      help="remote port number to send command to")
    parser.add_option("-c", "--cmd",
                      action="store", dest="cmd",
                      choices=('p', 'q', 'Q', 's', '*', '/', '+', '-'),
                      help="command string to send. ")
    parser.add_option("-r", "--rawcmd",
                      action="store", dest="cmd",
                      help="raw command string to send")

    return parser.parse_args()
def main():
    (options, args) = prsopt()
    sndCmd(options.ip, options.port, options.cmd)

def sndCmd(ip, port, cmd):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.sendto(cmd, (ip, port))
    sock.close()

if __name__ == "__main__":
    main()