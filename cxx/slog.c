/**          -*- slog.c -*-
 *
 * Description:
 * 
 *   cmdline program to write to syslog
 */

#include <ctype.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <strings.h>
#include <syslog.h>


const char* usageString = 
"Usage: \n"
"slog -l <log_level> [-p prefix] <\"msg\">\n"
"log_level:\n"
"m: LOG_EMERG\n"
"a: LOG_ALERT\n"
"c: LOG_CRIT\n"
"e: LOG_ERR\n"
"w: LOG_WARNING\n"
"n: LOG_NOTICE\n"
"i: LOG_INFO\n"
"d: LOG_DEBUG\n";

int main (int argc, char **argv) {
    /* log level
     * LOG_EMERG
     * LOG_ALERT
     * LOG_CRIT
     * LOG_ERR
     * LOG_WARNING
     * LOG_NOTICE
     * LOG_INFO
     * LOG_DEBUG
     * */
    int llvl = LOG_INFO;
    int c;
    char* prx = "logtest";
    while ((c = getopt (argc, argv, ":l:p:")) != -1) {
        switch (c) {
            case 'p':
            prx = optarg;
                break;
                case 'l':
            switch (optarg[0]) {
              case 'm':
                llvl = LOG_EMERG;
                break;
              case 'a':
                llvl = LOG_ALERT;
                break;
              case 'c':
                llvl = LOG_CRIT;
                break;
              case 'e':
                llvl = LOG_ERR;
                break;
              case 'w':
                llvl = LOG_WARNING;
                break;
              case 'n':
                llvl = LOG_NOTICE;
                break;
              case 'i':
                llvl = LOG_INFO;
                break;
              case 'd':
                llvl = LOG_DEBUG;
                break;
              default:
                llvl = LOG_EMERG;
            } // endof swit log level
            break;
            case '?':
                case 'h':
                fprintf (stderr, "This program write msg to system log.");
                fprintf(stderr, usageString);
                return 1;
            default:
                fprintf(stderr, "Unknown option `-%c'.\n", c);
                fprintf(stderr, usageString);
                return 1;
          }//endof switch opt c
        }// end of while getopt
    printf ("writing level:%d\n\'\'\'%s\'\'\', to system log\n",
                llvl, argv[optind]);
    openlog(prx, LOG_CONS, LOG_USER);
    syslog(llvl, "%s", argv[optind]);
    return 0;
}
