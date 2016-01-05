declare -a tunnels
tunnels=(\
"8023 test@135.242.80.23" \
"8076 test@172.24.178.76" \
"8138 test@172.24.179.138" \
"8254 test@172.24.178.254" \
"8199 test@172.24.178.199"
)

declare -A desth
desth=(\
[omcp1]=root@172.17.3.30 \
[omcp2]=root@172.17.3.40 \
[tp1]=root@172.17.3.130 \
[tp2]=root@172.17.3.110 \
[ccp14]=root@172.17.3.140 \
)
rcmds=( ssh sftp scp )

lstun() {
	tl=$(pgrep -f -l 'ssh.*-D' | sed -e 's/.*\-D \+\([0-9]\+\).* \(.\+@[0-9\.]\+\)/\1 \2/')
	printf "%s\n" "${tl[@]:-No tunnel found}"
}

seltun() {
	echo "select tunnel to setup"
	select t in "${tunnels[@]}"; do
		setuptun $t;
		break;
	done
}

setuptun() {
	ssh -f -N -D $1 $2
}

conndst() {
	[[ -n "$1" ]] && { cmd=$1; shift; } || {
		echo "Command:"
		select cmd in ${rcmds[@]}; { break; };
	}
	[[ -n "$1" ]] && { port=$1; shift; } || {
		echo "Proxy port:"
		select port in $(pgrep -f -l 'ssh.*-D' | sed -e 's/.*\-D \+\([0-9]\+\).*/\1/'); { break; };
	}

	[[ -n "$1" ]] && { rmh=$1; shift; } || {
		echo "Remote host:"
		select rmh in ${desth[@]}; { break; };
	}

	if [[ $cmd = scp ]]; then
		echo -n "copy from: ";
		read from;
		echo -n "to: ";
		read to;
		cpdirs=( "${rmh}:${from} ${to}" "$from ${rmh}:${to}" )
		echo -n "direction: ";
		select fromto in "${cpdirs[@]}"; { break; };
		echo "scp -o ProxyCommand='nc -x 127.0.0.1:$port %h %p' ${fromto}"
		scp -o ProxyCommand="nc -x 127.0.0.1:$port %h %p" ${fromto}
	else
# screen action according to env variable: $STY Alternate socket name.
# If screen is invoked, and the environment variable STY is set, then it
# creates only a window in the running screen session rather than starting a new session. 
        [[ -n $STY ]] && { scr="screen -t '$port::$rmh' --"; } || { scr=; }
		echo "$scr $cmd -o ProxyCommand='nc -x 127.0.0.1:$port %h %p' $rmh $@"
		$scr $cmd -o ProxyCommand="nc -x 127.0.0.1:$port %h %p" $rmh $@
	fi
}

