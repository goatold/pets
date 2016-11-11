#include<stdio.h>
#include <string.h>
/*
 * calculate factorial of given n (<=100)
 * because n! will grow out of any int types soon enough as n increases
 * I will use an array of small numbers to hold individual digits of the final result
 */
int main()
{
	// max accept n is 100, max possible number of digits of result is less that 200
	int numOfTest, n;
	unsigned char result[200], resultLen, i, j, k;
	unsigned short x;
    printf("number of test (<=100):");
	scanf("%d", &numOfTest);
	while(numOfTest--)
	{
		printf("input n (<=100) for test %u:", numOfTest);
		scanf("%d", &n);
        if (n > 100) {
            printf("input n > 100! try again\n");
            continue;
        }
		// start from 1! = 1
		resultLen = 1;
        memset(result, 0, sizeof(result));
		result[0] = 1;
		for(i = 2; i <= n; i++)
		{
			x = 0;
			for(j = 0; j < resultLen; j++)
			{
                x += result[j] * i;
                result[j] = x % 10;
				x /= 10;
			}
			while(x)
			{
				result[j] = x % 10;
				j++;
				resultLen++;
				x /= 10;
			}
            printf("%u! len %u  = ", i, resultLen);
            for(k = resultLen; k > 0; k--)
                printf("%d", result[k-1]);
            printf("\n");
		}
		printf("test %u result:", numOfTest);
		for(k = resultLen; k > 0; k--)
			printf("%d", result[k-1]);
		printf("\n");
	}
	return 0;
}
