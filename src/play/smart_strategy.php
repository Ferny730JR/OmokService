<?php
define('PLAYER', 1);
define('COMPUTER', 2);

require_once "move_strategy.php";
class SmartStrategy extends MoveStrategy {
    private $scoreFromPosition;

    public function __construct($board) {
        $this->board = $board;
        $this->scoreFromPosition = [
            '11111' => 30000000,
            '22222'=> -30000000,
            '011110'=> 20000000,
            '022220'=> -20000000,
            '011112'=> 50000,
            '211110'=> 50000,
            '022221'=> -50000,
            '122220'=> -50000,
            '01110'=> 30000,
            '02220'=> -30000,
            '011010'=> 15000,
            '010110'=> 15000,
            '022020'=> -15000,
            '020220'=> -15000,
            '001112'=> 2000,
            '211100'=> 2000,
            '002221'=> -2000,
            '122200'=> -2000,
            '211010'=> 2000,
            '210110'=> 2000,
            '010112'=> 2000,
            '011012'=> 2000,
            '122020'=> -2000,
            '120220'=> -2000,
            '020221'=> -2000,
            '022021'=> -2000,
            '01100'=> 500,
            '00110'=> 500,
            '02200'=> -500,
            '00220'=> -500
        ];
    }
    public function pickPlace() {
        /* Check if center is taken, otherwise take move next to center */
        if($this->board->movesMade() === 1) {
            if($this->board->isOccupied(7,7)) {
                $this->board->makeMove(8,7, COMPUTER);
                return array('x' => 8, 'y' => 7);
            } else {
                $this->board->makeMove(7,7, COMPUTER);
                return array('x' => 7, 'y' => 7);
            }
        }

        /* Calculate the next best move */
        $bestMove = [-1, -1];
        $bestEval = PHP_INT_MAX;

        /* Iterate over moves to be made */
        foreach($this->board->adjacentMoves() as $move) {

            /* Make the move and calculate the weight of the move */
            $this->board->makeMove($move[0], $move[1], COMPUTER);
            $moveEval = $this->minimax($this->board, 2, PLAYER);
            $this->board->undoMove($move[0], $move[1]);

            /* Computer, so try to minimize the evaluation */
            echo nl2br("move: ($move[0], $move[1]) = $moveEval\n");
            if($moveEval <= $bestEval) {
                $bestMove = [$move[0], $move[1]];
                $bestEval = $moveEval;
            }
        }

        echo nl2br("Best move: ($bestMove[0], $bestMove[1])\n");
        $this->board->makeMove($bestMove[0], $bestMove[1], COMPUTER);
        return array('x' => $bestMove[0], 'y' => $bestMove[1]);
    }

    private function minimax($board, $depth, $player) {
        $eval = $this->evaluate($board);
        if($depth === 0 || $board->isFull() || $eval >= 29000000 || $eval <= -29000000) {
            return $eval;
        }

        if($player === PLAYER) {
            return $this->getMax($board, $depth);

        /* Computer turn */
        } else {
            return $this->getMin($board, $depth);
        }
    }

    private function getMax($board, $depth) {
        $maxEval = PHP_INT_MIN;
        foreach($this->board->adjacentMoves() as $move) {
            $this->board->makeMove($move[0], $move[1], PLAYER);
            $curEval = $this->minimax($board, $depth - 1, COMPUTER);
            $this->board->undoMove($move[0], $move[1]);
            // echo "\t" . nl2br("P: ($move[0], $move[1]) = $curEval\n");
            $maxEval = max($curEval, $maxEval);
        }
        return $maxEval;
    }

    private function getMin($board, $depth) {
        $minEval = PHP_INT_MAX;
        foreach($this->board->adjacentMoves() as $move) {
            $this->board->makeMove($move[0], $move[1], COMPUTER);
            $curEval = $this->minimax($board, $depth - 1, PLAYER);
            $this->board->undoMove($move[0], $move[1]);
            // echo "\t" . nl2br("C: ($move[0], $move[1]) = $curEval\n");
            $minEval = min($curEval, $minEval);
        }
        return $minEval;
    }

