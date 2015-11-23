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
	[[ -n "$1" ]] && port=$1 || {
		select p in $(pgrep -f -l 'ssh.*-D' | sed -e 's/.*\-D \+\([0-9]\+\).*/\1/'); do
			port=$p;
			break;
		done
	}
	[[ -n "$2" ]] && dst=$2 || {
		select d in ${desth[@]}; do
			dst=$d;
			break;
		done
	}
	[[ "$withscr" ]] && [[ "$withscr" =~ y|Y ]] && { scr="screen -t '$port::$dst' --"; } || { scr=; }
	echo "connecting to $dst via $port $scr"
	$scr ssh -o ProxyCommand="'nc -x 127.0.0.1:$port %h %p'" $dst
}
