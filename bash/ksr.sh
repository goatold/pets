#!/bin/bash


kfile="/usr/lib/debug/lib/modules/2.6.18-238.el5/vmlinux"
basedir=/var/local/nectar/crtest/bk
ksout=$basedir/ks.out
kslog=$basedir/ks.log
krlog=$basedir/kr.log
krfile="$basedir/ksr.kdb"
addrbuf=""

log(){
  echo -e "`date \"+%m%d%H%M%S\"`: $@";
}

usage(){
echo "Usage: $0 -<s|r> path/2/file
# crash /usr/lib/debug/lib/modules/2.6.18-238.el5/vmlinux
# repeat search -k 1df52500 >> /var/local/nectar/crtest/bk/ks.out
 tailing result of 'search -k' from file per line
 -s log the time and matching addr
 -r read 1024 bytes from (addr&0xffffff00)-256
 default file is $ksout
";
}

procl(){
  cpid=`ps -C crash --no-headers -o pid|head -1|tr -d " "`;
  if [ ! -z $cpid ]; then
    log "exit with ps: `ps --no-headers -opid,args -p $cpid`";
    cpid="--pid=$cpid";
  fi
  log "read addr from: $ksout";
  tail --follow=name --retry $cpid $ksout | while read l; do
    a=`echo $l|cut -d":" -f1`;
    $func $a;
  done
}

kread(){
  if [[ "$addrbuf" == *"$1"* ]]; then
    log "repeat addr $1" >> $krlog;
  else
    log "raddr $1">>$krlog;
    raddr=`printf "0x%x" $(((0x$1&0xffffff00)-256))`;
    echo -e "rd ${raddr} 1024\nkmem $1\nquit\n" > $krfile;
    crash -i $krfile $kfile | egrep -A 10 "^crash>|^[a-f0-9]{8}:">>$krlog;    addrbuf="$addrbuf$1 ";
    if [ ${#addrbuf} -gt 90 ]; then
      # delete the first addr
      addrbuf=${addrbuf#* }
    fi
  fi
}

logaddr(){
  log "$a">>$kslog;
}

if [ $# -ge 1 ]; then
  if [ "$1" == "-s" ]; then
    func='logaddr';
  elif [ "$1" == "-r" ]; then
    func='kread';
  fi
fi

if [ ! -z "$2" -a -f "$2" ]; then
  ksout="$2";
fi

if [ -z "$func" -o ! -f "$ksout" ]; then
    usage;
    exit 0;
fi

procl;

exit 0;
