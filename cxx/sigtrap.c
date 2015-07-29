/*
Example program to trap signal and print signal info
There are 2 options to achieve this:
1, sigaction: set SA_SIGINFO flag and set signal handler via sigaction()
2, signalfd: mask signal and read out signalfd_siginfo from file descriptor created via signalfd()

In the main we will open a socket and block on select.
On signal interruption we will be unblocked from select and print out signal info
*/

#include <stdio.h>
#include <unistd.h>
#include <signal.h>
#include <sys/signalfd.h>
#include <string.h>
#include <sys/select.h>
#include <sys/time.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <stdlib.h>
#include <netinet/in.h>

#define BUFFSIZE 32

int srvrSock;
struct sockaddr_in srvrAddr;
char buffer[BUFFSIZE];

fd_set rfds;
struct timeval tv;

int sfd;
struct signalfd_siginfo si;
sigset_t mask;

struct sigaction act;

void Die(char *mess) { perror(mess); exit(1); }
void printsi(struct signalfd_siginfo &si) {
  printf("Signal number %u\n"
         "Error number (unused) %u\n"
         "Signal code %u\n"
         "PID of sender %u\n"
         "Real UID of sender %u\n"
         "File descriptor (SIGIO) %u\n"
         "Kernel timer ID (POSIX timers) %u\n"
         "Band event (SIGIO) %u\n"
         "POSIX timer overrun count %u\n"
         "Trap number that caused signal %u\n"
         "Exit status or signal (SIGCHLD) %u\n"
         "Integer sent by sigqueue(2) %u\n"
         "Pointer sent by sigqueue(2) %lu\n"
         "User CPU time consumed (SIGCHLD) %lu\n"
         "System CPU time consumed (SIGCHLD) %lu\n"
         "Address that generated signal (for hardware-generated signals) %lu\n",
         si.ssi_signo, si.ssi_errno, si.ssi_code, si.ssi_pid, si.ssi_uid, si.ssi_fd, si.ssi_tid, si.ssi_band,
         si.ssi_overrun, si.ssi_trapno, si.ssi_status, si.ssi_int, si.ssi_ptr, si.ssi_utime, si.ssi_stime, si.ssi_addr);
}

static void sigTrap(int sig, siginfo_t *siginfo, void *context)
{
	printf("int        Signal number %d\n"
         "int        An errno value %d\n"
         "int        Signal code %d\n"
         "int        Trap number that caused hardware-generated signal (unused on most architectures) %d\n"
         "pid_t      Sending process ID %ld\n"
         "uid_t      Real user ID of sending process %ld\n"
         "int        Exit value or signal %d\n"
         "clock_t    User time consumed %d\n"
         "clock_t    System time consumed %d\n"
         "sigval_t   Signal value %d\n"
         "int        POSIX.1b signal %d\n"
         "void    *  POSIX.1b signal %x\n"
         "int        Timer overrun count; POSIX.1b timers %d\n"
         "int        Timer ID; POSIX.1b timers %d\n"
         "void    *  Memory location which caused fault %x\n"
         "int        Band event %d\n"
         "int        File descriptor %d\n",
         siginfo->si_signo, siginfo->si_errno, siginfo->si_code, siginfo->si_trapno,
         siginfo->si_pid, siginfo->si_uid, siginfo->si_status, siginfo->si_utime,
         siginfo->si_stime, siginfo->si_value, siginfo->si_int, siginfo->si_ptr,
         siginfo->si_overrun, siginfo->si_timerid, siginfo->si_addr, siginfo->si_band,
         siginfo->si_fd);
}

int main(int argc, char *argv[]) {
  int retval;
  time_t tsec = 5;
  if (argc != 2) {
    fprintf(stderr, "USAGE: %s <port>\n", argv[0]);
    exit(1);
  }
  /* Create the TCP socket */
  if ((srvrSock = socket(PF_INET, SOCK_STREAM, IPPROTO_TCP)) < 0) {
    Die("Failed to create socket");
  }
  /* Construct the server sockaddr_in structure */
  memset(&srvrAddr, 0, sizeof(srvrAddr));       /* Clear struct */
  srvrAddr.sin_family = AF_INET;                  /* Internet/IP */
  srvrAddr.sin_addr.s_addr = htonl(INADDR_ANY);   /* Incoming addr */
  srvrAddr.sin_port = htons(atoi(argv[1]));       /* server port */
  /* Bind the server socket */
  if (bind(srvrSock, (struct sockaddr *) &srvrAddr,
                               sizeof(srvrAddr)) < 0) {
    Die("Failed to bind the server socket");
  }
  /* Listen on the server socket */
  if (listen(srvrSock, MAXPENDING) < 0) {
    Die("Failed to listen on server socket");
  }

  // set signal handler
  memset (&act, '\0', sizeof(act));
  act.sa_sigaction = &sigTrap;
  act.sa_flags = SA_SIGINFO;
  if (sigaction(SIGQUIT, &act, NULL) < 0) Die("set signal handler fail");
  if (sigaction(SIGUSR1, &act, NULL) < 0) Die("set signal handler fail");

  /* mask signal*/
  sigemptyset (&mask);
	sigaddset (&mask, SIGTERM);
	sigaddset (&mask, SIGINT);
	if (sigprocmask(SIG_BLOCK, &mask, NULL) < 0) Die("mask signal fail");
	// open signal file descriptor
	sfd = signalfd(-1, &mask, 0);
	if (sfd < 0) Die("signalfd() fail");

  /* Loop on select */
  while (1) {
    FD_ZERO(&rfds);
    FD_SET(srvrSock, &rfds);
    FD_SET(sfd, &rfds);
    tv.tv_sec = tsec;
    tv.tv_usec = 0;
    retval = select(2, &rfds, NULL, NULL, &tv);
    if (retval == -1) Die("select() fail");
    else if (retval) {
      if (FD_ISSET(srvrSock, &rfds)){
        if (recv(srvrSock, buffer, BUFFSIZE, 0) > 0) printf("sock data received: %s\n");
        else Die("sock recv failure");
      }
      if (FD_ISSET(sfd, &rfds)) {
        ssize_t res;
        res = read(sfd, &si, sizeof(si));
        if (res < 0 || res != sizeof(si)) Die("read signal fd fail");
        else printsi(si);
      }
    } else {
      printf("No data in last %d seconds\n", tsec);
    }// endof retval check
  }// endof while(1)
}
