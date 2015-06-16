#include <iostream>
using namespace std;

class a {
public:
	a();
	~a();
	virtual void prnt();
};

a::a(){
	cout << "a() called" << endl;
}

a::~a(){
	cout << "~a() called" << endl;
}

void a::prnt(){
	cout << "print a" << endl;
}

class b {
public:
	b();
	virtual ~b();
	void prnt();
};

b::b(){
	cout << "b() called" << endl;
}

b::~b(){
	cout << "virtual ~b() called" << endl;
}

void b::prnt(){
	cout << "print b" << endl;
}

class c: public a, public b {
public:
	c(int);
	~c();
	void prnt();
	int getn();
	int operator + (int x);
	int operator + (c *x);
private:
	int n;
};

c::c(int i=0){
	n=i;
	cout << n << "c() called" << endl;
}

int c::getn(){
	return n;
}

c::~c(){
	cout << n << "~c() called" << endl;
}

void c::prnt(){
	cout << n << "print c" << endl;
}

int c::operator + (c *x){
	return x->getn() + n;
}

int c::operator + (int x){
	return x + n;
}

int main() {
	a *c1 = new c(1);
	b *c2 = new c(2);
	c1->prnt();
	c2->prnt();
	cout << (*(c*)c1) + (c*)c2 << endl;
	cout << (*(c*)c1) + 5 << endl;
	delete c1;
	delete c2;
	return 0;
}

