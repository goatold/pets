CC=g++
CFLAGS=-Wall -Werror -g -O0 -Ilibsort
LDFLAGS=-Llib -lsort
MAIN=sorttest
MAINOBJS=sorttest.o

all: $(MAIN)

.cc.o:
	$(CC) -c $(CFLAGS) $<

$(MAIN): $(MAINOBJS)
	$(CC) -o $(MAIN) $(MAINOBJS) $(LDFLAGS)
