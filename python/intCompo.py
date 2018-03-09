import random
import timeit

def intCompo(n, k):
    if (k < 1 or n < k):
        return
    elif (k == 1):
        yield (n,)
    elif (k == n):
        yield (1,) * n
    else:
        for i in range(1, n-k+2):
            for subc in intCompo(n-i, k-1):
                yield (i,) + subc

def intCompoU(n, k, l=1):
    if (k < 1 or n < k or n < l):
        return
    elif (k == 1):
        yield (n,)
    elif (k == n and l == 1):
        yield (1,) * n
    else:
        for i in range(l, int(n/k)+1):
            for subc in intCompoU(n-i, k-1, i):
                yield (i,) + subc

def calcPi(n=1000000):
    pi = 0
    for i in range(n):
        x = random.random()
        y = random.random()
        d = x*x + y*y
        if d < 1.0: pi += 1 
    print("n %u pi %u, %f"%(n, pi, pi/(n*0.25)))

print(timeit.timeit(calcPi, number=20))

def testCompo(n,k):
    t = tuple(intCompo(n,k))
    print("compo of %u with %u parts #%u"%(n, k, len(t)))
    print(t)

def testCompoU(n,k):
    t = tuple(intCompoU(n,k))
    print("compo of %u with %u parts #%u"%(n, k, len(t)))
    print(t)

for n in range(2, 21):
    for k in range(1, n):
        l = len(tuple(intCompo(n,k)))
        lu = len(tuple(intCompoU(n,k)))
        print(n, k, l, lu)
