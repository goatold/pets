#!/bin/bash
#k=$1;
#S=$2;
read k;
read S;
msl=$((${#S}/$k));
if [ $msl -eq 0 ]; then echo 0; exit; fi
mreg=
if [ $k -gt 2 ]; then
  r=`seq $((k-2))`;
  mreg=`printf '(\\\\1[a-z]*)%.0s' $r`;
fi

echo $S | sed -r 's/^([a-z]{1,'$sl'})[a-z]*'$mreg'\1$/\1/' | tr -d '\n' | wc -c
