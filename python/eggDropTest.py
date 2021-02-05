#!/bin/env python3
'''
To find out what height a egg can survive from dropping in range of bottom to top.
n eggs are provided for test. They are exactly the same.
If an egg survived a test, it can be used again in later test.
What is the minimum test required in worst case to find:
the integer maximum height an egg can land safely.

The solution here is generally divide-and-conqure with recursive function
'''

def findMinTrial(top, n, kntbl):
    if top == 0:
        return 0
    # at leat 1 egg is required
    if n < 1:
        n = 1
    if kntbl[top-1][n-1][0] > 0:
        return kntbl[top-1][n-1][0]
    # the most conservative solutoion is to start form 1
    # and try each height up to top
    testMin = top
    # in case there is only one egg
    # we have to take the most conservative solutoion
    if n == 1:
        print("start drop from 1 to %u with 1 egg"%(top))
        return testMin
    # in case top == bottom, only 1 test needed
    if 1 == top:
        print("drop at 1 and end")
        return 1
    # in general cases we will call findMinTrial() recursively
    # assume most conservative solution is the best
    bestk = 1
    # iterate through all start point to find the best solution
    for k in range(2, top):
        # if we start test at k (1 < k < top)
        # if egg broken at k, we reduce the problem to findMinTrial(k-1, n-1)
        # if egg survive at k, we reduce the problem to findMinTrial(top-k, n)
        testk = max(findMinTrial(k-1, n-1, kntbl), findMinTrial(top-k, n, kntbl)) + 1
        if testk < testMin:
            bestk = k
            testMin = testk
    # in case we start from the top
    testk = findMinTrial(top-1, n-1, kntbl) + 1
    if testk < testMin:
        bestk = top
        testMin = testk
    print(f"best start drop at {bestk} and minT({top}, {n})={testMin}")
    kntbl[top-1][n-1] = [testMin, bestk]
    return testMin


if __name__ == '__main__':
    maxTop = 100
    maxN = 2
    kntbl = [[[i,1]] + [[0,1]] * (maxN-1) for i in range(1,maxTop+1)]
    kntbl[0] = [[1,1]] * maxN
    kntbl[1] = [[2,1]] * maxN
    findMinTrial(maxTop, maxN, kntbl)
    top = maxTop
    s = kntbl[top-1][1][0]
    t = 0
    for i in range(1, s+1):
        k = kntbl[top-1][1][1]
        t+=k
        print(f"{i} try at {t}/{k} n({kntbl[top-1][0][1]})")
        top -= k 