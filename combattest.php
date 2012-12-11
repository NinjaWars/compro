<?php
define('STRIKE_HI', 0);
define('STRIKE_MID', 1);
define('STRIKE_LO', 2);
define('STRIKE_DUCK_LO', 3);
define('STRIKE_DUCK_MID', 4);
define('STRIKE_JUMP_HI', 5);
define('STRIKE_JUMP_MID', 6);
define('GUARD', 7);
define('REVERSAL_HI', 8);
define('REVERSAL_MID', 9);
define('REVERSAL_LO', 10);
define('BLOCK_HI', 11);
define('BLOCK_MID', 12);
define('BLOCK_LO', 13);
define('BLOCK_DUCK_LO', 14);
define('JUMP', 15);
define('DUCK', 16);
define('SEQ_MIN', 3);
define('SEQ_MAX', 255);

class CombatSession
{
	public static $STRIFE_MATRIX = array(
		STRIKE_HI => array(
			BLOCK_HI => 0,
			BLOCK_MID => 1,
			BLOCK_LO => 1,
			BLOCK_DUCK_LO => 0,
			JUMP => 1,
			DUCK => 2
		),
		STRIKE_MID => array(
			BLOCK_HI => 1,
			BLOCK_MID => 2,
			BLOCK_LO => 1,
			BLOCK_DUCK_LO => 0,
			JUMP => 1,
			DUCK => 1
		),
		STRIKE_LO => array(
			BLOCK_HI => 1,
			BLOCK_MID => 1,
			BLOCK_LO => 0,
			BLOCK_DUCK_LO => 2,
			JUMP => 0,
			DUCK => 1
		),
		STRIKE_DUCK_MID => array(
			BLOCK_HI => 1,
			BLOCK_MID => 0,
			BLOCK_LO => 2,
			BLOCK_DUCK_LO => 1,
			JUMP => 0,
			DUCK => 1
		),
		STRIKE_DUCK_LO => array(
			BLOCK_HI => 1,
			BLOCK_MID => 1,
			BLOCK_LO => 0,
			BLOCK_DUCK_LO => 0,
			JUMP => 0,
			DUCK => 1
		),
		STRIKE_JUMP_HI => array(
			BLOCK_HI => 2,
			BLOCK_MID => 1,
			BLOCK_LO => 1,
			BLOCK_DUCK_LO => 0,
			JUMP => 1,
			DUCK => 0
		),
		STRIKE_JUMP_MID => array(
			BLOCK_HI => 0,
			BLOCK_MID => 0,
			BLOCK_LO => 1,
			BLOCK_DUCK_LO => 1,
			JUMP => 2,
			DUCK => 1
		)
	);

	private $m_actors = array();
	private $m_queue  = array();
	private $m_points = 0;
	private $m_defenseIndex = 0;
	private $m_offenseIndex = 0;

	public function __construct($p_attacker, $p_defender)
	{
		$p_defender->setDefense($this->createDefense());
		$p_attacker->setDefense($this->createDefense());

		$this->m_actors['attacker'] = $p_attacker;
		$this->m_actors['defender'] = $p_defender;
	}

	private function createDefense()
	{
		return $this->createSequence(rand(10, 15), 11, 16);
	}

	private function createOffense()
	{
		return $this->createSequence(rand(6, 10), 0, 6);
	}

	private function createSequence($p_length, $p_min, $p_max)
	{
		$sequence = array();

		for ($i = 0;$i < $p_length; $i++)
		{
			$sequence[] = rand($p_min, $p_max);
		}

		return $sequence;
	}

	public function setOffense($p_moves)
	{
		$this->m_actors['attacker']->setOffense($p_moves);
	}

