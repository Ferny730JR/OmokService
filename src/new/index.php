<?php

$strategies = array("smart", "random");

/* Check if a strategy was entered */
if(!array_key_exists('strategy', $_GET)) {
    $result = array("response" => false, "reason" => "Strategy not specified");
    echo json_encode($result);
    exit;
}

/* Strategy was entered, test it */
$strategy = $_GET['strategy'];
$strategy = strtolower($strategy);

/* Check if strategy that was entered is a valid strategy */
if(!in_array($strategy, $strategies)) {
    $result = array("response" => false, "reason" => "Unknown Strategy");
    echo json_encode($result);
    exit;
}

/* Get PID and output it */
$pid = uniqid();
$result = array("response" => true, "pid" => $pid);
echo json_encode($result);

/* Begin storing game data in writable */
$file_Name = "../writable/$pid";
$file = fopen("$file_Name", "w") or die("Unable to open file to store game data");
fputs($file, json_encode(array('pid' => $pid, 'strategy' => $strategy, 'player' => [], 'computer' => [])));
fclose($file);

?>
