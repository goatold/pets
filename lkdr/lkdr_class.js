//Hash class
function arrHash()
{
  this.length = 0;
  this.items = new Array();
  for (var i = 0; i < arguments.length; i += 2) {
    if (typeof(arguments[i + 1]) != 'undefined') {
      this.items[arguments[i]] = arguments[i + 1];
      this.length++;
    }
  }

  this.removeItem = function(in_key)
  {
    var tmp_previous;
    if (typeof(this.items[in_key]) != 'undefined') {
      this.length--;
      var tmp_previous = this.items[in_key];
      delete this.items[in_key];
    }

    return tmp_previous;
  }

  this.getItem = function(in_key) {
    return this.items[in_key];
  }

  this.setItem = function(in_key, in_value)
  {
    var tmp_previous;
    if (typeof(in_value) != 'undefined') {
      if (typeof(this.items[in_key]) == 'undefined') {
        this.length++;
      }
      else {
        tmp_previous = this.items[in_key];
      }

      this.items[in_key] = in_value;
    }

    return tmp_previous;
  }

  this.hasItem = function(in_key)
  {
    return typeof(this.items[in_key]) != 'undefined';
  }

  this.copy = function(){
    n = new arrHash();
    for (var i in this.items) {
      n.setItem(i, this.getItem(i));
    }
    return n;
  }

  this.clear = function()
  {
    for (var i in this.items) {
      delete this.items[i];
    }

    this.length = 0;
  }
}

// Class prize Rank
function PrizeRank(batch, excl, lim) {
  this.batch = batch;
  this.excl = excl;
  this.lim = lim;
}

