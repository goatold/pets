CC=gcc
CLIBFLAGS=-c -Wall -Werror -O0 -fPIC -g
LDLIBFLAGS=-shared
LIBOBJS=libsort.o
LIBOUT=libsort.so

all: $(LIBOUT)

.cc.o:
	$(CC) $(CLIBFLAGS) $<

$(LIBOUT): $(LIBOBJS)
	$(CC) -o $(LIBOUT) $(LDLIBFLAGS) $(LIBOBJS)