	public function strife()
	{
		$breaker       = 0;
		$comboCounter  = 0;
		$reversalMulti = 0;
		$repeat        = 0;
		$broken        = false;

		$offenseSequence = $this->m_actors['attacker']->getOffense();
		$defenseSequence = $this->m_actors['defender']->getDefense();
		$end             = count($offenseSequence);
		$pattern         = count($defenseSequence);

		for ($this->m_offenseIndex = 0; $this->m_offenseIndex < $end; $this->m_offenseIndex++, $this->m_defenseIndex++)
		{
			$offenseMove = $offenseSequence[$this->m_offenseIndex % $end];
			$defenseMove = $defenseSequence[$this->m_defenseIndex % $pattern];

			if ($this->m_offenseIndex > 1)
			{
				if ($offenseMove == $offenseSequence[($this->m_offenseIndex-1) % $end])
				{
					$repeat = 2;

					if ($offenseMove == $offenseSequence[($this->m_offenseIndex-2) % $end])
					{
						$repeat = 3;
					}
				}
				else
				{
					$repeat = 0;
				}
			}

			$strife = $this->resolve($offenseMove, $defenseMove);

			if ($strife == 1)
			{
				$this->m_points += $strife;
				$this->m_actors['defender']->setHP($this->m_actors['defender']->getHP()-1);

				echo $defenseMove, " ";
				echo "Hit!\n";

				$breaker = 0;
				$comboCounter++;
			}
			else
			{
				if ($comboCounter > 2)
				{
					if ($reversalMulti)
					{
						echo "C-c-c-combo x$comboCounter X".($reversalMulti+1)."!!!\n";
					}
					else
					{
						echo "C-c-c-combo x$comboCounter!!!\n";
					}
				}

				$breaker += $strife+1;
			}

			if ($breaker > 0 && $repeat)
			{
				$repeat = 0;

				// *** THROW ***

				$broken = true;
				echo $defenseMove, " Your opponent throws you; you land hard!\n";
				$this->m_actors['attacker']->setHP($this->m_actors['attacker']->getHP()-2);
			}
			else if ($breaker > 1)
			{
				$broken = true;
				echo $defenseMove, " ";

				if (($strife == 2) || (($this->m_offenseIndex + 1) >= $end))
				{
					echo (($strife == 2) ? "Reversal" : "Counter-attack"), "!!!\n";

					$reverseOffense = $this->createOffense();
					$reverseDefense = $this->m_actors['attacker']->getDefense();
					$reverseEnd     = count($reverseOffense);
					$reversePattern = count($reverseDefense);

					$this->m_actors['defender']->setOffense($reverseOffense);

					$r = 0;
					$reverseBreaker = 0;

					for ($q = 0;$q < $reverseEnd; $q++, $r++)
					{
						$offenseMove = $reverseOffense[$q % $reverseEnd];
						$defenseMove = $reverseDefense[$r % $reversePattern];

						$strife = $this->resolve($offenseMove, $defenseMove);

						if ($strife == 1)
						{
							$comboCounter = 0;
							$reverseBreaker = 0;
							$this->m_actors['attacker']->setHP($this->m_actors['attacker']->getHP()-1);
							echo "You get hit!\n";
						}
						else
						{
							$reverseBreaker++;

							if ($r == 0)
							{
								echo "Double reversal!!!\n";
								$reversalMulti++;
								$broken = false;
								break;
							}
							else if ($reverseBreaker > 1)
							{
								$reversalMulti = 0;
								echo "You defend!\n";
								break;
							}
							else
							{
								$reversalMulti = 0;
								echo "You block...\n";
							}
						}
					}

					if ($q == $reverseEnd)
					{
						$reversalMulti = 0;
						echo "Your opponent steps back and taunts you!!\n";
					}
				}
				else if ($comboCounter > 2)
				{
					$reversalMulti = 0;
					echo "C-c-c-combo breaker!!!!!!!\n";
				}
				else
				{
					$reversalMulti = 0;
					$comboCounter = 0;
					echo "Miss!\n";
				}
			}
			else if ($breaker > 0)
			{
				$reversalMulti = 0;
				$comboCounter = 0;
				echo $defenseMove, " ";
				echo "Miss!\n";
			}

			if ($broken) break;
		}

		if ($comboCounter > 2)
		{
			echo "C-c-c-combo x$comboCounter!!!\n";
		}

		return true;
	}

	public function isComplete()
	{
		return array_reduce($this->m_actors, function ($a, $b) { return ($a || ($b->getHP() <= 0)); });
	}

	private function resolve($p_attack, $p_block)
	{
		return CombatSession::$STRIFE_MATRIX[$p_attack][$p_block];
	}
}

class CombatActor
{
	private $m_hp = 0;
	private $m_defense = array();
	private $m_offense = array();

	public function __construct()
	{
	}

	public function setDefense($p_moves)
	{
		$this->m_defense = $p_moves;
	}

	public function setOffense($p_moves)
	{
		$this->m_offense = $p_moves;
	}

	public function setHP($p_number)
	{
		$this->m_hp = $p_number;
	}

	public function getDefense()
	{ return $this->m_defense; }

	public function getOffense()
	{ return $this->m_offense; }

	public function getHP()
	{ return $this->m_hp; }
}

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
	$combat = unserialize();
}

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
	}
}

// *** Report combat result ***
echo "You ", ($player1->getHP() > 0 ? 'win' : 'lose'), "!\n";
?>
