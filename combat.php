<?php
require_once('combatrendering.php'); // All output rendering & displaying should go in the rendering lib script.

/*
The core of the combat system, includes the player objects and the combat object, as well as definitions of some attack-type constants.
*/





// The translations of integers into moves.  Unfortunately, it's hard to translate them back into display messages in this form.  Perhaps they should be remade as array values so that display strings can be applied.
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
define('SEQ_MAX', 255); // This is probably supposed to be 25, not 255.

define('DEBUG', false); // While debugging, display more information than you would with a player.

// Should potentially go in the rendering area eventually, but they're here to keep in sync for now.
$move_display_strings = array(
	0=>'Strike Hi',
	1=>'Strike Mid',
	2=>'Strike Lo',
	3=>'Strike and duck lo',
	4=>'Strike and duck mid',
	5=>'Jumping high strike',
	6=>'Jumping middle strike',
	7=>'Guard',
	8=>'Reversal Hi',
	9=>'Reversal Mid',
	10=>'Reversal Lo',
	11=>'Block Hi',
	12=>'Block Mid',
	13=>'Block Lo',
	14=>'Block and Duck Lo',
	15=>'Jump',
	16=>'Duck',
);

class CombatSession
{
	// 1s indicate attacker success, 0s indicate misses, and 2s indicate potential for reversal.
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
	private $outcome = array();

	public function __construct($p_attacker, $p_defender)
	{
		if(count($p_defender->getDefense())<1){ // For now, only set the defender's defense if it hasn't already been set in the defender object initially.
			$p_defender->setDefense($this->createDefense());
		}
		$p_attacker->setDefense($this->createDefense());

		$this->m_actors['attacker'] = $p_attacker;
		$this->m_actors['defender'] = $p_defender;
	}

	private function createDefense()
	{
		// Sets a defense sequence between 10 and 15 long!  Which is pretty damn long.
		return $this->createSequence(rand(10, 15), 11, 16);
	}

	private function createOffense()
	{
		// Sets an offense sequence between 6 and 10 long!
		return $this->createSequence(rand(6, 10), 0, 6);
	}

	// Creates a sequence of integers so long, within the range of two max integers depending on whether it's defense or offense.
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

	// Add a single entry to the outcome, including current hitpoints for both sides.
	private function addSpar($message, $attacker_hp, $defender_hp)
	{
		// Append array of information to the outcome.
		$this->outcome[] = array('message'=>$message, 'attacker_hp'=>$attacker_hp, 'defender_hp'=>$defender_hp);
	}
	
	// Get the entire outcome for display rendering.
	public function outcome()
	{
		return $this->outcome;
	}

	// The main combat comparison section, based on pre-made defense sequences from the defender.
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
		
		if(DEBUG){
			// Only while debugging clarify the pattern length and attack length.
			echo "End length is: ".$end.", pattern length is: ".$pattern."\n";
		}
		
		$spar_block = ''; // The combined messages that will be displayed per round of combat.
		
		// Is this a single round, or a single happening-between-inputs period?
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

				$spar_block .= $defenseMove. " ";
				$spar_block .= "Hit!\n";

				$breaker = 0;
				$comboCounter++;
			}
			else
			{
				if ($comboCounter > 2)
				{
					if ($reversalMulti)
					{
						$spar_block .= "C-c-c-combo x$comboCounter X".($reversalMulti+1)."!!!\n";
					}
					else
					{
						$spar_block .= "C-c-c-combo x$comboCounter!!!\n";
					}
				}

				$breaker += $strife+1;
			}

			if ($breaker > 0 && $repeat)
			{
				$repeat = 0;

				// *** THROW ***

				$broken = true;
				$spar_block .=  $defenseMove. " Your opponent throws you; you land hard!\n";
				$this->m_actors['attacker']->setHP($this->m_actors['attacker']->getHP()-2);
			}
			else if ($breaker > 1)
			{
				$broken = true;
				$spar_block .= $defenseMove. " ";

				if (($strife == 2) || (($this->m_offenseIndex + 1) >= $end))
				{
					$spar_block .= (($strife == 2) ? "Reversal" : "Counter-attack"). "!!!\n";

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
							$spar_block .= "You get hit!\n";
						}
						else
						{
							$reverseBreaker++;

							if ($r == 0)
							{
								$spar_block .= "Double reversal!!!\n";
								$reversalMulti++;
								$broken = false;
								break;
							}
							else if ($reverseBreaker > 1)
							{
								$reversalMulti = 0;
								$spar_block .= "You defend!\n";
								break;
							}
							else
							{
								$reversalMulti = 0;
								$spar_block .= "You block...\n";
							}
						}
					}

					if ($q == $reverseEnd)
					{
						$reversalMulti = 0;
						$spar_block .= "Your opponent steps back and taunts you!!\n";
					}
				}
				else if ($comboCounter > 2)
				{
					$reversalMulti = 0;
					$spar_block .= "C-c-c-combo breaker!!!!!!!\n";
				}
				else
				{
					$reversalMulti = 0;
					$comboCounter = 0;
					$spar_block .= "Miss!\n";
				}
			}
			else if ($breaker > 0)
			{
				$reversalMulti = 0;
				$comboCounter = 0;
				$spar_block .= $defenseMove. " ";
				$spar_block .= "Miss!\n";
			}

			if ($broken) break;
		}

		if ($comboCounter > 2)
		{
			$spar_block .= "C-c-c-combo x$comboCounter!!!\n";
		}

		// Set a set of output into the outcome array for later display.
		$this->addSpar($spar_block, $this->m_actors['attacker']->getHP(), $this->m_actors['defender']->getHP());

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

// A defender or attacker's stored state.
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




?>