// Class Luckdraw
function Luckdraw() {
  this.drpool = new arrHash();
  this.tmpl = [];
  this.prizeLst = new arrHash();
  this.winLst = new arrHash();
  var ForReading = 1, ForWriting = 2, ForAppending = 8;

  // method initialize draw pool as sequencial INT
  this.initPoolInt = function(n) {
    this.drpool.clear();
    for (i=1;i<=n;i++) {
      this.drpool.setItem(i, "v_" + i);
    }
  }

  // method load prize list from file
  this.loadPrizeFile = function(fname) {
    var fso  = new ActiveXObject("Scripting.FileSystemObject");
    f = fso.OpenTextFile(getPwd()+fname, ForReading);
    this.prizeLst.clear();

    // Read from the file and display the results.
    while (!f.AtEndOfStream)
    {
        var r = f.ReadLine();
        var l = r.match(/^\s*(.+)\s*\|\s*(\d+)\s*\|\s*([yYnN])\s*\|\s*(\d+)\s*$/);
        if (typeof(l[1]) != 'undefined') {
          var l, k, lm, ec, b;
          k = l[1];
          if (typeof(l[2]) != 'undefined' && parseInt(l[2]) != 'NaN') {
            lm = parseInt(l[2]);
          }else{
            lm = 0;
          }
          if (typeof(l[3]) != 'undefined') {
            ec = !/[nN]/.test(l[3]);
          }
          if (typeof(l[4]) != 'undefined' && parseInt(l[4]) != 'NaN') {
            b = parseInt(l[4]);
          }else{
            b = 1;
          }
          var pr = new PrizeRank(b, ec, lm);
          this.prizeLst.setItem(k, pr);
          if (!this.winLst.hasItem(k)) {
            var l = [];
            this.winLst.setItem(k, l);
          }
        }else{
          continue;
        }
    }
    f.Close();
    for (var i in this.winLst.items) {
      if (!this.prizeLst.hasItem(i)) {
        var c = confirm("Prize: " + i + " no longer exists. Clear winner list of it?");
        if (c) {
          this.winLst.removeItem(i);
          alert("winner list of " + i + " cleared!");
        }
      }
    }
  }

  this.dumpPrize = function(dsp) {
    var htm = "";
    for (var i in this.prizeLst.items) {
      htm += i + "::[" + this.prizeLst.getItem(i).lim + "],[";
      htm += this.prizeLst.getItem(i).excl + "],[";
      htm += this.prizeLst.getItem(i).batch + "]<br>";
    }
    if (htm == "") htm = "no prize!";
    document.getElementById(dsp).innerHTML = htm;
  }

  // method load winner list from file
  this.loadWinFile = function(fname) {
    var fso  = new ActiveXObject("Scripting.FileSystemObject");
    f = fso.OpenTextFile(getPwd()+fname, ForReading);
    // clear current winner list first
    this.clearWin();

    // Read from the file
    while (!f.AtEndOfStream)
    {
      var r = f.ReadLine();
      var l, p, k, v;
      l = r.replace(/(^\s*)|(\s*$)/g, "");
      if (l.indexOf("#") == 0) {
        p = l.replace(/(^#+\s*)|(\s*$)/g, "");
      }else{
        l = r.split("|",2);
        if (typeof(l[0]) != 'undefined' && typeof(l[1]) != 'undefined') {
          // trim item
          k = l[0].replace(/(^\s*)|(\s*$)/g, "");
          v = l[1].replace(/(^\s*)|(\s*$)/g, "");
          if (k != "" && v != "" && this.prizeLst.hasItem(p)) {
            if (!(this.winLst.hasItem(p))) {
              w = [];
              this.winLst.setItem(p, w);
            }
            this.winLst.getItem(p).push(k);
          }
        }
      }
    }
    f.Close();
  }

  // clear winner list
  this.clearWin = function(pr){
    if (pr == null) {
      for (var p in this.winLst.items) {
        this.winLst.setItem(p, []);
      }
    }else{
      this.winLst.setItem(pr, []);
    }
  }

  this.addWin = function(p, l){
    if (!this.prizeLst.hasItem(p)) return;
    if (!this.winLst.hasItem(p)) {
      var w = [];
      this.winLst.setItem(p, w);
    }
    for (var i=0; i<l.length; i++) {
      this.winLst.getItem(p).push(l[i]);
    }
  }

  // method save winner list to file
  this.saveWinFile = function(fn) {
    var fso  = new ActiveXObject("Scripting.FileSystemObject");
    var fh = fso.CreateTextFile(getPwd()+fn, true);
    for (var p in this.prizeLst.items) {
      fh.WriteLine("#" + p);
      var w = this.winLst.getItem(p);
      if (typeof(w) == 'undefined' || w.length == 0) continue;
      for (var i=0;i<w.length;i++) {
        fh.WriteLine(w[i] + "|" + this.drpool.getItem(w[i]));
      }
    }
    fh.Close();
  }

  this.dspWin = function(dsp, pr) {
    var htm = "";
    if (pr == null) {
      for (var p in this.prizeLst.items) {
        var wl = "";
        var w = this.winLst.getItem(p);
        if (typeof(w) == 'undefined') continue;
        for (var i=0;i<w.length;i++) {
          wl += "<li>" + w[i] + ":" + this.drpool.getItem(w[i]) + "</li>";
        }
        if (wl == "") wl = " no winner!";
        htm += "<ul>" + p + wl + "</ul>";
      }
    }else{
      var wl = "";
      var w = this.winLst.getItem(pr);
      if (typeof(w) != 'undefined' && w.length != 0) {
        for (var i=0;i<w.length;i++) {
          wl += "<li>" + w[i] + ":" + this.drpool.getItem(w[i]) + "</li>";
        }
      }
      if (wl == "") wl = " no winner!";
      htm = "<ul>" + pr + wl + "</ul>";
    }
    document.getElementById(dsp).innerHTML = htm;
  }

  // method load draw pool from file
  this.loadPoolFile = function(fname) {
    var fso  = new ActiveXObject("Scripting.FileSystemObject");
    f = fso.OpenTextFile(getPwd()+fname, ForReading);
    this.drpool.clear();

    // Read from the file and display the results.
    while (!f.AtEndOfStream)
    {
        var r = f.ReadLine();
        var l, k, v;
        l = r.split("|",2);
        if (typeof(l[0]) != 'undefined' && typeof(l[1]) != 'undefined') {
          // trim item
          k = l[0].replace(/(^\s*)|(\s*$)/g, "");
          v = l[1].replace(/(^\s*)|(\s*$)/g, "");
          if (k != "" && v != "") {
            this.drpool.setItem(k, v);
          }
        }
    }
    f.Close();
  }

  this.dumpPool = function(dsp) {
    var htm = "";
    for (var i in this.drpool.items) {
      htm += i + "::" + this.drpool.getItem(i) + "<br>";
    }
    if (htm == "") htm = "empty pool!";
    document.getElementById(dsp).innerHTML = htm;
  }

  this.validDraw = function(p, n) {
    if (n <= 0) return false;
    try{
      if (this.prizeLst.getItem(p).lim > 0){
        var l = this.prizeLst.getItem(p).lim - this.winLst.getItem(p).length;
        if (n > l) {
          alert(l + ' ' + p + ' left. Cannot draw ' + n + ' more!');
          return false;
        }
      }
      var l = this.drpool.length - this.winLst.getItem(p).length;
      if (this.prizeLst.getItem(p).excl) {
        for(var i in this.winLst.items) {
          if (i == p) continue;
          if (this.prizeLst.getItem(i).excl) {
            l -= this.winLst.getItem(i).length;
          }
        }
      }
      if (l <= 0) {
        alert('no candidate left!');
        return false;
      }
      if (n > l) {
        alert('not enough candidates! ' + l + ' available');
        return false;
      }
      return true;
    } catch(err){
      alert('valid draw except:' + err.description);
      return false;
    }
  }

  // get list of keys from draw pool exclude keys in win list
  this.getPool = function(p) {
    var tmpl = this.drpool.copy();
    var w = this.winLst.getItem(p);
    if (typeof(w) != 'undefined' && w.length != 0) {
      for (var i=0;i<w.length;i++) {
        tmpl.removeItem(w[i]);
      }
    }
    if (this.prizeLst.getItem(p).excl) {
      for (var i in this.winLst.items) {
        if (i == p) continue;
        if (this.prizeLst.getItem(i).excl) {
          w = this.winLst.getItem(i);
          if (typeof(w) == 'undefined' || w.length == 0) continue;
          for (var j =0;j<w.length;j++) {
            tmpl.removeItem(w[j]);
          }
        }
      }
    }
    var r = [];
    for (var i in tmpl.items) {
      r.push(i+"");
    }
    return r;
  }

  this.draw = function(p, n) {
    if (!this.validDraw(p, n)) return [];
    var pl = this.getPool(p);
    var wl = [];
    if (n == pl.length) {
      return pl;
    }else{
      for (var i=0;i<n;i++) {
        var randIdx = (Math.floor(Math.random()*pl.length));
        wl.push(pl[randIdx]);
        pl.splice(randIdx, 1);
      }
    }
    return wl;
  }
}

function getPwd() {
  var lp = location.pathname.replace(/^[\/\\]*(.+)[\/\\].*$/g,"$1");
  lp = lp.replace(/[\/\\]/g, "\\\\");
  return lp+"\\\\";
}