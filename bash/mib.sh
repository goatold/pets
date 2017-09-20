#!/bin/bash
MibPath=~/.snmp/mibs/*
declare -A MibTableIndexex=()
searchMibTableIndex() {
    # specify table to tmnxIpTunnelEntry if not given
    tableName=${1:-tmnxIpTunnelEntry}
    echo "searching mib table definition in mib files ${MibPath}"
    echo "given table '${tableName}'"

    # the first sed cmd get lines between "${tableName} OBJECT-TYPE" and "::=" then get lines between "INDEX" and "}"
    # therefore get the Index definition part of given table
    # this script cannot handle the situation when the MIB object definition are squeezed into a single line,
    # fortunately we all current definitions are cross multiple lines
    # tr cmd replace linebreaks and "," with whitespace
    # the second sed cmd get the index sequence between first pair of '{' and '}'
    MibTableIndexex[${tableName}]=$(sed -n -e '/'"${tableName}"'[[:space:]]\+OBJECT-TYPE/,/::=/ {/INDEX/,/\}/ p}' ${MibPath} |\
     tr '\n,' ' ' |\
     sed 's/[^{]*{\([^}]*\)}.*/\1/')
    echo -e "index of table ${tableName}:\e[4m"
    for i in ${MibTableIndexex[${tableName}]}; do
	    echo $i
    done
    echo -e "\e[24m"
}

searchMibChildTable() {
    tableName=${1:-tmnxIpTunnelEntry}
    searchMibTableIndex ${tableName}
    echo -e "searching child table of \e[1m'${tableName}'\e[21m in mib files ${MibPath}"

    # child table will inherit exact index sequence for parent table and extend new ones
    # compose the search pattern from parent table index
    indexSearchPattern='.*OBJECT-TYPE[^{]*INDEX\s*{'
    #pcregrep -M -H -n --color '.*OBJECT-TYPE[^{]*INDEX\s*{\s*svcId[,\s\n]*sapPortId[,\s\n]*sapEncapValue[,\s\n]*tmnxIpTunnelName'
    for i in ${MibTableIndexex[${tableName}]}; do
        indexSearchPattern="${indexSearchPattern}"'[\s\n]*'"$i"'[\s\n]*,'
    done
    indexSearchPattern="${indexSearchPattern}"'[^}]*'
    echo "search patern '${indexSearchPattern}'"
    pcregrep -M -H -n --color "${indexSearchPattern}" ${MibPath}
    if [[ $? -ne 0 ]]; then
        echo -e "\e[7mNo match found\e[27m"
    fi
}

# searchMibChildTable
