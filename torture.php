<?php
// torture.php - tortue an Omok web service by performing
// various tests, esp. error handling. 
// Requires PHP version 5.3.3 or higher

// set to the base address (URL) of your Omok web service
//$home = "https://cssrvlab01.utep.edu/Classes/cs3360Cheon/<username>"
// $home = "https://www.cs.utep.edu/cheon/cs3360/project/omok/";
//$home = "http://localhost:8000/";
$home = "http://localhost:3000/src/";

$strategies = array(); // strategies supported by the web service under test
$size = 15;  // size of the board

runTests();

// same as: testcases.php

/** Test info: {"size":15,"strategies":["Smart","Random"]}. */
function testInfo() {
    global $strategies;
    global $size;
    $TAG = "I1";
    $response = visitInfo();
    //var_dump($response);
    if ($response) {
        $info = json_decode($response);
        if ($info != null) {
            $dim = property($info, 'size');
            assertTrue(isSet($dim) && $dim == $size, "$TAG-1");
            $strategies = property($info, 'strategies');
            assertTrue(isSet($strategies) && is_array($strategies)
                && sizeof($strategies) >= 2, "$TAG-2");
            return;
        }
    }
    fail("$TAG-3");
}

/** helper to retrieve a property of an object, or null. */
function property($obj, $property) {
    if (is_object($obj) && property_exists($obj, $property)) {
        return $obj->{$property};
    }
    return null;
}

/** Test: all strategies. Must be called after testInfo(). */
function testNew1() {
    $TAG = "N1";
    global $strategies;
    if (!is_array($strategies)) {
        assertTrue(false, "$TAG-1");
        return;
    }
    assertTrue(sizeof($strategies) > 0, "$TAG-2");
    foreach ($strategies as $s) {
        $response = visitNew($s);
        checkNewResponse($response, true, "$TAG-3");
    }
}

/** Test: strategy not specified. */
function testNew2() {
    $response = visitNew();
    checkNewResponse($response, false, "N2");
}

/** Test: unknown strategy. */
function testNew3() {
    $response = visitNew('Strategy' . uniqid()); // invalid strategy
    checkNewResponse($response, false, "N3");
}

/** Test: no pid specified. */
function testPlay1() {
    $response = visitPlay();
    //var_dump($response);
    checkPlayResponse($response, false, "P1");
}

/** Test: no move specified. */
function testPlay2() {
    $response = visitPlay(createGame());
    //var_dump($response);
    checkPlayResponse($response, false, "P2");
}

/** Test: unknown pid. */
function testPlay3() {
    $response = visitPlay('pid-' . uniqid(), 1, 1);
    //var_dump($response);
    checkPlayResponse($response, false, "P3");
}

/** Test: move not well-formed. */
function testPlay4_0() {
    $response = visitPlay(createGame(), null, null);
    //var_dump($response);
    checkPlayResponse($response, false, "P4_0");
}

/** Test: move not well-formed. */
function testPlay4() {
    $response = visitPlay(createGame(), 10, null);
    //var_dump($response);
    checkPlayResponse($response, false, "P4");
}

/** Test: move not well-formed. */
function testPlay5() {
    $response = visitPlay(createGame(), null, 10);
    //var_dump($response);
    checkPlayResponse($response, false, "P5");
}

/** Test: invalid move coordinate, x. */
function testPlay6() {
    $response = visitPlay(createGame(), -1, 5);
    //var_dump($response);
    checkPlayResponse($response, false, "P6");
}

/** Test: invalid move coordinate, x. */
function testPlay6_1() {
    $response = visitPlay(createGame(), 100, -5);
    //var_dump($response);
    checkPlayResponse($response, false, "P6_1");
}


/** Test: invalid move coordinate, y. */
function testPlay7() {
    global $size;
    $response = visitPlay(createGame(), 5, $size);
    //var_dump($response);
    checkPlayResponse($response, false, "P7");
}

/** Test: already placed. */
function testPlay8() {
    $TAG = "P8";
    $pid = createGame();
    $response = visitPlay($pid, 5, 5);
    //var_dump($response);
    checkPlayResponse($response, true, "$TAG-1");
    $response = visitPlay($pid, 5, 5);
    //var_dump($response);
    checkPlayResponse($response, false, "$TAG-2");
}


/** Test: valid move. */
function testPlay9() {
    $response = visitPlay(createGame(), 0, 0);
    //var_dump($response);
    checkPlayResponse($response, true, "P9");
}

/** Test: valid move. */
function testPlay10() {
    global $size;
    $maxIndex = $size - 1;
    $response = visitPlay(createGame(), $maxIndex, $maxIndex);
    checkPlayResponse($response, true, "P10");
}

/** Test: play response - ack of the player's move*/
function testPlay11() {
    $TAG = "P11";
    $response = visitPlay(createGame(), 3, 4);
    //var_dump($response);
    $json = json_decode($response);
    $ackMove = property($json, 'ack_move');
    assertTrue(isSet($ackMove), "$TAG-1");
    $x = property($ackMove, 'x');
    assertTrue(isSet($x) && $x == 3, "$TAG-2");
    $y = property($ackMove, 'y');
    assertTrue(isSet($x) && $y == 4, "$TAG-3");
    $isWin = property($ackMove, 'isWin');
    assertTrue(isSet($isWin) && !$isWin, "$TAG-4");
    $isDraw = property($ackMove, 'isDraw');
    assertTrue(isSet($isDraw) && !$isDraw, "$TAG-5");
    $row = property($ackMove, 'row');
    assertTrue(isSet($row) && is_array($row) && empty($row), "$TAG-6");
}

