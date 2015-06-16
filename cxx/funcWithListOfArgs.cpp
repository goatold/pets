#include <string.h>
#include <stdio.h>
#include <stdarg.h>
#include <iostream>
using namespace std;
/********************
* Try function with variable list of arguments
*
*
********************/

#define M_vaf(args) vaf args

void vaf(char* arg0, ...) {
    va_list args;
    const char* p;
    va_start(args, arg0);
    cout << "dump args:" << args<< endl;
    cout << "dump arg0:" << arg0<< endl;
    for (p = arg0; *p != '\0'; ++p) {
        char c = *p;
        if (c == '%') {
            ++p;
            if (*p == '\0') {
                break;
            }
            switch (*p) {
                case 's': {
                    const char* data = va_arg(args, const char*);
                    printf("%s", data);
                }
                break;
                case 'd': {
                    int data = va_arg(args, int);
                    printf("%d", data);
                }
                break;
                case 'f': {
                    double data = va_arg(args, double);
                    printf("%f", data);
                }
                break;
                case '%': putchar('%');
                    break;
                default: putchar(*p);
                    break;
            }
        } else {
            putchar(c);
        }
    }
    va_end(args);
}

int main() {
    vaf("arg1: %d, arg2:%s, arg3: %f\n", 23, "2nd Arg", 2.3);
	return 0;
}

