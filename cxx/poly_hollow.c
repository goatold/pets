/*
Program practice: Poly (this version only mark visited spots)
a 1x1 square moves in a map (matrix) following a given sequence of movements (E, W, S, N).
Output: number of intersection points whose adjacent squares have all been visited.

2 solution depends on the data structure that stores the map
1, balanced tree to store visited spots:
    insert new spot on every move to the tree, search tree to check whether given x,y already in tree
2, 2 dimention matrix:
    requires n*n space, constant mark and check time

*/
#include <stdbool.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>

typedef struct spot {
    int x;
    int y;
} spot;

// number of surrounded intersection points
unsigned int numOfIPoints = 0;
// matrix width and height
unsigned int matrixSize_x = 0;
unsigned int matrixSize_y = 0;
// min/max value of coordinates
int max_x, max_y, min_x, min_y;
// Array to store visited spot
spot* spotArray;
// bit array to store the matrix
int* matrixBitArray;
// index of bit in array
unsigned int bitIndx;

// calc bit index from x,y
unsigned int calcBitIndex(int x, int y) {
        return (unsigned int)((x-min_x)+(y-min_y)*matrixSize_x);
}

/*
return whether the given coordinates x, y has been marked
*/
bool is_markedInBitArray(int x, int y){
    if (x > max_x || y > max_y || x < min_x || y < min_y) return false;
    bitIndx = calcBitIndex(x, y);
    return (matrixBitArray[bitIndx/32] & 1<<(bitIndx%32));
}

/*
mark given coordinates x, y
*/
void markInBitArray(int x, int y){
    bool adj[8];
    // check whether the bit has already been marked
    if (is_markedInBitArray(x, y)) return;
    // mark corresponding bit
    matrixBitArray[bitIndx/32] |= 1<<(bitIndx%32);
    // check adjacent spots
    adj[0] = is_markedInBitArray(x, y+1);
    adj[1] = is_markedInBitArray(x+1, y+1);
    adj[2] = is_markedInBitArray(x+1, y);
    adj[3] = is_markedInBitArray(x+1, y-1);
    adj[4] = is_markedInBitArray(x, y-1);
    adj[5] = is_markedInBitArray(x-1, y-1);
    adj[6] = is_markedInBitArray(x-1, y);
    adj[7] = is_markedInBitArray(x-1, y+1);
    if(adj[0] && adj[1] && adj[2]) numOfIPoints++;
    if(adj[0] && adj[6] && adj[7]) numOfIPoints++;
    if(adj[2] && adj[3] && adj[4]) numOfIPoints++;
    if(adj[4] && adj[5] && adj[6]) numOfIPoints++;
    return;
}

/*
Store visited spots in array
*/
void markInArray(unsigned int n) {
    char mv;
    size_t matrixSize;
    unsigned int nm = n;
    int x, y; // coordinates of the square
    x = y = max_x = max_y = min_x = min_y = 0;
    if (nm > 0) {
        spotArray = malloc(sizeof(spot)*(nm+1));
        memset(spotArray, 0, sizeof(spot)*(nm+1));
        if (spotArray == NULL) {
            perror("malloc spot array error:");
            return;
        }
    }
    // read and save all visited spot in array
    while (nm > 0) {
        mv = fgetc(stdin);
        switch (mv) {// read one movement
        case 'E':
            x++;
            nm--;
            if (x > max_x) max_x = x;
            spotArray[nm].x = x;
            spotArray[nm].y = y;
            break;
        case 'W':
            x--;
            nm--;
            if (x < min_x) min_x = x;
            spotArray[nm].x = x;
            spotArray[nm].y = y;
            break;
        case 'S':
            y--;
            nm--;
            if (y < min_y) min_y = y;
            spotArray[nm].x = x;
            spotArray[nm].y = y;
            break;
        case 'N':
            y++;
            nm--;
            if (y > max_y) max_y = y;
            spotArray[nm].x = x;
            spotArray[nm].y = y;
            break;
        case EOF:
            nm = 0;
            break;
        default:
            break;
        }
    }
    // create matrix bitmap according to max/min x/y
    matrixSize_x = (max_x - min_x + 1);
    matrixSize_y = (max_y - min_y + 1);
    matrixSize=((matrixSize_x * matrixSize_y)/32+1)*sizeof(int);
    matrixBitArray = malloc(matrixSize);
    memset(matrixBitArray, 0, matrixSize);
    // mark spot(0,0)
    numOfIPoints;
    markInBitArray(0,0);
    for (nm = 0; nm <= n; nm++) {
        // mark corresponding bit
        markInBitArray(spotArray[nm].x, spotArray[nm].y);
    }
    printf("number of points: %u\n", numOfIPoints);
#ifdef dbg
for(int i=max_y; i>=min_y; i--){
    printf("y=%3d ",i);
    for(int j=min_x; j<=max_x; j++){
        if (j == 0 && i == 0){
            putchar('*');
            continue;
        }
        if (is_markedInBitArray(j, i)) putchar('1');
        else putchar('0');
    }
    printf("\n");
}
#endif
}

int main(int argc, char* argv[]) {
    unsigned int nm; // number of movements
#ifdef dbg
    printf("Please input number of movements:");
#endif
    if (scanf("%u", &nm) == 1) {
        while (fgetc(stdin) != '\n'); // Read until a newline is found
    } else {
#ifdef dbg
        printf("No valid number given. Exiting!\n");
#endif
        putchar('0');
        return 0;
    }
    markInArray(nm);
    return 0;
}

