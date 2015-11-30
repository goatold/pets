#include <stdlib.h>
#include "libsort.h"

/*
Basic quick sort algorithm on int array
pick the first item in array as pivot
*/
void qsortB(int* a, size_t s) {
	int swp;
	size_t i, j;
	// check array size
	if (s <= 1) {
		return;
	}
	i = 0;
	for (j=1;j<s;j++) {
		if (a[j] < a[0]) {
			i++;
			if(j == i) continue;
			swp = a[i];
			a[i] = a[j];
			a[j] = swp;
		}
	}
	if (i != 0) {
		swp = a[0];
		a[0] = a[i];
		a[i] = swp;
	}
	if (i>1) qsortB(a, i);
	if (s>i+2) qsortB(a+i+1, s-i-1);
}
/*
optimized quick sort algorithm on int array
pick the random item in array as pivot
*/
void qsortR(int* a, size_t s) {
	int swp;
	size_t i, j;
	// check array size
	if (s <= 1) {
		return;
	}
	// pick a random item to be the pivot
	srand(*a);
	i = rand() % s;
	if (i != 0) {
		swp = a[0];
		a[0] = a[i];
		a[i] = swp;
	}
	i = 0;
	for (j=1;j<s;j++) {
		if (a[j] < a[0]) {
			i++;
			if(j == i) continue;
			swp = a[i];
			a[i] = a[j];
			a[j] = swp;
		}
	}
	if (i != 0) {
		swp = a[0];
		a[0] = a[i];
		a[i] = swp;
	}
	if (i>1) qsortR(a, i);
	if (s>i+2) qsortR(a+i+1, s-i-1);
}

/*
Insertion Sort
*/
void isort(int* a, size_t s) {
	int swp;
	size_t i,j;
	for (i=1;i<s;i++) {
		swp = a[i];
		for (j=i;j>0 && swp<a[j-1];j--) {
			a[j] = a[j-1];
		}
		a[j] = swp;
	}
}

/*
Shell sort
1, loop gap h from s/3 to 1
2, sort sub arrays of {1st, (h+1)th, (2h+1)th, ...}, {2nd, (h+2)th, (2h+2)th}, ... with insertion sort
3, divide h by 3 continue loop until h decrease to 1
*/
void shsort(int* a, size_t s) {
	int swp;
	size_t h,i,j;
	for(h=s;h/=3;) {
		for (i=h;i<s;i++) {
			swp = a[i];
			for(j=i;j>=h && swp<a[j-h];j-=h) {
				a[j] = a[j-h];
			}
			a[j] = swp;
		}
	}
}



