<?php
require_once "game.php";

if(!defined('PLAYER')) define('PLAYER', 1);
if(!defined('COMPUTER')) define('COMPUTER', 2);

/* Check if a PID isn't entered */
if (!array_key_exists('pid', $_GET)) {
    echo json_encode(array("response" => false, "reason" => "PID not specified"));
    exit;
}

/* Get the PID provided */
$pid = $_GET['pid'];

/* Try and open game contents from PID */
$file = @file_get_contents("../writable/$pid");
if($file === false) {
    echo json_encode(array("response" => false, "reason" => "Unknown pid"));
    exit;
}

/* If the PID does not match the given one */
$game_data = json_decode($file);
if ($pid != $game_data->pid) {
    echo json_encode(array("response" => false, "reason" => "Unknown pid"));
    exit;
}

/* Check if the moves are not specified */
if (!array_key_exists('x', $_GET)) {
    echo json_encode(array("response" => false, "reason" => "x not specified"));
    exit;
}

/* Check if the moves are not specified */
if (!array_key_exists('y', $_GET)) {
    echo json_encode(array("response" => false, "reason" => "y not specified"));
    exit;
}

/* If the x value is not within the range */
$x_move = $_GET['x'];
if (filter_var($x_move, FILTER_VALIDATE_INT) === false || $x_move < 0 || $x_move > 14) {
    echo json_encode(array("response" => false, "reason" => "Invalid x coordinate - $x_move"));
    exit;
}

/* If the y value is not within the range */
$y_move = $_GET['y'];
if (filter_var($y_move, FILTER_VALIDATE_INT) === false || $y_move < 0 || $y_move > 14) {
    echo json_encode(array("response" => false, "reason" => "Invalid y coordinate - $y_move"));
    exit;
}

$game = new Game();
$game->loadGame($game_data);

/* If the player's move is invalid */
if (!$game->makeMove($x_move, $y_move, PLAYER)) {
    echo json_encode(array("response" => false, "reason" => "Place not empty, ($x_move, $y_move)"));
    exit;
}

/* Check if the player wins or the game draws */
if ($game->isWonBy(PLAYER) || $game->isDraw()) {
    $result = array('response' => true, 'ack_move' => $game->json_response(PLAYER, $x_move, $y_move));
    echo json_encode($result);
    exit;
}

/* Otherwise, let the computer make a move and check if it won */
$computer_move = $game->makeComputerMove();
if($game->isWonBy(COMPUTER) || $game->isDraw()) {
    $result = array(
        'response' => true,
        'ack_move' => $game->json_response(PLAYER, $x_move, $y_move),
        'move' => $game->json_response(COMPUTER, $computer_move[0], $computer_move[1])

    );
    echo json_encode($result);
    exit;
}

/* No winner, continue and update the game */
$result = array(
    'response' => true,
    'ack_move' => $game->json_response(PLAYER, $x_move, $y_move),
    'move' => $game->json_response(COMPUTER, $computer_move[0], $computer_move[1])
);
echo json_encode($result);

/* Save the game changes */
$game->saveGame();
?>
