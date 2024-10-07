<?php
require_once "board.php";
require_once "random_strategy.php";
require_once "smart_strategy.php";

class Game {
    private $board;
    private $pid;
    private $strategy;
    private $playerMoves;
    private $computerMoves;

    public function __construct() {
        $this->playerMoves = [];
        $this->computerMoves = [];
        $this->board = new Board(15);
    }

    public function loadGame($data) {
        // Load game state from the saved JSON data
        $this->pid = $data->pid;
        $this->strategy = $data->strategy;
        $this->playerMoves = $data->player;
        $this->computerMoves = $data->computer;

        // Update the board state based on the player's moves
        foreach ($this->playerMoves as $move) {
            $this->board->makeMove($move[0], $move[1], 1); // Player is 1
        }
        foreach ($this->computerMoves as $move) {
            $this->board->makeMove($move[0], $move[1], 2); // Computer is 2
        }
    }

    public function saveGame() {
        $filename = "../writable/$this->pid";
        $gameState = array(
            'pid' => $this->pid,
            'strategy' => $this->strategy,
            'player' => $this->playerMoves,
            'computer' => $this->computerMoves,
        );
        file_put_contents($filename, json_encode($gameState));
    }

    public function makeMove($x, $y, $player) {
        if($player == 1) {
            return $this->makePlayerMove($x, $y);
        } else if($player == 2) {
            return $this->makeComputerMove();
        } else {
            echo "Error! wrong player";
            exit;
        }
    }

    private function makePlayerMove($x, $y) {
        $x = (int)$x; /* make sure x and y are ints */
        $y = (int)$y;

        /* Check is this is a valid move */
        if ($x < 0 || $x >= $this->board->getSize() || $y < 0 || $y >= $this->board->getSize())
            return false;
        if ($this->board->isOccupied($x, $y))
            return false; // Position is already occupied

        /* Make the move and store it */
        $this->board->makeMove($x, $y, 1);
        $this->playerMoves[] = [$x, $y];

        return true;
    }

    public function makeComputerMove() {
        /* Choose either random or smart strategy */
        if ($this->strategy == 'random') {
            $strategy = new RandomStrategy($this->board);
        } else if ($this->strategy == 'smart') {
            $strategy = new SmartStrategy($this->board);
        }

        /* Make computer move and store it */
        $move = $strategy->pickPlace();
        if ($move === NULL) {
            return false; // No move possible (the board is full)
        }
        $this->computerMoves[] = [$move['x'], $move['y']];
        return [$move['x'], $move['y']];
    }


    public function isDraw() {
        return $this->board->isFull();
    }

    public function isWonBy($player) {
        for ($x = 0; $x < $this->board->getSize(); $x++) {
            for ($y = 0; $y < $this->board->getSize(); $y++) {
                $winningMoves = $this->checkWinningPosition($x, $y, $player);
                if (!empty($winningMoves)) {
                    return $winningMoves;
                }
            }
        }
        return [];
    }
    
    private function checkWinningPosition($x, $y, $player) {
        $horizontal = $this->checkDirection($x, $y, 0, 1, $player);
        if ($horizontal) {
            return $horizontal;
        }
    
        $vertical = $this->checkDirection($x, $y, 1, 0, $player);
        if ($vertical) {
            return $vertical;
        }
    
        $diagonalRight = $this->checkDirection($x, $y, 1, 1, $player);
        if ($diagonalRight) {
            return $diagonalRight;
        }
    
        $diagonalLeft = $this->checkDirection($x, $y, 1, -1, $player);
        if ($diagonalLeft) {
            return $diagonalLeft;
        }
    
        return []; // No winning position found in any direction
    }
    
    private function checkDirection($x, $y, $deltaX, $deltaY, $player) {
        $winningMoves = [];
        
        // Traverse in the specified direction (deltaX, deltaY)
        for ($i = 0; $i < 5; $i++) {
            $newX = $x + $i * $deltaX;
            $newY = $y + $i * $deltaY;
            
            // Check if the new coordinates are within the bounds of the board
            if ($newX >= 0 && $newX < $this->board->getSize() &&
                $newY >= 0 && $newY < $this->board->getSize() &&
                $this->board->isOccupied($newX, $newY) &&
                $this->board->getPlayer($newX, $newY) === $player) {
                $winningMoves[] = [$newX, $newY]; // Store winning move
            } else {
                break; // Stop checking if we hit an empty space or out of bounds
            }
        }
    
        // If we found 5 in a row, return the array of winning moves
        return count($winningMoves) === 5 ? $winningMoves : [];
    }

    public function json_response($player, $x, $y) {        
        $winningRow = $this->isWonBy($player);
        $isWin = !empty($winningRow); // True if the winning row is found

        $response = array(
            "x" => (int)$x, 
            "y" => (int)$y, 
            "isWin" => $isWin, 
            "isDraw" => $this->isDraw(), 
            "row" => $isWin ? array_merge(...$winningRow) : []
        );
        return $response;
    }
}

?>