<?php
require_once "move_strategy.php";
class RandomStrategy extends MoveStrategy {
    public function pickPlace() {
        $board = $this->board;

        // don't make move if board is full
        if($board->isFull())
            return NULL;

        // Make a random move within the board size
        $size = $board->getSize();
        do {
            $x = random_int(0, $size - 1);
            $y = random_int(0, $size - 1);
        } while($board->isOccupied($x, $y));
        $board->makeMove($x, $y, 2);

        // Return random move made
        return array('x' => $x, 'y' => $y);
    }
}
?>
