#!/bin/env python
import calendar
import datetime
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
           'where Places.name=Events.place group by Events.place order by lastdate limit %u;')%(n)
    cur.execute(sql)
    return [dict(zip(r.keys(),r)) for r in cur]

def getNextCoordinators(cur, n, orderby, v):
    sql = 'select firstname,lastname from Members '
    clause = 'where %s > "%s" order by %s limit %u;'%(orderby, v, orderby, n)
    cur.execute(sql + clause)
    cl = [dict(zip(r.keys(),r)) for r in cur]
    c = len(cl)
    if (c < n):
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

conn = sqlite3.connect(db, detect_types=sqlite3.PARSE_DECLTYPES)
cur = conn.cursor()
cur.row_factory = sqlite3.Row

el = getLastEvents(cur, 3)
el.sort(key = lambda e:e['date'])
pprint(el)
cl = getNextCoordinators(cur, 3, 'lastname', el[-1]['lastname']) 
dl = getNextDates(3, calendar.THURSDAY)
for i in range(min(len(cl), len(dl))):
    pprint((dl[i], cl[i]['firstname'], cl[i]['lastname']))

pprint(getPlaces(cur, 4))

conn.close()
