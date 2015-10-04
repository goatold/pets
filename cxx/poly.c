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
    Above algorithm will exclude the internal points that eclosed by the external side of adjacent&tangent concave borders.
    So we need to remove the vertices of adjacent&tangent concave borders

compile dbg ver: gcc -g -Ddbg -Wall poly.c -o poly
compile normal ver: g++ -O2 -lm poly.c

test run:
    echo -e "46\nEEEEEEENNWWWNNNNEEEESENNNWWWWWWWWWWSEESSSSSSSW" | ./poly   #result 43
    echo -e "32\nSNEEESNEEWWNNEENSWWNNWSSWWNNSSSS" | ./poly                 #result 8
    echo -e "48\nEEEEESENENNWWWNNNNEEEESENNNWWWWWWWWWWSEESSSSSSSW" | ./poly #result 44 
    echo -e "52\nEEEEESENENNWWWNNNNEEEESENNNWWWWWNNWSSWWWWSEESSSSSSSW" | ./poly #result 46 
concave adjacent borders, width-1-concave test:
    echo -e "14\nSSEENWNENWNWSS" | ./poly #result 7
    echo -e "60\nENESEEEEEENNESSEEEEENNWWNENNNNWWSSWWNENWWWWWWWWWSEESWWWSSSSS"|./poly #result 91
Load test run: 
{
echo $((25000*4))
printf 'E%.0s' {1..25000};printf 'N%.0s' {1..25000};printf 'W%.0s' {1..25000};printf 'S%.0s' {1..25000}
echo
}|./a.out # result 25000*25000 = 625000000
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
    - only the turning points (change of direction) are the vertices
    - vertices of width-1-concave will be checked and removed
