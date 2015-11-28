/*
Tic Toe Game -- Unbeatable Computer

Computer and a player play on a n*n square alternatively.
To win the game one must take an entire line (a row, column or diagonal).
There are totaly n rows + n columns + 2 diagonals
To distribute the calculation, after each move,
I will save occupancy counts of both computer and player for each line.
The computer's strategy is to maximize its chance and minimize the opponent's chance to win,
according to current occupancies of all lines.
*/

#include <stdio.h>
#include <string.h>

#define SLATE_SIZE	3
#define COMPUTER_PAWN	'X'
#define PLAYER_PAWN		'O'
#define BLANK_CHAR		' '

typedef enum { false, true } bool;

typedef struct {
	unsigned int occup_computer;
	unsigned int occup_player;
} LineState;

typedef struct {
	char gameSlate[SLATE_SIZE*SLATE_SIZE];
	LineState diagLState;
	LineState diagRState;
	LineState rowStates[SLATE_SIZE];
	LineState colStates[SLATE_SIZE];
	unsigned int round;
	bool computerTurn;
	bool over;
} Game;

// score the benefit of a move
typedef struct {
	unsigned int block; // block # of lines that opponent could win
	unsigned int extent; // extent # of lines I occupy
	unsigned int increment; // # of lines I will increase my occupancy
	bool saving; // will stop opponent from winning in next move
} MoveScore;

Game TheGame;

void startNewGame(Game*);
void displaySlate(char[]);
void getMove(unsigned int*);
bool validMove(const char*, const unsigned int);
bool evaluateLine(LineState*, MoveScore*);
bool evaluateMove(Game*, const unsigned int, MoveScore*);
void playMove(Game*, const char, const unsigned int);
bool updateLineState(char, LineState*);
void updateGameState(Game*, char, unsigned int, unsigned int);
void claimWinner(const char);
void computerPlay(Game*);

void startNewGame(Game* aGame) {
	char inputChar;
	printf("################\n"
		   "New Game!\n");
	memset(aGame->gameSlate, BLANK_CHAR, sizeof(aGame->gameSlate));
	memset(&(aGame->diagLState), 0, sizeof(LineState)*(SLATE_SIZE*2+2));
	// ask for who play first
	printf("Computer first?(n|y)");
	scanf(" %[ny]", &inputChar);
	getchar();
	if (inputChar == 'n') {
		aGame->computerTurn = false;
		printf("Player first!\n");
	} else {
		aGame->computerTurn = true;
		printf("Computer first!\n");
	}
	
	aGame->round = 0;
	aGame->over = false;
	displaySlate(aGame->gameSlate);
}

void displaySlate(char gameSlate[]) {
	int row, col;
	printf("\n");
	for(row=0;row<SLATE_SIZE;row++){
		for (col=0;col<SLATE_SIZE-1;col++) {
			printf("%c|", gameSlate[row*SLATE_SIZE+col]);
		}
		printf("%c\n", gameSlate[row*SLATE_SIZE+col]);
		if (row<SLATE_SIZE-1) {
			for (col=0;col<SLATE_SIZE-1;col++) printf("-+");
			printf("-\n");
		}
	}
	printf("\n");
}

void getMove(unsigned int* pos){
	printf("Input position to play[0-%u]:", SLATE_SIZE*SLATE_SIZE-1);
	scanf("%u", pos);
	getchar();
}

bool validMove(const char* gameSlate, unsigned int pos) {
	return (pos < 0 ||
	        pos >= SLATE_SIZE*SLATE_SIZE ||
			gameSlate[pos] != BLANK_CHAR)?false:true;
}

// return true if it's a winning move
bool updateLineState(char pawn, LineState* lstate) {
	if (pawn == COMPUTER_PAWN) {
		if (lstate->occup_computer == SLATE_SIZE-1) return true;
		lstate->occup_computer++;
	} else {
		if (lstate->occup_player == SLATE_SIZE-1) return true;
		lstate->occup_player++;
	}
	return false;
}

void updateGameState(Game* aGame, char pawn, unsigned int row, unsigned int col) {
	if (updateLineState(pawn, aGame->rowStates+row) ||
		updateLineState(pawn, aGame->colStates+col) ||
		(row == col && updateLineState(pawn, &(aGame->diagLState))) ||
		(row+col == SLATE_SIZE-1 && updateLineState(pawn, &(aGame->diagRState))) ) {
			claimWinner(pawn);
			aGame->over = true;
			return;
	}
	aGame->round++;
#ifdef DEBUG
	printf("Round%u:row%u(%u,%u), col%u(%u,%u), diagL(%u,%u), diagR(%u,%u)\n",
		aGame->round, row,
		aGame->rowStates[row].occup_computer,
		aGame->rowStates[row].occup_player,
		col,
		aGame->colStates[col].occup_computer,
		aGame->colStates[col].occup_player,
		aGame->diagLState.occup_computer,
		aGame->diagLState.occup_player,
		aGame->diagRState.occup_computer,
		aGame->diagRState.occup_player
		);
#endif
	if (aGame->round >= SLATE_SIZE*SLATE_SIZE) { // maximum possible moves reached
			claimWinner(BLANK_CHAR);
			aGame->over = true;
	}
}

