# origination
# basic orig with 3pcc triggere
sipp 152.148.163.134:5660 -i 135.252.136.97 -p 5660 -mp 60000 -sf basic_orig_3pcc.xml -inf cinfo.csv -3pcc 135.252.136.97:50081
# 3pcc triggere
sipp 127.0.0.1:6666 -sf trigger.xml -3pcc 135.252.136.97:50081
# termination
sipp -i 152.148.163.134 -p 5660 -mp 60030 -sf basic_term.xml -inf cinfo.csv
