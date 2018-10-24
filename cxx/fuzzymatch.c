#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define DBG_TRACE(fmt, args...) \
    if (debugTrace) \
        printf(fmt, ##args);

#define MIN(a, b) ((a) < (b) ? (a) : (b))
#define MIN3(a, b, c) ((a) < (b) ? MIN(a, c) : MIN(b, c))
bool debugTrace = true;
// Levenshtein distance: minimum edition(add, del, substitude) needed to change str1 to str2
unsigned int levenshteinDist(const char* str1, const char* str2, const int max = 32)
{
    unsigned int len1, len2, x, y, diag, nextdiag;
    len1 = strlen(str1);
    len2 = strlen(str2);
    if (len1 > len2) return levenshteinDist(str2, str1, max);
    if ((len2 - len1) >= max) return max;
    unsigned int column[len1+1];
    DBG_TRACE("\t0");
    for (y = 1; y <= len1; y++) {
        DBG_TRACE("\t%c", str1[y-1]);
    }
    DBG_TRACE("\n0\t0");
    for (y = 1; y <= len1; y++) {
        DBG_TRACE("\t%u", y);
        column[y] = y;
    }
    DBG_TRACE("\n");
    for (x = 1; x <= len2; x++) {
        column[0] = x;
        DBG_TRACE("%c\t%u", str2[x-1], x);
        char skipFirst = (x > (max+2)) ? (x-max-2) : 0;
        char skipLast = ((x+max+1)<len1) ? (len1-x-max-1) : 0;
        for (int i=0;debugTrace && i<skipFirst;i++) printf("\t-");
        for (y = skipFirst+1, diag = x-1; y <= len1-skipLast; y++) {
            nextdiag = column[y];
            column[y] = MIN3(column[y] + 1, column[y-1] + 1, diag + (str1[y-1] == str2[x-1] ? 0 : 1));
            diag = nextdiag;
            DBG_TRACE("\t%u", column[y]);
        }
        for (int i=0;debugTrace && i<skipLast;i++) printf("\t-");
        DBG_TRACE("\n");
    }
    return(column[len1]);
}

int main()
{
    const char* strs[] = {
        "abcdef", // exact match
        "aabcdef", // add 1 char
        "ababcdef", // add 2 char
        "axbcdefx", // add 2 char
        "bbcdef",  // sub 1st char
        "abbdef",  // sub 1 char
        "abbyxfyy",  // sub 3 and add 2 char
        "aabcxef", // add 1 and sub 1 char
        "azczf", // del 1 char and sub 2 char
        "axcef", // del 1 and sub 1 char
        "aabcxefx", // add 2 char
    };
    for (int i=0; i<sizeof(strs)/sizeof(char*); i++) {
        printf("%s: %u\n", strs[i], levenshteinDist(strs[i], "abcdef", 2));
    }
    return 0;
}
