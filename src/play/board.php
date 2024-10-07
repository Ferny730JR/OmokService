<?php
class Board {
    private $size;
    private $board; // 0 is empty, 1 is player, 2 is computer
    private $movesMade;

    function __construct($size = 15) {
        $this->size = $size;
        $this->board = array_fill(0, $size, array_fill(0, $size, 0));
        $this->movesMade = 0;
    }

    public function getSize() {
        return $this->size;
    }

    public function isEmpty($x, $y) {
        return $this->board[$x][$y] === 0;
    }

    public function isOccupied($x, $y) {
        return $this->board[$x][$y] !== 0;
    }

    public function isFull() {
        return $this->movesMade === pow($this->size, 2);
    }

    public function getPlayer($x, $y) {
        return $this->board[$x][$y];
    }

    public function makeMove($x, $y, $player) {
        $this->board[$x][$y] = $player;
        $this->movesMade++;
    }

    public function undoMove($x, $y) {
        $this->board[$x][$y] = 0;
        $this->movesMade--;
    }

    public function movesMade() {
        return $this->movesMade;
    }

    public function adjacentMoves() {
        $adjacentMoves = [];
        $directions = [[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]]; // 8 possible directions
    
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if($this->isOccupied($row, $col))
                    continue;
                foreach ($directions as $direction) {
                    $newRow = $row + $direction[0];
                    $newCol = $col + $direction[1];
                    if ($this->isWithinBounds($newRow, $newCol) && $this->isOccupied($newRow, $newCol)) {
                        $adjacentMoves[] = [$row, $col];
                        break;
                    }
                }
            }
        }
    
        return $adjacentMoves;
    }
    
    public function isWithinBounds($x, $y) {
        return $x >= 0 && $x < $this->size && $y >= 0 && $y < $this->size;
    }
    
}
?>
