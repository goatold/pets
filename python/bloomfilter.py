#!env python
"""
Exercise for http://codekata.com/kata/kata05-bloom-filters/
Implement Bloom filter
"""
from BitVector import BitVector
import math, glob, fileinput


def MurmurHash3_x86_32(key, seed):
  """
  murmur hash function
  """
  from struct import unpack
  klen= len(key)
  nblocks = klen/4
  h1 = seed

  c1 = 0xcc9e2d51
  c2 = 0x1b873593

  for i in range(nblocks):
    k1 = unpack("I", key[i*4:(i+1)*4])[0]

    k1 *= c1;
    k1 = (k1 << 15) | (k1 >> 17)
    k1 *= c2
    
    h1 ^= k1
    h1 = (h1<<13) | (h1 >> 19)
    h1 = h1*5+0xe6546b64

  #tail

  k1 = 0;
  for i in range(klen%4):
    ti = unpack("B", key[-1-i])[0]
    k1 ^= ti << 8*(2-i)
  k1 *= c1
  k1 = (k1<<16) | (k1 >> 16)
  k1 *= c2
  h1 ^= k1

  # finalization

  h1 ^= klen
  h1 ^= h1 >> 16;
  h1 *= 0x85ebca6b;
  h1 ^= h1 >> 13;
  h1 *= 0xc2b2ae35;
  h1 ^= h1 >> 16;
  return h1

class BloomFilter:
  """
  BloomFilter data structure is a Bitmap of m bits representing n elements
  To add elements to the map:
    For each given elements, apply k hash functions to get k positions in bitmap
    and set each position to 1
  To query elements in map or not:
    apply k hash functions on the given element
    to get k positions in bitmap. If all of the k position in bit map are 1,
    it's probably in the set. Otherwise it's definately NOT
  """
  KRange = (2,4,8,16)
  # const ln(2), ln(2)^2
  ln2 = 0.69314718056
  ln2p2 = 0.4804530139182014

  @staticmethod
  def calPFP(n, m, k):
    """
    calculate probability of false positive
    """
    return pow(1-math.exp(-k*(n+0.5)/(m-1)), k)
  
  @staticmethod
  def calBitLen(n, p):
    """
    calculate bit map size for given probability of false positive
    """
    m = int(-(n*math.log(p))/BloomFilter.ln2p2)
    # round up to 32 bits
    if m%32: m += (32-m%32)
    return m
  
  @staticmethod
  def calHash(n, m):
    """
    calculate optimal number of hash functions of give n,m
    """
    return int(m*BloomFilter.ln2/n)
  
  @staticmethod
  def sugg(n):
    """
    suggesting m and k for given number of element
    """
    print ("%s\t"*3)%("p", "m(bytes)", "ok")
    for p in (0.1, 0.01, 0.001, 0.0001, 0.00001):
      m=BloomFilter.calBitLen(n,p)
      ok=BloomFilter.calHash(n,m)
      print ("%.5f\t"+"%d\t"*2)%(p, m/8, ok)
      for k in BloomFilter.KRange:
        rp=BloomFilter.calPFP(n,m,k)
        print ("\t"*2+"%d\t%f")%(k, rp)
  
  def __init__(self, n, m, k=2):
    """
    initialize bloom bit map by given
      n: number of elements to hold, 
      m: number of bytes of the map, allow times of 4 bytes
      k: number of hash functions to use, allow {2,4,8,16}
    """
    # expecting to hold n elements
    self.n = n
    if m%4: m += (4-m%4)
    self.m = m*8
    print "bit map size set to %d (%d bytes)after round up to 32bits"%(self.m, self.m/8)
    self.bm = BitVector(size=self.m, intVal=0)
    if k in BloomFilter.KRange:
      self.k = k
    else:
      self.k = BloomFilter.KRange[-1]
      # round k to closest allowed value
      for i in range(len(BloomFilter.KRange)-1):
        if k < BloomFilter.KRange[i]:
          self.k = BloomFilter.KRange[i]
          break
        elif k < BloomFilter.KRange[1+i]:
          if (BloomFilter.KRange[+i]-k) >= k-BloomFilter.KRange[1+i]:
            self.k = BloomFilter.KRange[i]
          else:
            self.k = BloomFilter.KRange[i+1]
          break
    print "k set to %d after validation"%(self.k)
    p=BloomFilter.calPFP(self.n, self.m, self.k)
    print "false positive probability will be %f when filtering %d elements"%(p, self.n)
    #slice bitmap into k slices
    self.ms = self.m/self.k
    self.hashf = MurmurHash3_x86_32

  def add(self, el):
    """
    add element to bit map
      bit map are sliced by k
      calculate position via kth hash function in kth slice
      set it to 1
    """
    for i in range(self.k):
      self.bm[self.hashf(el,i)%self.ms + self.ms*i] = 1

  def query(self, el):
    """
    query whether element in set
    """
    for i in range(self.k):
      if self.bm[self.hashf(el,i)%self.ms + self.ms*i] == 0:
        return False
    return True

def loadfile(path, bf):
  files = glob.glob(path)
  for line in fileinput.input(files):
    bf.add(line)
  
def chkfile(path, bf):
  files = glob.glob(path)
  for line in fileinput.input(files):
    print line, bf.query(line)

if __name__ == '__main__':
  BloomFilter.sugg(479625)
  bf = BloomFilter(479625, 574656, 8)
  loadfile("linux.words", bf)
  chkfile("chk.txt", bf)
#  for seed in range(3):
#    for key in ("aaa","aab","aac","aa1"):
#      print MurmurHash3_x86_32(key, seed)

