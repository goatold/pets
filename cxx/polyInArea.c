/*
Program practice: Poly
a 1x1 square moves counterclockwise in a map (matrix) following a given sequence of movements (E, W, S, N)
The square will go back to the start point and enclose a polygon.
The moving track actually marks the border of the polygon
Output: number of internal points that the polygon encloses. It's actually the area of the polygon

Calculation:
    polygon area = sum((vertex_n_x * vertex_n+1_y) - (vertex_n_y * vertex_n+1_x))/2
    where:
        vertex_n_x is the x coordinate of nth vertex, vertex_n_y is the y coordinate of nth vertex
        vertex_n+1_x is the x coordinate of next vertex counterclockwise next to nth vertex, so as vertex_n_y
        n+1 will loop back to start vertex when n reaches the last vertex in counterclockwise sequence.
Note:
    This algorithm will exclude the internal points that eclosed by the external side of adjacent borders.
    For example: given 14 SSEENWNENWNWSS will get 6 instead of 7

compile dbg ver: gcc -Ddbg -Wall poly.c -o poly
compile normal ver: g++ -O2 -lm poly.c

test run:
    echo -e "46\nEEEEEEENNWWWNNNNEEEESENNNWWWWWWWWWWSEESSSSSSSW" | ./poly   #result 43
    echo -e "32\nSNEEESNEEWWNNEENSWWNNWSSWWNNSSSS" | ./poly                 #result 8
    echo -e "48\nEEEEESENENNWWWNNNNEEEESENNNWWWWWWWWWWSEESSSSSSSW" | ./poly #result 44 
    echo -e "52\nEEEEESENENNWWWNNNNEEEESENNNWWWWWNNWSSWWWWSEESSSSSSSW" | ./poly #result 46 
Load test run: 
{
echo $((25000*4))
printf 'E%.0s' {1..25000};printf 'N%.0s' {1..25000};printf 'W%.0s' {1..25000};printf 'S%.0s' {1..25000}
echo
}|./poly # result 25000*25000 = 625000000
{
echo $((24999*4))
printf 'EN%.0s' {1..12500};printf 'WN%.0s' {2..12500};printf 'WS%.0s' {1..12500};printf 'ES%.0s' {2..12500}; echo;
}|./a.out # result 312475001
*/

#include <stdio.h>
#include <string.h>
#include <stdlib.h>

// area of enclosed polygon
unsigned int polyArea = 0;

/*
Read movements sequence from input and calculate vertexs coordinates and polygon area
Input: n is the number of movements
Output: no return value, just print the result to stdout
Note:
    - only the turning points (change of direction) is the vertex
    - start/end point is not checked for vertex. Since it's (0,0), we are safe to ignore it.
*/
void calcPoly(unsigned int n) {
    char preMv, mv;
    preMv = 0;
    unsigned int nm = n;
    int x, y; // coordinates of the moving square
    x = y = 0;// start from (0,0)
    int px, py; // coordinates before movement
    px = py = 0;// start from (0,0)
    int vx, vy; //coordinates of the vertex
    int prv = 0; // has previous vertex
    if (nm < 2) { // at least 2 movements to enclose the polygon
        putchar('0');
        return;
    }
    // read movements and calculate vertexs coordinates and polygon area
    while (nm > 0) {
        mv = getc_unlocked(stdin);// read one movement
        py = y;
        px = x;
        switch (mv) {
        case 'E':
            x++;
            break;
        case 'W':
            x--;
            break;
        case 'S':
            y--;
            break;
        case 'N':
            y++;
            break;
        case EOF:
            nm = 0;
            continue;
        default:
            continue;
        }
        nm--;
#ifdef dbg
        printf("preMv=%c, mv= %c, x=%d, y=%d px=%d, py=%d ", preMv, mv, x, y, px, py);
#endif
        if (preMv == 0) { //this is the first movement
            preMv = mv;
#ifdef dbg
            printf("first mv\n");
#endif
            continue;
        }
        if (preMv != mv) { //direction changed so current point is vertex
            preMv = mv; // save current movement
#ifdef dbg
            printf("vertex(%d,%d) ", px, py);
#endif
            if (prv) { // this is not the 1st vertex
                polyArea += vx*py - vy*px;
            } else { // this is the first vertex
                prv = 1;
            }
            vx = px;
            vy = py;
        }
#ifdef dbg
        printf("\n");
#endif
    }
    if (x!=0 || y!=0) { // polygon not enclosed
        putchar('0');
        return;
    }

    printf("%u\n", polyArea/2);
}

int main(int argc, char* argv[]) {
    unsigned int nm; // number of movements

#ifdef dbg
    printf("Please input number of movements:\n");
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
    calcPoly(nm);
    return 0;
}

