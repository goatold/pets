#!/bin/env python3
import calendar
import datetime
import pick
import readline
import sqlite3
from pprint import pprint

db = 'teame.db'

def getLastEvents(cur, n):
    sql = ('select date,firstname,lastname,place from Events,Members '
           'where Events.coordinator=Members.email order by date desc limit %u;')%(n)
    cur.execute(sql)
    return [dict(zip(r.keys(),r)) for r in cur]

def getPlaces(cur, n):
    sql = ('select place,count(name) as visits,max(date) as lastdate,address,info from Events,Places '
           'where Places.name=Events.place group by Events.place order by lastdate')
    if (n > 0):
        sql += ' limit %u'%(n)
    cur.execute(sql)
    return [dict(zip(r.keys(),r)) for r in cur]

def getNextCoordinators(cur, n, orderby, v):
    sql = 'select firstname,lastname,email from Members '
    clause = 'where %s > "%s" order by %s'%(orderby, v, orderby)
    if (n > 0):
        clause += ' limit %u'%(n)
    cur.execute(sql + clause)
    cl = [dict(zip(r.keys(),r)) for r in cur]
    c = len(cl)
    if (c < n or n == 0):
        if (n == 0):
            clause = 'where %s <= "%s" order by %s'%(orderby, v, orderby)
        else:
            clause = 'order by %s limit %u;'%(orderby, n - c)
        cur.execute(sql + clause)
        cl += [dict(zip(r.keys(),r)) for r in cur]
    return cl

def nextWeekday(d, wd):
    delta = wd - d.weekday()
    if (delta >= 0):
        return d + datetime.timedelta(delta)
    else:
        return d + datetime.timedelta(7+delta)

def getNextDates(n, wd):
    td = datetime.date.today()
    nd = nextWeekday(td.replace(day=1), wd)
    dl = []
    if (nd > td):
        dl.append(nd)
        n -= 1
    while (n > 0):
        if (nd.month == 12):
            nd = nd.replace(year=nd.year+1, month=1)
        else:
            nd = nd.replace(month=nd.month+1)
        nd = nextWeekday(nd.replace(day=1), wd)
        dl.append(nd)
        n -= 1
    return dl

def addPlace(cur, name, address, info):
    sql = 'insert into Places values("%s","%s","%s")'%(name, address, info)
    cur.execute(sql)

def addEvent(cur, date, coord, place):
    sql = 'insert into Events values("%s","%s","%s")'%(date, coord, place)
    cur.execute(sql)

def inputWithDefV(prompt, defV=''):
   readline.set_startup_hook(lambda: readline.insert_text(defV))
   try:
      return input(prompt)
   finally:
      readline.set_startup_hook()

def validate(date_text):
    try:
        datetime.datetime.strptime(date_text, '%Y-%m-%d')
    except ValueError:
        raise ValueError("Incorrect data format, should be YYYY-MM-DD")

conn = sqlite3.connect(db, detect_types=sqlite3.PARSE_DECLTYPES)
cur = conn.cursor()
cur.row_factory = sqlite3.Row

el = getLastEvents(cur, 3)
el.sort(key = lambda e:e['date'])
print("previous events\n====")
for e in el:
    print("%s:%s %s: %s"%(e['date'], e['firstname'], e['lastname'], e['place']))

cl = getNextCoordinators(cur, 0, 'lastname', el[-1]['lastname']) 
dl = getNextDates(3, calendar.THURSDAY)
print("comming events\n====")
for i in range(min(len(cl), len(dl))):
    print("%s: %s %s"%(dl[i], cl[i]['firstname'], cl[i]['lastname']))

addNew = input("Add new event?(Y/N)")
if (addNew == '' or (addNew[0] != 'n' and addNew[0] != 'N')):
    picked = pick.pick([" ".join((c['firstname'], c['lastname'])) for c in cl], "choos coordinator")
    coord = cl[picked[1]]['email']
    nonPicked = lambda p: ('', -1)
    picker = pick.Picker(dl, "choose date(press n for new date)")
    picker.register_custom_handler(ord('n'), nonPicked)
    date, idx = picker.start()
    if (idx == -1):
        date = inputWithDefV("date of event:", dl[0].isoformat())
        validate(date)
    picker = pick.Picker([p['place'] for p in getPlaces(cur, 0)], "choose date(press n for new place)")
    picker.register_custom_handler(ord('n'), nonPicked)
    place, idx = picker.start()
    if (idx == -1):
        place = input("name of new place:")
        address = input("address:")
        info = input("info:")
        print("new place: '%s'\n%s\n%s"%(place, address, info))
        addPlace(cur, place, address, info)
    addEvent(cur, date, coord, place)
    print("new event added: (%s) %s: %s"%(date, cl[picked[1]]['firstname'], place))
    conn.commit()

conn.close()
