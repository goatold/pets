#!env python
"""
Exercise for http://codekata.com/kata/kata04-data-munging/
"""
import re, glob, fileinput

# read data from text file and find the item with minimium value of given attributes
class DataStat:
  """
  load data into array of structure
  provide statistics of given raw data
  """
  
  def __init__(self, ptnStr=""):
    """
    initialize pattern if given
    """
    self.pat = re.compile(ptnStr)
    self.rawdata = list()
    
  def readData(self, path):
    """
    read raw from text file
    """
    files = glob.glob(path)
    for line in fileinput.input(files):
      m = self.pat.match(line)
      if m:
        self.rawdata.append(m.groups())

  def findMinDiff(self, fi, d1, d2):
    """
    in raw data, find minimium defereciation between field d1 and d2
    output corresponding field fi
    fi, d1 and d2 are to be given by field index in raw data.
    """
    mind = None
    output = None
    for d in self.rawdata:
      dif = abs(int(d[d1]) - int(d[d2]))
      if not mind or dif < mind:
        mind = dif
        output = d[fi]
    print output, mind

if __name__ == '__main__':
  ds = DataStat("\s+(\d+)\s+(\d+)\*?\s+(\d+)")
  ds.readData("weather.dat")
  ds.findMinDiff(0,1,2)
  print ds.rawdata
  ds = DataStat("\s+\d+\.\s+(\w+)\s+.+\s+(\d+)\s*-\s*(\d+)")
  ds.readData("football.dat")
  print ds.rawdata
  ds.findMinDiff(0,1,2)