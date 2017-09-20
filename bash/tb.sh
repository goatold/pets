#!/bin/bash
paramf=/usr/local/timostools/regress.params
tb=kan2privvm38
phyTop=flexIpSec
subTop=sharedFabric
topoDir=/users/$USER/topology
homeTopoDir=~/netshare/$USER/topology
varPrefixSubTp=ALLOW_SUBTOPOS_7750_

getTbParam() {
    local tbName=${1:-$tb}
    local param=$2
    awk '/^'"$tbName"'\ +'"$param"'\ +/{if($1=="'"$tbName"'" && $2=="'"$param"'"){$1=$2="";print $0}}' $paramf
}

getTbAllowPhyTop() {
    local tbName=${1:-$tb}
    getTbParam "$tbName" "ALLOW_PHYSTOPOS"
    for tbPrf in $(getTbParam $tbName "PROFILE");
    do
        getTbPram $tbPrf "ALLOW_PHYSTOPOS_SR_7750"
    done
}

getTbPhyTop() {
    local tbName=${1:-$tb}
    getTbParam "$tbName" "PHYSTOPO"
    for tbPrf in $(getTbParam $tbName "PROFILE");
    do
        getTbPram $tbPrf "PHYSTOPO"
    done
}

getTbSubTop() {
    local tbName=${1:-$tb}
    if [[ -z $2 ]];
    then
        getTbParam "$tbName" "SUBTOPO";
    else
        local phyTop=$2
        local subParam="${varPrefixSubTp}${phyTop}"
        getTbParam "$tbName" "$subParam"
        for tbPrf in $(getTbParam $tbName "PROFILE");
        do
            getTbPram $tbPrf "$subParam"
        done
    fi
}

searchSubTp() {
    local subtp=${1// /}
    [[ -z $subtp ]] && return
    awk '/'"$varPrefixSubTp"'.+'"$subtp"'/ {sub("'"$varPrefixSubTp"'", "", $2);print $1"/"$2}' $paramf|sort -u
}

searchTbByProf() {
    local prof=${1// /}
    [[ -z $prof ]] && return
    awk '/^[[:alnum:]]+ +PROFILE\ +.+'"$prof"'/ {sub(/[0-9]+$/,".*",$1);print $1}' $paramf|sort -u
}

searchTbBySubTp() {
    local subtp=${1// /}
    (
    PS3="profile & physical topology?"
    select prof in $(searchSubTp $subtp); do prof=${prof%%/*};break; done
    searchTbByProf $prof
    )
}

ut() {
    local tbName=${1:-$tb}
    (
    PS3="physical topology?"
    select phyTp in $(getTbAllowPhyTop $tbName); do break; done
    phyTp=${phyTp:-$phyTop}
    PS3="sub topology?"
    select subTp in $(getTbSubTop $tbName $phyTp) $(for d in ${homeTopoDir}/*/;do [[ -d $d  ]] && echo "${topoDir}/$(basename $d)"; done); do break; done
    subTp=${subTp:-$subTop}
    echo "regress -nobuild -forcePause unitTest -runSuite Sanity -physTopology $phyTp -subTopology $subTp -testBed $tbName"
    )
}
