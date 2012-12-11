<?php

function display_intro(){
	echo <<<THEND
	----------------------------------------
	Hello there! Welcome to the combat prototype last updated 2012-12-10. Aesthetically, imagine a fighting game like Street Fighter.

	Both characters have 25 hit points. Each hit takes away 1 hit point.

	Mechanically, there are 2 goals:
		1) Hit your opponent 25 times
		2) Get sweet combos

	You have 7 moves, 0 - 6, and you attack by stringing those numbers together in a sequence like so:
		> 1244554256623

	If you hit your opponent 3 or more times without missing, you'll get a combo! ("C-c-c-combo!!!")

	Each number corresponds to a move like STRIKE-HI. Your opponent has a set defensive pattern like BLOCK-HI, BLOCK-LO, DUCK, JUMP. You don't know what the pattern is or how long it is. As you fight, pay attention to the numbers on the left. Those are the defensive moves. Plan your attack sequences to predict the moves of your opponent.

	Your attack ends when you miss twice. ("Miss!")

	If your last attack is blocked ("Miss!"), your opponent will counter attack ("Counter-attack!"), so you always benefit from long attack sequences. (124563213)

	If your attack and your opponent's block are a reversal pair, your opponent will counter-attack ("Reversal!"). If you are lucky enough to block his first counter-attack, you will reverse the reversal! ("Double reversal!")

	You take damage during counter-attacks ("You get hit!"). Counter-attacks end when you are lucky enough to successfully block twice in a row ("You defend!") or your opponent's attack sequence ends ("Your opponent taunts you!").

	If you attack too predictably, your opponent will throw you for extra damage. ("Your opponent throws you....")

	--------------FIGHT!---------------


THEND;
}

// Render as many individual lines of combat as required, with hitpoint change reporting.
function render_combat($combat_results){
	$res = '';
	$max_hp = 25; // This is a temporary hack.
	foreach($combat_results as $spar){
		$res .= $spar['message'];
		$res .= '['.hp_percent($spar['attacker_hp'], $max_hp).'%] ------- ['.hp_percent($spar['defender_hp'], $max_hp).'%]'."\n";
	}
	return $res;
}

// Just get the hitpoints in percent...
function hp_percent($current, $max){
	return round(($current/$max)*100);
}

// Deal with wining, losing, status effects, rewards, etc.
function render_win_loss($player1){
	return "You ". ($player1->getHP() > 0 ? 'win' : 'lose'). "!\n";
}



?>