    public function bestMoves($player) {
        $moveEvals = [];
        foreach($this->board->adjacentMoves() as $move) {
            $this->board->makeMove($move[0], $move[1], $player);
            $score = $this->evaluate($this->board);
            $this->board->undoMove($move[0], $move[1]);

            $moveEvals[] = ['move' => [$move[0], $move[1]], 'score' => $score];
        }
        if($player == COMPUTER) {
            usort($moveEvals, function($a, $b) {return $a['score']-$b['score'];});
        } else {
            usort($moveEvals, function($a, $b) {return $b['score']-$a['score'];});
        }
        return array_map(function($move) {return $move['move'];}, array_slice($moveEvals, 0, 10));
    }

    /**
     * Static evaluation of the board. If the evaluation is 0, that means that
     * neither the playe or the computer is winning. If above 0, it means the
     * player has an advantage. If below 0, it means the computer has an
     * advantage.
     */
    private function evaluate($board) {
        $score = 0;
    
        for($x = 0; $x<$this->board->getSize(); $x++) {
            for($y=0; $y<$this->board->getSize(); $y++) {
                $score += $this->getScore($x, $y);
            }
        }

        // /* Iterate over all possible rows, columns, and diagonals */
        // for ($i = 0; $i < $board->getSize(); $i++) {
        //     /* Evaluate rows and columns */
        //     $score += $this->evaluateLine($board, $i, 0, 0, 1, 5); // row
        //     $score += $this->evaluateLine($board, $i, 0, 0, 1, 6); // row
        //     $score += $this->evaluateLine($board, 0, $i, 1, 0, 5); // column
        //     $score += $this->evaluateLine($board, 0, $i, 1, 0, 6); // column
        // }
    
        // // Evaluate diagonals
        // for ($i = 0; $i < $board->getSize(); $i++) {
        //     $score += $this->evaluateLine($board, $i, 0, 1, 1, 5); // diagonal from left to right
        //     $score += $this->evaluateLine($board, $i, 0, 1, 1, 6); // diagonal from left to right
        //     $score += $this->evaluateLine($board, $i, 0, -1, 1, 5); // diagonal from right to left
        //     $score += $this->evaluateLine($board, $i, 0, -1, 1, 6); // diagonal from right to left
        // }
    
        return $score;
    }

    private function getScore($x, $y) {
        $score = 0;
        /* Check horizontal */
        $score += $this->evaluateLine($this->board, $x, $y, 0, 1, 5);
        $score += $this->evaluateLine($this->board, $x, $y, 0, 1, 6);

        /* Check vertical */
        $score += $this->evaluateLine($this->board, $x, $y, 1, 0, 5);
        $score += $this->evaluateLine($this->board, $x, $y, 1, 0, 6);

        /* Check diagonal left to right */
        $score += $this->evaluateLine($this->board, $x, $y, 1, 1, 5);
        $score += $this->evaluateLine($this->board, $x, $y, 1, 1, 6);

        /* Check diagonal right to elft */
        $score += $this->evaluateLine($this->board, $x, $y, 1, -1, 5);
        $score += $this->evaluateLine($this->board, $x, $y, 1, -1, 6);

        return $score;
    }
    
    private function evaluateLine($board, $startX, $startY, $deltaX, $deltaY, $length) {    
        $line = "";
        for ($i = 0; $i < $length; $i++) {
            $x = $startX + $i * $deltaX;
            $y = $startY + $i * $deltaY;
    
            if (!$board->isWithinBounds($x, $y)) {
                break; // Out of bounds
            }
    
            $player = $board->getPlayer($x, $y);
    
            if ($player == PLAYER) {
                $line = $line . "1";
            } elseif ($player == COMPUTER) {
                $line = $line . "2";
            } else {
                $line = $line . "0";
            }
        }
    
        if(array_key_exists($line, $this->scoreFromPosition))
            return $this->scoreFromPosition[$line];
        else return 0;
    }
    
}

?>
