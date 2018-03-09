#include <algorithm>
#include <list>
#include <map>
#include <set>
#include <vector>
#include <unordered_map>
#include <string>
#include <iostream>

using namespace std;

class record {
    public:
    int key;
    string value;

    record(int key, string value) {
        this->key = key;
        this->value = value;
    }

    bool operator<(record r) const {
        return this->key < r.key;
    }
};

int main() {
    vector<string> v{"dfa", "fabsr", "duer5", "oo", "g6584tsfh", "ophj3", "bb8", "d245yz"};
    v.push_back("ou873");
    for (const auto &i : v) {
        cout << i << " ";
    }
    cout << endl;
    auto b = find(v.begin(), v.end(), "oo");
    auto e = find_if(v.begin(), v.end(), [](string s){return s.compare(0,2, "op") == 0;});
    sort(b,e);
    for (const auto &i : v) {
        cout << i << " ";
    }
    cout << endl;
    list<string> l;
    copy(b, e, front_inserter(l));
    for (const auto &i : l) {
        cout << i << " ";
    }
    cout << endl;
    move(make_move_iterator(e), make_move_iterator(v.end()), back_inserter(l));
    for_each (l.begin(), l.end(), [](string i) {
        cout << "'" << i << "' ";
    });
    cout << endl;
    for (const auto &i : v) {
        cout << "'" << i << "' ";
    }
    cout << endl;

    vector<int> iv = {1,2,2,3,4};
    rotate(iv.begin(), iv.begin()+1, iv.end());
    for (const auto &i : iv) {
        cout << "'" << i << "' ";
    }
    cout << endl;

    vector<int> sv = {1,3};
    cout << boolalpha << includes(begin(iv), end(iv), begin(sv), end(sv)) << endl;

    cout << "union: ";
    vector<int> u;
    set_union(begin(iv), end(iv), begin(sv), end(sv), back_inserter(u));
    for (const auto &i : u) {
            cout << i << " ";
    }
    cout << endl;

    string str = "abgec";
    next_permutation(str.begin(), str.end());
    cout << "next permutation: " << str << endl;
    record r1(1, "one");
    record r2(2, "two");
    record r3(5, "five");
    set<record> s;
    s.insert(r3);
    s.insert(r1);
    s.insert(r2);
    auto p = make_pair(1,2);
    for (const auto &itr:s) {
        cout << itr.key << ":" << itr.value << endl;
    }
    map<record, string>m;
    m[r1] = "sss";
    m[r3] = "sde";
    m[r2] = "rtrr";
    m[r2] = "gyuasd";
    for (auto &itr:m) {
        cout << itr.first.key << ":" << itr.second << endl;
    }
    unordered_map<char, int> um;
    um['g'] = 88;
    auto mhasher = um.hash_function();
    cout << "z: " << hex << mhasher('z')  << endl;
    multimap<int, string> mm;
    mm.insert(make_pair(0, "abc"));
    mm.insert(make_pair(5, "xyz"));
    mm.insert(make_pair(5, "st"));
    for (auto &itr:mm) {
        cout << itr.first << ":" << itr.second << endl;
    }
    auto itr =  mm.find(5);
    while (itr != mm.end() && itr->first == 5) {
        cout << itr->first << ":" << itr->second << endl;
        itr++;
    }
    return 0;
}