// evaluate a given move per line from the computer's perspective
// return true directly if winning
bool evaluateLine(LineState* lstate, MoveScore* score) {
#ifdef DEBUG
		printf("evaluating on line: %u, %u\n",
			   lstate->occup_computer,
			   lstate->occup_player);
#endif
	if (lstate->occup_computer == SLATE_SIZE-1 &&
		lstate->occup_player == 0) {
#ifdef DEBUG
		printf("Winning on this line\n");
#endif
		return true;
	}
	if (lstate->occup_player == SLATE_SIZE-1 &&
		lstate->occup_computer == 0) {
#ifdef DEBUG
		printf("I must block this line\n");
#endif
		score->saving = true;		 
	}
	if (lstate->occup_computer == 0) {
#ifdef DEBUG
		printf("occupying this line\n");
#endif
		score->extent++;
		if (lstate->occup_player > 0) {
#ifdef DEBUG
			printf("blocking this line\n");
#endif
			score->block++;	
		}
	} else {
		if (lstate->occup_player == 0) {
#ifdef DEBUG
			printf("increase occupancy on this line\n");
#endif
			score->increment++;
		}
	}
	return false;
}

// evaluate a given move
// return true if it's a winning move
bool evaluateMove(Game* aGame, const unsigned int pos, MoveScore* score){
	unsigned int row, col;
	row = pos/SLATE_SIZE;
	col = pos%SLATE_SIZE;
#ifdef DEBUG
	printf("evaluating position %u\n", pos);
#endif
	memset(score, 0, sizeof(MoveScore));
	if (evaluateLine(aGame->rowStates+row, score) ||
		evaluateLine(aGame->colStates+col, score) ||
		(row == col && evaluateLine(&(aGame->diagLState), score)) ||
		(row+col == SLATE_SIZE-1 && evaluateLine(&(aGame->diagRState), score)) ) {
		return true;
	}
#ifdef DEBUG
	printf("score(%u,%u): %u,%u,%u,%u\n",
	       row, col, score->saving, score->block, score->extent, score->increment);
#endif
	return false;
}

void playMove(Game* aGame, const char pawn, const unsigned int pos){
	unsigned int row, col;
	row = pos/SLATE_SIZE;
	col = pos%SLATE_SIZE;
	aGame->gameSlate[pos] = pawn;
	if (pawn == COMPUTER_PAWN) {
		printf("Computer takes:");
	}
	if (pawn == PLAYER_PAWN) {
		printf("Player takes:");
	}
	printf("%u,%u!\n", row, col);
	updateGameState(aGame, pawn, row, col);
}

void claimWinner(char pawn) {
	if (pawn == COMPUTER_PAWN){
		printf("Computer Win!\n");
	} else if (pawn == PLAYER_PAWN){
		printf("Player Win!\n");
	} else {
		printf("Tie!\n");
	}
}

// Alternate turns between computer and player till game over
void playGame(Game* aGame) {
	unsigned int pos;
	while(!aGame->over){
		if (aGame->computerTurn){ // computer play
			computerPlay(aGame);
			aGame->computerTurn = false;
		} else { // player play
			getMove(&pos);
			while(!validMove(aGame->gameSlate, pos)) {
				printf("Invalid move!\n");
				getMove(&pos);
			}
			playMove(aGame, PLAYER_PAWN, pos);
			aGame->computerTurn = true;
		}
		displaySlate(aGame->gameSlate);
	}	
}

// iterate all possible moves and play the best move
void computerPlay(Game* aGame) {
	int i, bestMove = -1;
	MoveScore score, bestScore;
	memset(&bestScore, 0, sizeof(MoveScore));
	for (i=0;i<SLATE_SIZE*SLATE_SIZE;i++) {
		if (aGame->gameSlate[i] != BLANK_CHAR) continue;
		if (bestMove < 0) bestMove = i;
		if (evaluateMove(aGame, i, &score)) { //if it's a winning move
			playMove(aGame, COMPUTER_PAWN, i);
			return;
		}
		if ((!bestScore.saving && score.saving) ||
			score.extent > bestScore.extent ||
			(score.extent == bestScore.extent && score.block > bestScore.block) ||
			(score.extent == bestScore.extent && score.block == bestScore.block &&
			 score.increment > bestScore.increment) ) {
			bestScore = score;
#ifdef DEBUG
			printf("found better score at %u: %u,%u,%u,%u\n",
			       i, bestScore.saving, bestScore.block, bestScore.extent, bestScore.increment);
#endif
			bestMove = i;
		}
	}
	if (validMove(aGame->gameSlate, bestMove)) {
		playMove(aGame, COMPUTER_PAWN, bestMove);
	} else {
#ifdef DEBUG
		printf("no valid move found!\n");
#endif
	}
}

int main(int argc, char* argv[]){
	char inputChar;
	while (inputChar != 'n') {
		startNewGame(&TheGame);
		playGame(&TheGame);
		printf("One more game?(n|y)");
		scanf(" %[ny]", &inputChar);
		getchar();
	}
	return 0;
}
