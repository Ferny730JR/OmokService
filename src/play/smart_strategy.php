<?php
define('PLAYER', 1);
define('COMPUTER', 2);

require_once "move_strategy.php";
class SmartStrategy extends MoveStrategy {
    private $hash5Score;
    private $hash6Score;

    public function __construct($board) {
        $this->board = $board;
        $this->hash5Score = [
            341 =>  50000000, // 11111
            682 => -50000000, // 22222
            84  =>     30000, // 01110
            168 =>    -30000, // 02220
            80  =>       500, // 01100
            20  =>       500, // 00110
            160 =>      -500, // 02200
            40  =>      -500  // 00220
        ];
        $this->hash6Score = [
            340  =>  20000000, // 011110
            680  => -20000000, // 022220
            342  =>     50000, // 011112
            2388 =>     50000, // 211110
            681  =>    -50000, // 022221
            1704 =>    -50000, // 122220
            324  =>     15000, // 011010
            276  =>     15000, // 010110
            648  =>    -15000, // 022020
            552  =>    -15000, // 020220
             86  =>      2000, // 001112
            2384 =>      2000, // 211100
            169  =>     -2000, // 002221
            1696 =>     -2000, // 122200
            2372 =>      2000, // 211010
            2324 =>      2000, // 210110
            278  =>      2000, // 010112
            326  =>      2000, // 011012
            1672 =>     -2000, // 122020
            1576 =>     -2000, // 120220
            553  =>     -2000, // 020221
            649  =>     -2000  // 022021
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
        foreach($this->bestMoves(PLAYER) as $move) {

            /* Make the move and calculate the weight of the move */
            $this->board->makeMove($move[0], $move[1], COMPUTER);
            $moveEval = $this->minimax($this->board, 2, PHP_INT_MIN, PHP_INT_MAX, PLAYER);
            $this->board->undoMove($move[0], $move[1]);

            /* Computer, so try to minimize the evaluation */
            if($moveEval <= $bestEval) {
                $bestMove = [$move[0], $move[1]];
                $bestEval = $moveEval;
            }
            // echo nl2br("Move ($move[0], $move[1]) = $moveEval\n");
        }

        $this->board->makeMove($bestMove[0], $bestMove[1], COMPUTER);
        return array('x' => $bestMove[0], 'y' => $bestMove[1]);
    }

    private function minimax($board, $depth, $alpha, $beta, $player) {
        $eval = $this->evaluate();
        if($depth === 0 || $board->isFull() || $eval >= 29000000 || $eval <= -29000000) {
            return $eval;
        }

        if($player === PLAYER) {
            return $this->getMax($board, $depth, $alpha, $beta);

        /* Computer turn */
        } else {
            return $this->getMin($board, $depth, $alpha, $beta);
        }
    }

    private function getMax($board, $depth, $alpha, $beta) {
        $maxEval = PHP_INT_MIN;
        foreach($this->bestMoves(PLAYER) as $move) {
            $this->board->makeMove($move[0], $move[1], PLAYER);
            $curEval = $this->minimax($board, $depth - 1, $alpha, $beta, COMPUTER);
            $this->board->undoMove($move[0], $move[1]);
            $maxEval = max($maxEval, $curEval);
            $alpha = max($alpha, $curEval);
            if($beta <= $alpha) {
                break;
            }
        }
        return $maxEval;
    }

    private function getMin($board, $depth, $alpha, $beta) {
        $minEval = PHP_INT_MAX;
        foreach($this->bestMoves(COMPUTER) as $move) {
            $this->board->makeMove($move[0], $move[1], COMPUTER);
            $curEval = $this->minimax($board, $depth - 1, $alpha, $beta, PLAYER);
            $this->board->undoMove($move[0], $move[1]);
            $minEval = min($curEval, $minEval);
            $beta = min($beta, $curEval);
            if($beta <= $alpha) {
                break;
            }
        }
        return $minEval;
    }

    public function bestMoves($player) {
        $moveEvals = [];
        foreach($this->board->adjacentMoves() as $move) {
            $this->board->makeMove($move[0], $move[1], $player);
            $score = $this->evaluate();
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
    private function evaluate() {
        $score = 0;

        /* Check horizontal. Origin (0,0) is at the top left of board */
        for($y=0; $y<$this->board->getSize(); $y++) {
            $hash_value = 0;
            for($x=0; $x<$this->board->getSize(); $x++) {
                $hash_value = (($hash_value << 2) | $this->board->getPlayer($x,$y)) & 0xfff;
                if($x>4 && array_key_exists($hash_value, $this->hash6Score)) {
                    $score += $this->hash6Score[$hash_value];
                }
                $hash_value &= 0x3ff;
                if($x>3 && array_key_exists($hash_value, $this->hash5Score)) {
                    $score += $this->hash5Score[$hash_value];
                }
            }
        }

        /* Check vertical. Origin (0,0) is at top left of board */
        for($x = 0; $x<$this->board->getSize(); $x++) {
            $hash_value = 0;
            for($y=0; $y<$this->board->getSize(); $y++) {
                $hash_value = (($hash_value << 2) | $this->board->getPlayer($x,$y)) & 0xfff;
                if($y>4 && array_key_exists($hash_value, $this->hash6Score)) {
                    $score += $this->hash6Score[$hash_value];
                }
                $hash_value &= 0x3ff;
                if($y>3 && array_key_exists($hash_value, $this->hash5Score)) {
                    $score += $this->hash5Score[$hash_value];
                }
            }
        }

        /* Check diagonal left to right, beginning at top left of board */
        for($x=0; $x<$this->board->getSize(); $x++) {
            $hash_value = 0;
            for($y=0; $y<=$x; $y++) {
                $i = $x-$y;
                $hash_value = (($hash_value << 2) | $this->board->getPlayer($i, $y)) & 0xfff;
                if($y>4 && array_key_exists($hash_value, $this->hash6Score)) {
                    $score += $this->hash6Score[$hash_value];
                }
                $hash_value &= 0x3ff;
                if($y>3 && array_key_exists($hash_value, $this->hash5Score)) {
                    $score += $this->hash5Score[$hash_value];
                }
            }
        }
        for($x=0; $x<$this->board->getSize()-2; $x++) {
            $hash_value = 0;
            for($y=0; $y<=$x; $y++) {
                $i = $x - $y;
                $hash_value = (($hash_value << 2) | $this->board->getPlayer($this->board->getSize()-$y-1, $this->board->getSize()-$i-1)) & 0xfff;
                if($y>4 && array_key_exists($hash_value, $this->hash6Score)) {
                    $score += $this->hash6Score[$hash_value];
                }
                $hash_value &= 0x3ff;
                if($y>3 && array_key_exists($hash_value, $this->hash5Score)) {
                    $score += $this->hash5Score[$hash_value];
                }
            }
        }

        /* Check diagonals right to left, beginning at the bottom left of the board */
        for($i = $this->board->getSize()-1; $i > 0; $i--) {
            $hash_value = 0;
            for($y = 0, $x=$i; $x <= $this->board->getSize()-1; $y++, $x++) {
                $hash_value = (($hash_value << 2) | $this->board->getPlayer($x, $y)) & 0xfff;
                if($y>4 && array_key_exists($hash_value, $this->hash6Score)) {
                    $score += $this->hash6Score[$hash_value];
                }
                $hash_value &= 0x3ff;
                if($y>3 && array_key_exists($hash_value, $this->hash5Score)) {
                    $score += $this->hash5Score[$hash_value];
                }
            }
        }
        for($i=0; $i<$this->board->getSize(); $i++) {
            $hash_value = 0;
            for($x=0, $y=$i; $y < $this->board->getSize(); $x++, $y++) {
                $hash_value = (($hash_value << 2) | $this->board->getPlayer($x, $y)) & 0xfff;
                if($x>4 && array_key_exists($hash_value, $this->hash6Score)) {
                    $score += $this->hash6Score[$hash_value];
                }
                $hash_value &= 0x3ff;
                if($x>3 && array_key_exists($hash_value, $this->hash5Score)) {
                    $score += $this->hash5Score[$hash_value];
                }
            }
        }
    
        return $score;
    }
}
?>
