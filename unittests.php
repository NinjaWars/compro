<?php
require_once('combatrendering.php'); // All output rendering & displaying should go here.
require_once('combat.php'); // The core combat system
// Test assertions against the combat system.

// Def format is space separated numbers
// Attack format is no-space numbers
// Win will be true if you expect the attacker to win, just for complete clarity.
$expectations = array(
	array(
	'def'=>'13 13 13',
	'att'=>'000',
	'win'=>true // Strike high vs. block low, should be a certain win for attacker.
	),
	array(
	'def'=>'13 13 13',
	'att'=>'222',
	'win'=>false // Strike low vs. block low, should be a certain misses for the attacker.
	),
	array(
	'def'=>'11 11 11',
	'att'=>'555',
	'win'=>false // Strike jump high vs. block hi, should be all reversal.
	),
	array(
	'def'=>'11',
	'att'=>'5',
	'win'=>false // Single strike jump high vs. block hi, should be a reversal.
	),
	array(
	'def'=>'12 12 12 12 12 12 12 12 12 12',
	'att'=>'000',
	'win'=>true
	),
	array(
	'def'=>'11 11 11 11 11 11 11 11 11 11',
	'att'=>'222', 
	'win'=>true // Strike low against a block high, should probably generally win.
	),
	array(
	'def'=>'11 11 11 11 11 11 11 11 11 11',
	'att'=>'111',
	'win'=>true // Strike mid, block hi, attacker wins.
	),
	array(
	'def'=>'11 11 11 11 11 11 11 11 11 11',
	'att'=>'11111111111111111', // Long repeats, should probably lose.
	'win'=>false
	),
);

$new = true;
if ($new){
	// Set up initial info as normal, and then just clone the objects for reuse.
	$player1 = new CombatActor();
	$player1->setOffense(array());
	$player1->setDefense(array());
	$player1->setHP(25);

	$player2 = new CombatActor();
	$player2->setOffense(array());
	$player2->setDefense(array());
	$player2->setHP(25);

	$combat = new CombatSession($player1, $player2);
}

// Whatever the basic win conditions for a round are, they can be added to here as time goes on.
function is_a_win($outcome){
	$spar = reset($outcome);
	return $spar['attacker_hp'] > $spar['defender_hp'];
}

// Wrapper just for clarity.
function is_a_loss($outcome){
	return !is_a_win($outcome);
}

// hack for echoing out readable messages on failed tests, this is included in php 5.4 but we're not there yet.
function assert_a_match($result, $message, $test_number){
	if(!(bool)$result){ // Test failed.
		echo 'Test number ['.$test_number.'] failed: '.$message;
	}
	return (bool)$result;
}


// Test a single expectation, made it a function to prevent sharing of scope.
function testz($expectation, $player1, $player2, &$test_count, &$successful_test_count){ // Test a single expectation.
	// Run the first battery of tests.

	$att = clone $player1; // Clone initial setup.
	$def = clone $player2; //  Clone defender.
	$att->setOffense(str_split($expectation['att']));
	$def->setDefense(explode(' ', $expectation['def']));
	$att->setDefense(explode(' ', '11 11 11 11 11 11 11 11 11 11')); // Just for reliability, set the attackers defense sequence to be all 11s every time.
	$combat = new CombatSession($att, $def);
	$combat->setOffense(str_split($expectation['att']));
	$combat->strife();
	$outcome = $combat->outcome();
	if($expectation['win']){
		$pass = assert_a_match(is_a_win($outcome), "Loss when there should have been a win\n", $test_number=$test_count+1);
	} else {
		$pass = assert_a_match(is_a_loss($outcome), "Win when there should have been a loss\n", $test_number=$test_count+1);
	}
	if($pass){
		$successful_test_count++;
	}
	$test_count++;
}


// This code is all ugly and all, but it's only while prototyping.

$test_count = 0;
$successful_test_count = 0;
// Run a test against the exact same set of input 100 times
$continue = 100; // Test the exact same input 100 times.
while($continue>0){
	$expectation = reset($expectations);
	testz($expectation, $player1, $player2, &$test_count, &$successful_test_count);
	$continue--;
}
echo "Tests:
$successful_test_count out of $test_count passed.\n\n";

// Reset counters and test the other sequences.
$test_count = 0;
$successful_test_count = 0;
echo "\nBeginning tests of different sequences.\n";
// I don't expect any variations of the other sequences above to work until the while loop tests become consistent.
foreach($expectations as $expectation){
	testz($expectation, $player1, $player2, &$test_count, &$successful_test_count);
}






echo "\nTests:
$successful_test_count out of $test_count passed.";

// TODO: Test longer, whole-combat sets.  These can be simple, or complex & based on playtests that were fun.  Of course, the more complex, the trickier it will be to preserve the behavior.
// TODO: Make expectations of the relationships between the moves that we want.
// Future tests: Make expectations on weapons, armor, items and their effect on the fight.

?>
