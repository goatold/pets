/*
Programming contest Problem 19
For a given integer k and a string S consisting of lower letters a..z, find the longest substring  s  that has at least k non-overlapping occurrences in S and: 

s is prefix of S, (prefix is any leading contiguous part of S) and 
s is a suffix of S, (suffix is any ending contiguous part of S) 
 
Input: (standard input)

+Line 1 contains a number k, 2 ≦  k  ≦ 1000000.
+Line 2 contains a string S of a length n, 0 ≦ n  ≦ 1000000. 
*/

#include <stdio.h>
#include <string.h>
#include <stdlib.h>

#define MAXLEN 1000000
#define MINOCC 2

#define OK       0
#define NO_INPUT 1
#define TOO_LONG 2

/*
Read a line form stdin with buffer overrun protection
*/
static int getLine(const char *prmpt, char *buff, size_t sz) {
    int ch, extra;

    if (prmpt != NULL) {
        printf ("%s", prmpt);
        fflush (stdout);
    }
    if (fgets(buff, sz, stdin) == NULL)
        return NO_INPUT;

    // If it was too long, there'll be no newline. In that case, we flush
    // to end of line so that excess doesn't affect the next call.
    extra = 0;
    if (buff[strlen(buff)-1] != '\n') {
        while (((ch = getchar()) != '\n') && (ch != EOF))
            extra = 1;
    }

    // Otherwise remove newline and give string back to caller.
    buff[strlen(buff)-1] = '\0';
    return (extra == 1) ? TOO_LONG : OK;
}

/*
function to look in string S for the longest substr that is both prefix and suffix and
no-overlapping occurance at least k
*/
unsigned int maxsublen(unsigned int k, const char * S) {
    unsigned int l,r,c;
    bool m; 
    char* sub; //log purpose only
    l =  strlen(S);
    printf("strlen: %u\n", l);
    sub = (char*)malloc(l/k);
    // maxmium possible substr len is length of given target str devided by minimium occurance
    for(r=l/k; r>0; r--) {
        printf("checking sub len: %u\n", r);
        // expecting we would find the match
        m = true;
        // look for the longest maching sub string for both prefix and suffix
        for(int i=1; i<=r; i++) {
            // examine prefix/suffix char by char
            if (S[r-i] != S[l-i]) {
                m = false;
                break;
            }
        }
        // prefix/suffix not match, continue try shorter substr
        if (!m) {
            continue;
        }
        strncpy(sub, S, r);
        sub[r] = 0;
        printf("matching pre/suffix len: %u %s\n", r, sub);
        // we already found 2 occurances: prefix and suffix
        if (k==2) {
            return r;
        }
        c = 2;
        // look for other matches besids prefix and suffix
        for (int i=r; i<l-r*2+1; i++) {
            for (int j=0; j<r; j++) {
                // comparing with prefix
                if (S[i+j] != S[j]) {
                    m = false;
                    break;
                }
            }
            // moving forward if current offset does NOT match prefix
            if (!m) {
                m = true;
                continue;
            }
            strncpy(sub, S+i, r);
            sub[r] = 0;
            printf("matching in str: %s (%u)\n", sub, i);
            c++;
            // found enough matches?
            if(c>=k) return r;
            i += r-1;
        }
    }
    // r shall be greater than 0 if qualified sub string found
    // if no qualified sub foud all the way down here, we just return 0
    return r;
}

int main(int argc, char* argv[]) {
    char buff[MAXLEN+1];
    unsigned k,result;
    k=MINOCC;
    printf("Please input minimum occorance of sub string:");
    if (scanf("%u", &k) == 1) {
        while (fgetc(stdin) != '\n'); // Read until a newline is found
    } else {
        printf("No valid number given. Exiting!\n");
        exit(1);
    }
    if (k>MAXLEN || k<MINOCC) {
        printf("minimum occurance shall between %u~%u. Given %u. Exiting!\n", MINOCC, MAXLEN, k);
        exit(1);
    }
    printf("Given: %u\n", k);
    switch(getLine("Input target String:", buff, sizeof(buff))) {
    case OK:
        printf("Given Target String:\n%s\n", buff);
        break;
    case TOO_LONG:
        printf("Given Target Sring too long. Truncated to %u:\n%s\n", strlen(buff), buff);
        break;
    case NO_INPUT:
    default:
        printf("No valid target string given. Exiting!\n");
        exit(1);
    }
    result = maxsublen(k, buff);
    buff[result] = 0;
    printf("longest match len:%u substr: %s\n", result, buff);
    return 0;
}

