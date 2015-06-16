#!/bin/bash

ptn="1df52500"
basedir=/var/local/nectar/crtest/bk
kfile="/usr/lib/debug/lib/modules/2.6.18-238.el5/vmlinux"
ksfile="$basedir/ks.kdb"
krfile="$basedir/kr.kdb"

log(){
  echo "`date`: $@";
}

# gen kdb file
cat << EOKF >$ksfile
search -k $ptn
quit
EOKF

# search patern in kernel mem
addrs=`crash -i $ksfile $kfile |grep $ptn|grep -v search | cut -f1 -d":"`
>$krfile;
for a in $addrs; do
  log "found match at 0x$a";
  a512=$((0x${a}%512));
  printf "rd 0x%x 1024\n" $((0x${a}-256-${a512})) >> $krfile;
done
if [ -s $krfile ]; then
  echo "quit">> $krfile;
  crash -i $krfile $kfile | egrep "^crash|^[a-f0-9]{8}:" ;
else
  log "no match found!";
fi
