#!/bin/env python
import math
import sys

def getDivisors(n):
    if (n == 1):
        return [1];
    if (n == 2):
        return [1, 2];
    r1 = list([1]) # lower half of divisors
    r2 = list([n]) # higher half of divisors
    # from 2 to sqrt(n) inclusive
    for i in range(2, int(math.sqrt(n))+1):
        if (n % i == 0):
            r1.append(i)
            j = n/i
            if (i != j):
                r2.insert(0, j)
    return r1 + r2

if __name__ == '__main__':
    n = int(sys.argv[1])
    l = list()
    print "input %u\n"%(n)
    for i in range(1, n+1):
        r = getDivisors(i)
        print "%u: %s\n"%(i, ', '.join(map(str, r)))
        if (len(r) % 2 == 1):
            l.append(r)
    print "Done!\n"
    for r in l:
        print "%u (sqrt %u): %u divisors: %s\n"%(r[-1], math.sqrt(r[-1]), len(r), ', '.join(map(str, r)))


