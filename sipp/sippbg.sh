sipp -sn uas -i 10.84.5.3 -p 5060 -mp 10000 -cp 8898 -bg;
sipp -sn uac -d 2s -r 2 -rp 1s -i 10.84.5.3 -p 5070 -mp 10020 10.84.5.207:51016 -s 5088022233 -trace_screen -cp 8902 -bg;
sipp -sn uac -d 5s -r 3 -rp 1s -i 10.84.5.3 -p 5080 -mp 10030 10.84.5.207:51016 -s 5088032233 -trace_screen -cp 8903 -bg;
sipp -sn uac -d 3s -r 3 -rp 1s -i 10.84.5.3 -p 5090 -mp 10040 10.84.5.207:51016 -s 5088042233 -trace_screen -cp 8904 -bg;
sipp -sn uac -d 5s -r 3 -rp 1s -i 10.84.5.3 -p 5100 -mp 10050 10.84.5.207:51016 -s 5088082233 -trace_screen -cp 8908 -bg;

python sipprm.py -i 10.84.5.3 -p 8902 -c s;
python sipprm.py -i 10.84.5.3 -p 8903 -c s;
python sipprm.py -i 10.84.5.3 -p 8904 -c s;
python sipprm.py -i 10.84.5.3 -p 8908 -c s;

python sipprm.py -i 10.84.5.3 -p 8898 -c q;
python sipprm.py -i 10.84.5.3 -p 8902 -c q;
python sipprm.py -i 10.84.5.3 -p 8903 -c q;
python sipprm.py -i 10.84.5.3 -p 8904 -c q;
python sipprm.py -i 10.84.5.3 -p 8908 -c q;
