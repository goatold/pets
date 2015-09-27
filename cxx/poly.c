/*
Program practice: Poly
a 1x1 square moves in a map (matrix) following a given sequence of movements (E, W, S, N).
Output: number of intersection points whose adjacent squares have all been visited.

2 solution depends on the data structure that stores the map
1, balanced tree to store visited spots:
    insert new spot on every move to the tree, search tree to check whether given x,y already in tree
2, 2 dimention matrix:
    requires 4*n*n space, constant mark and check time
*/
#include <stdbool.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>

/*
mark given coordinates x, y
depends on the data structure that stores the map
1, balance tree to store visited spots
2, 2 dimention matrix
*/
void mark(int x, int y){
    
}

/*
return whether the given coordinates x, y has been marked
*/
bool is_marked(int x, int y){
    return false;
}

int main(int argc, char* argv[]) {
    unsigned int nm; // number of movements
    unsigned int result;
    char mv;
    bool valid_mv;
    int x,y; // coordinates of the square
    x = y = 0;
    result = 0;
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
    while (nm > 0) {
        mv = fgetc(stdin);
        valid_mv = true;
        switch (mv) {// read one movement
        case 'E':
            nm--;
            x++;
            if (!is_marked(x,y)) {
                mark(x, y);
                // checkAdj(x, y 'E');
                a0 = is_marked(x, y+1);
                a1 = is_marked(x+1, y+1);
                a2 = is_marked(x+2, y+1);
                a3 = is_marked(x+2, y);
                a4 = is_marked(x+2, y-1);
                a5 = is_marked(x+1, y-1);
                a6 = is_marked(x, y-1);
            }
            if (a0 && a1) result++;
            if (a1 && a2 && a3) result++;
            if (a3 && a4 && a5) result++;
            if (a5 && a6) result++;
            break;
        case EOF:
            valid_mv = false;
            nm = 0;
            break;
        default:
            valid_mv = false;
    }
}

}