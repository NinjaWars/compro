<?php
require_once('combatrendering.php'); // All output rendering & displaying should go here.
require_once('combat.php'); // The core combat lib.

// =================== Start of procedural stuff ==========================

$new = true;

if ($new)
{
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
else
{
	$combat = unserialize(); // Currently unused...
}


display_intro(); // Intro explanation to the fight...

while (!$combat->isComplete())
{
	// *** Prompt for input ***
	echo "Enter attack sequence: [0-6] > ";

	// *** Read input ***
	$line = trim(fgets(STDIN));
	$lineLength = strlen($line);

	// *** Validate input ***
	if (preg_match('/[^0-6]/', $line))
	{
		echo "Your sequence can only contain numbers 0 - 6!\n";
	}
	else if ($lineLength < SEQ_MIN || $lineLength > SEQ_MAX)
	{
		echo "Your sequence must be between ".SEQ_MIN." and ".SEQ_MAX." moves!\n";
	}
	else
	{	// *** Send input into combat system ***
		$combat->setOffense(str_split($line));

		// *** Pump combat engine ***
		$combat->strife();
		
		// For now, render the results of each sequence input in a group...
		echo render_combat($combat->outcome());
	}
}

// *** Report combat result ***





echo render_combat($combat->outcome()); // Probably redundant to have this final render since all the rendering will be done already above.

echo render_win_loss($player1); // Display the win, loss, status effects, gold/rewards, etc.

?>