/** Test: play response - computer's move */
function testPlay12() {
    global $size;
    $TAG = "12";
    $response = visitPlay(createGame(), 4, 3);
    //var_dump($response);
    $json = json_decode($response);
    $move = property($json, 'move');
    assertTrue(isSet($move), "$TAG-1");
    $x = property($move, 'x');
    assertTrue(isSet($x) && $x >= 0 && $x < $size, "$TAG-2");
    $y = property($move, 'y');
    assertTrue(isSet($y) && $y >= 0 && $y < $size, "$TAG-3");
    $isWin = property($move, 'isWin');
    assertTrue(isSet($isWin) && !$isWin, "$TAG-4");
    $isDraw = property($move, 'isDraw');
    assertTrue(isSet($isDraw) && !$isDraw, "$TAG-5");
    $row = property($move, 'row');
    assertTrue(isSet($row) && is_array($row) && empty($row), "$TAG-6");
}

/** Test: partial game - place several stones. */
function testPlay13() {
    global $size;
    $TAG = "P13";
    $pid = createGame();
    $moves = array();
    for ($i = 0; $i < 4; $i++) {
        // pick an arbitray, empty place
        do {
            $x = rand(0, $size - 1);
            $y = rand(0, $size - 1);
        } while (in_array("$x,$y", $moves));
        $moves[] = "$x,$y";
        
        $response = visitPlay($pid, $x, $y);
        //var_dump($response);
        $json = json_decode($response);
        assertTrue (property($json, 'response'), "$TAG-1");
        $move = property($json, 'move'); // computer move
        assertTrue (isset($move), "$TAG-2");
        $x = property($move, 'x');
        $y = property($move, 'y');
        assertTrue (isset($x) && isset($y), "$TAG-3");
        $moves[] = "$x,$y";
    }
}

/** Test: concurrent games. */
function testPlay14() {
    $TAG = "P14";
    $g1 = createGame();
    play($g1, 1, 1, true, "$TAG-1");
    $g2 = createGame();
    play($g2, 1, 1, true, "$TAG-2");
    assertTrue($g1 != $g2, "$TAG-3"); // different play Ids.
}


//- helper methods

function cs_file_get_contents($url) {
    // To fix an issue with https on CS server
    // $response = @file_get_contents($url);
    $arrContextOptions = [
        "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
    ];
    $response = @file_get_contents($url, false,
        stream_context_create($arrContextOptions));
    return $response;
}

function visitInfo() {
    global $home;
    return cs_file_get_contents($home . "/info/index.php");
}

function visitNew($strategy = null) {
    global $home;
    $query = '';
    if (!is_null($strategy)) {
        $query = '?strategy=' . $strategy;
    }
    return cs_file_get_contents($home . "/new/index.php" . $query);
}

function checkNewResponse($response, $expected, $msg) {
    if ($response) {
        $json = json_decode($response);
        if ($json != null) {
            $r = property($json, 'response');
            assertTrue(isSet($r) && $r == $expected, $msg);
            if ($expected) {
                $pid = property($json, 'pid');
                assertTrue(isSet($pid), $msg);
            }
            return;
        }
    }
    fail($msg);
}

function createGame() {
    global $strategies;
    if (!is_array($strategies)) {
        assertTrue(false, "G-1");
        return;
    }
    $strategy = "Random";
    if (count($strategies) > 0) {
        $strategy = $strategies[0];
    }
    $response = visitNew($strategy);
    $json = json_decode($response);
    return property($json, 'pid');
}

function play($pid, $x, $y, $ok, $tag) {
    $response = visitPlay($pid, $x, $y);
    checkPlayResponse($response, $ok, $tag);
}

function visitPlay($pid = null, $x = null, $y = null) {
    global $home;
    $query = '';
    if (!is_null($pid)) {
        $query = '?pid=' . $pid;
    }
    if (!is_null($x)) {
        $query = $query . (strlen($query) > 0 ? '&' : '?');
        $query = $query . 'x=' . $x;
    }
    if (!is_null($y)) {
        $query = $query . (strlen($query) > 0 ? '&' : '?');
        $query = $query . 'y=' . $y;
    }
    return cs_file_get_contents($home . "/play/index.php" . $query);
}

function checkPlayResponse($response, $expected, $msg) {
    if ($response) {
        $json = json_decode($response);
        if ($json != null) {
            $r = property($json, 'response');
            assertTrue(isSet($r) && $r == $expected, $msg);
            if ($expected) {
                $ack = property($json, 'ack_move');
                assertTrue(isSet($ack), $msg);
            }
            return;
        }
    }
    fail($msg);
}

//---------------------------------------------------------------------
// Simple testing framework
//---------------------------------------------------------------------

/** Run all user-defined functions named 'test*'. */
function runTests($webOut = false) {
    $count = 0;
    $prefix = "test";
    $functions = get_defined_functions ();
    $names = $functions ['user'];
    foreach ($names as $name)  {
        if (substr($name, 0, strlen($prefix)) === $prefix) {
            $count ++;
            echo ".";
            call_user_func($name);
        }
    }
    summary($count, fail('', false), $webOut);
}

function assertTrue($expr, $msg) {
    if (!$expr) {
        fail($msg);
    }
}

function fail($msg, $report = true) {
    static $count = 0;
    static $tested = array();
    
    if ($report) {
        $prefix = explode('-', $msg);
        $testId = $prefix[0];  // e.g., P1 from P1-1
        if (!in_array($testId, $tested)) {
            $tested[] = $testId;
            $count++;
            echo "F($msg)";
        }
    }
    return $count;
}

function summary($total, $failure, $webout) {
    $sucess = $total - $failure;
    echo "\n";
    if ($webout) {
        echo "<br/>";
    }
    echo "Failed/Total: $failure/$total (passed: $sucess)\n";
}

?>