#include <sys/time.h>
#include <sys/resource.h>
#include <string.h>
#include <errno.h>
#include <stdio.h>
#include <sys/resource.h>
#include <iostream>
using namespace std;
/********************
* General background info on resources limits setting.
*
* - ulimit set resources limit of current shell and it's child processes
* - /etc/security/limits.conf configure system wide limits
* - sysctl sets the hard limits in kernel level.
* - setrlimit() sets the limits in current process level.
*
* - refer to man setrlimit/getrlimit for more syscall details
* - refer to man setcap/getcap/capabilities for details granting
    program the privilege to change limit that exceeds sys level
********************/

void prtlmt(int r) {
	struct rlimit l;
	int ret = 0;
	int en = 0;
	ret = getrlimit(r, &l);
	if (ret == 0) {
		cout << "current limit " << r << ": S" << l.rlim_cur << " H" << l.rlim_max << endl;
	} else {
		en =  errno;
		cout << "get limit error: " << strerror(en) << endl;
	}
}

void setlmt(int r, const struct rlimit *l) {
	int ret = 0;
	int en = 0;
	ret = setrlimit(r, l);
	if (ret == 0) {
		cout << "set limit " << r << " to: S" << l->rlim_cur << " H" << l->rlim_max << endl;
	} else {
		en =  errno;
		cout << "setlimit error: " << strerror(en) << endl;
	}
}

int main() {
	struct rlimit l;
	prtlmt(RLIMIT_NOFILE);
	l.rlim_cur = 100;
	l.rlim_max = 120;
	setlmt(RLIMIT_NOFILE, &l);
	prtlmt(RLIMIT_NOFILE);
	// test exceeding limits
	int i = 0;
	char fn[128] = "/tmp/t";
	FILE *f = NULL;
	while (1) {
		sprintf(fn+6, "%d", i);
		cout << "opening " << fn;
		f =  fopen(fn, "w+");
		if (f == NULL) {
			int en = 0;
			en =  errno;
			cout << endl << "fopen fail error: " << strerror(en) << endl;
			break;
		}
		cout << " fd: " << fileno(f) << endl;
		i++;
	}
	return 0;
}

