#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fcntl.h>

#include "libsort.h"
/*
print given array of int by
 4 chars per number
 16 numbers per line
*/
void prntArray(int* a, size_t s) {
	for (size_t i=0;i<s;i++) {
		printf("%4d ", *(a+i));
		if ((i+1)%16 == 0) {
			printf("\n");
		}
	}
	printf("\n");
}

typedef struct {
char fname[32];
void (*sfp)(int* a, size_t s);
} sFunc;

int main(int argc, char* argv[]) {
	int *a, *as;
	size_t s;
	// array of sort function pointers to be tested
	sFunc sf[4];
	strcpy(sf[0].fname, "qsortB");
	sf[0].sfp = qsortB;
	strcpy(sf[1].fname, "qsortR");
	sf[1].sfp = qsortR;
	strcpy(sf[2].fname, "insertion sort");
	sf[2].sfp = shsort;
	strcpy(sf[3].fname, "shell sort");
	sf[3].sfp = isort;
	
	s = 10;
	a = (int*)malloc(s*sizeof(int));
	as = (int*)malloc(s*sizeof(int));
	// read rand int form /dev/random
	int randomData = open("/dev/random", O_RDONLY);
	read(randomData, a, 4);
	close(randomData);
	srand(*a);
	// populate int into array to test against
	for (size_t k=0;k<s;k++) {
		*(a+k) = rand()%s;
	}
	printf("input array: \n");
	prntArray(a, s);
	for (int i=0;i<4;i++) {
		printf("test %s\n", sf[i].fname);
		memcpy(as, a, s*sizeof(int));
		(*(sf[i].sfp))(as, s);
		prntArray(as, s);
	}
	free(a);
	free(as);
	return 0;
}