*/
void calcPoly(unsigned int n) {
    char preMv, mv, frstMv;
    // create array of coordinates of verteices
    int* vx, *vy;
    vx = malloc(sizeof(int)*(n+1));
    vy = malloc(sizeof(int)*(n+1));
    unsigned int vc = 0; // count of vertices
    preMv = 0;
    int nm = n;
    int x, y; // coordinates of the moving square
    x = y = 0;// start from (0,0)
    int px, py, px2, py2; // temporarily holding coordinates of previous point
    px = py = 0;// start from (0,0)
/* as input will always be valid, ignore the check
    if (nm < 2) { // at least 2 movements to enclose the polygon
        putchar('0');
        return;
    }
*/
    // read movements and calculate vertexs coordinates and polygon area
    while (nm >= 0) {
        if (nm == 0){ // back to start
            mv = frstMv;
        } else {
            mv = fgetc(stdin);// read one movement
            if (mv == EOF) break;
        }
        nm--;
#ifdef dbg
        printf("step%u preMv=%c, mv=%c, x=%d, y=%d ", n-nm, preMv, mv, x, y);
#endif
        if (preMv == 0) { //this is the first movement
            preMv = mv;
            frstMv = mv;
#ifdef dbg
            printf("first mv %c", mv);
#endif
        } else {
            if (preMv != mv) { //direction changed so current point is vertex
                // check width-1-concave and remove vertices, so the width-1-concave is flattened
                if (vc > 1) { // at least 2 vertices to form a concave
                    if (vc > 2) {// there are more vertices before current concave
                        px = vx[vc-3];
                        py = vy[vc-3];
                    } else {
                        px = py = 0; // refer the start point as the last point
                    }                    
                    // check concave of width 1
                    if (preMv == 'W' && vx[vc-2] > px && vy[vc-2]-vy[vc-1] == 1) {
#ifdef dbg
printf("width 1 concave to East, ");
#endif
                        vc--;
                        if (px > x) {
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    px2 = vx[vc-3];
                                } else {
                                    px2 = 0; // refer the start point as the last point
                                }                    
                                if (vx[vc-2] == px2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vx[vc-1] = px;
                            vy[vc-1] = y;
#ifdef dbg
printf("later longer vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (x > px) {
                            preMv = 'S';
                            vx[vc-1] = x;
#ifdef dbg
printf("later shorter vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (x == px) {
                            preMv = 'S';
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    px2 = vx[vc-3];
                                } else {
                                    px2 = 0; // refer the start point as the last point
                                }                    
                                if (vx[vc-2] == px2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vc--;
#ifdef dbg
printf("aligned: vertex[%d](%d,%d) removed", vc, vx[vc], vy[vc]);
#endif
                        }
                    } else if (preMv == 'E' && vx[vc-2] < px && vy[vc-1]-vy[vc-2] == 1) {
#ifdef dbg
printf("width 1 concave to West, ");
#endif
                        vc--;
                        if (px < x) {
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    px2 = vx[vc-3];
                                } else {
                                    px2 = 0; // refer the start point as the last point
                                }                    
                                if (vx[vc-2] == px2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vx[vc-1] = px;
                            vy[vc-1] = y;
#ifdef dbg
printf("later longer vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (x < px) {
                            preMv = 'N';
                            vx[vc-1] = x;
#ifdef dbg
printf("later shorter vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (x == px) {
                            preMv = 'N';
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    px2 = vx[vc-3];
                                } else {
                                    px2 = 0; // refer the start point as the last point
                                }                    
                                if (vx[vc-2] == px2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vc--;
#ifdef dbg
printf("aligned: vertex[%d](%d,%d) removed", vc, vx[vc], vy[vc]);
#endif
                        }
                    } else if (preMv == 'S' && vy[vc-2] > py && vx[vc-1]-vx[vc-2] == 1) { // conave to North
#ifdef dbg
printf("width 1 concave to North, ");
#endif
                        vc--;
                        if (py > y) { 
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    py2 = vy[vc-3];
                                } else {
                                    py2 = 0; // refer the start point as the last point
                                }                    
                                if (vy[vc-2] == py2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vx[vc-1] = x;
                            vy[vc-1] = py;
#ifdef dbg
printf("later longer vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (y > py) {
                            preMv = 'E';
                            vy[vc-1] = y;
#ifdef dbg
printf("later shorter vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (y == py) {
                            preMv = 'E';
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    py2 = vy[vc-3];
                                } else {
                                    py2 = 0; // refer the start point as the last point
                                }                    
                                if (vy[vc-2] == py2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vc--;
#ifdef dbg
printf("aligned: vertex[%d](%d,%d) removed", vc, vx[vc], vy[vc]);
#endif
                        }
                    } else if (preMv == 'N' && vy[vc-2] < py && vx[vc-2]-vx[vc-1] == 1) {
#ifdef dbg
printf("width 1 concave to South, ");
#endif
                        vc--;
                        if (py < y) { 
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    py2 = vy[vc-3];
                                } else {
                                    py2 = 0; // refer the start point as the last point
                                }                    
                                if (vy[vc-2] == py2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vx[vc-1] = x;
                            vy[vc-1] = py;
#ifdef dbg
printf("later longer vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (y < py) {
                            preMv = 'W';
                            vy[vc-1] = y;
#ifdef dbg
printf("later shorter vertex[%d](%d,%d)", vc-1, vx[vc-1], vy[vc-1]);
#endif
                        } else if (y == py) {
                            preMv = 'W';
                            if(vc > 1){ //more than 1 vertices before current point
                                if (vc > 2) {//more than 2 vertices before current point
                                    py2 = vy[vc-3];
                                } else {
                                    py2 = 0; // refer the start point as the last point
                                }                    
                                if (vy[vc-2] == py2) {// last 2 vertices on the same line
                                    vc--;
                                }
                            }
                            vc--;
#ifdef dbg
printf("aligned: vertex[%d](%d,%d) removed", vc, vx[vc], vy[vc]);
#endif
                            }
                    }
                } // endof vc>1
                if (preMv != mv) { //after concave check and possible direction change
                    preMv = mv; // save current movement
                    vx[vc] = x;
                    vy[vc] = y;
#ifdef dbg
printf("vertex[%d](%d,%d)", vc, x, y);
#endif
                    vc++;
                }
            }//endof preMv != mv
        }//endof first mv and vertex check
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
        default:
            break;
        }
#ifdef dbg
printf("\n");
#endif

    } // end of all movements
/* as input will always be valid, ignore the check
    if (x!=0 || y!=0) { // polygon not enclosed
        putchar('0');
        return;
    }
*/
    int i;
    for (i=0;i<vc-1;i++) {
        polyArea += vx[i]*vy[i+1] - vy[i]*vx[i+1];
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

