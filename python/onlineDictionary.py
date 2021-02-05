
import requests
import hashlib
import random
import json

def qGlosbeTrans(q):
    result = q
    qParams = {
        'from':     'eng',
        'dest':     'zho',
        'tm':       'true',
        'format':   'json',
    }
    myurl = 'https://glosbe.com/gapi/translate'

    qParams['phrase'] = q

    try:
        r = requests.get(myurl, params=qParams)
        print(r.url)
        if (r.status_code == 200):
            print(r.json())

    except Exception as e:
        print(e)

    finally:
        return result

#百度通用翻译API,不包含词典、tts语音合成等资源
# coding=utf-8
cfgFile = 'd:\\data\\baiduApiConfig.json'
def qBaiduTrans(q):
    config = {'appId': '填写你的appid', 'appKey': '填写你的密钥'}
    with open(cfgFile, 'r') as f:
        config = json.load(f)
    myAppid = config['appId']
    myAppKey = config['appKey']
    result = q
    qParams = {
        'appid':    myAppid,
        'from':     'auto',
        'to':       'zh',
    }
    myurl = 'https://api.fanyi.baidu.com/api/trans/vip/translate'

    qParams['q'] = q
    salt = str(random.randint(32768, 65536))
    qParams['salt'] = salt
    sign = myAppid + q + salt + myAppKey
    qParams['sign'] = hashlib.md5(sign.encode()).hexdigest()

    try:
        r = requests.get(myurl, params=qParams)
        #print(r.url)
        #print(r.json())
        if (r.status_code == 200):
            result = r.json()['trans_result'][0]['dst']

    except Exception as e:
        print(e)

    finally:
        return result

#print(qGlosbeTrans('orange'))
print(qBaiduTrans('alone'))
