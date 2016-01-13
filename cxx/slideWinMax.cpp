/****************
practice program to print the max items
within the sliding windows of size k,
who slide along an array of size n (n>k)

the time complexity of the algorithm is O(n)
*****************/

#include <iostream>
#include <deque> 
using namespace std;

void printKMax(int arr[], int n, int k){
    int i;
    // a dequeue buffer of capacity k who stores indexes of elements within current window
    deque<int> sb(k); 
    // initiate buffer with the first window
    sb.push_back(0);
    for (i=1;i<k;i++) {
        // remove previous items if new element is bigger
        while((!sb.empty()) && arr[i]>=arr[sb.back()]) sb.pop_back();
        sb.push_back(i); // add new element
    }
    printf("%d ", arr[sb[0]]);
    for (i=k;i<n;i++) {
        // remove items that has slide out of the window
        while((!sb.empty()) && sb.front() <= i-k) sb.pop_front();
        // remove previous items if new element is bigger
        while((!sb.empty()) && arr[i]>=arr[sb.back()]) sb.pop_back();
        sb.push_back(i);
        // the first element is always the max item
        printf("%d ", arr[sb[0]]);
    }
    printf("\n");
}

int main(){
    int arr[] = {12, 1, 78, 90, 57, 89, 56};
    int n = sizeof(arr)/sizeof(arr[0]);
    int k = 3;
    printKMax(arr, n, k);
    int arr2[] = {12, 1, 5, 6, 9, 3, 55, 97, 23, 71, 78, 90, 45, 2, 7, 3, 4, 67, 49, 30, 22, 57, 89, 56};
    n = sizeof(arr2)/sizeof(arr[0]);
    k = 5;
    printKMax(arr2, n, k);
    return 0;
}
