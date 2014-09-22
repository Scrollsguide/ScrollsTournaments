<?php

	class BracketGenerator {

		private $seed;

		private $tournament;

		/**
		 * @var BracketRound
		 */
		private $rounds = array();

		private $byes;

		public function __construct(Tournament $t) {
			$this->tournament = $t;
		}

		public function setSeed($seed) {
			$this->seed = $seed;
		}

		public function generateBrackets() {
			$this->generateRounds();

			$players = BracketUtils::shuffleRand($this->tournament->getPlayers(), $this->seed);
			$player_index = 0;

			$brackets = array();

			for ($i = 0; $i < count($this->rounds); $i++) {
				$round = $this->rounds[$i];

				// check brackets with double byes, two players in the same bracket
				$to_assign = count($players) - $player_index;
				$double_rounds = $to_assign - $round->getMatchCount();

				for ($bracket_count = 0; $bracket_count < $round->getMatchCount(); $bracket_count++) {
					$bracket = new SingleEliminationBracket();
					$bracket->setId(0);

					$players_in_bracket = 2;
					if ($round->isByesRound()) {
						if ($double_rounds === 0) {
							$players_in_bracket = 1;
						} else {
							$double_rounds--;
						}
					}

					// keep adding players while there are still unassigned players
					for ($j = 0; $j < $players_in_bracket && $player_index < count($players); $j++) {
						$bracket->addPlayer($players[$player_index++]);
					}

					$brackets[] = $bracket;
					$round->addBracket($bracket);
				}
			}

			$this->generateTree();

			return $this->rounds;
		}

		private function generateRounds() {
			$players = $this->tournament->getPlayers();

			$num_rounds = BracketUtils::highestBase(count($players));

			$this->byes = BracketUtils::calcByes(count($players));

			// start with finals
			for ($i = $num_rounds; $i > 0; $i--) {
				$max_matchups = pow(2, $num_rounds - $i);

				if ($i === 1) { // first round, add byes
					$max_matchups -= $this->byes;
				}

				// TODO: remove temporary names
				$name = ($i === $num_rounds) ? 'finals' : 'round ' . $i;

				$br = new SingleEliminationBracketRound();
				$br->setName($name);
				$br->setTournamentId($this->tournament->getId());
				$br->setRoundNr($i);
				$br->setMatchCount($max_matchups);

				if ($i === 2) {
					$br->setIsByesRound(true);
				}

				$this->rounds[$i - 1] = $br;
			}
		}

		private function generateTree() {
			// no need to check the finals
			for ($r = 0; $r < count($this->rounds) - 1; $r++) {
				$brackets = $this->rounds[$r]->getBrackets();

				$nextBrackets = $this->rounds[$r + 1]->getBrackets();

				$index = 0;

				for ($b = 0; $b < count($brackets); $b++) {
					// look for suitable children
					while (count($nextBrackets[$index]->getParents()) === 2 ||
						count($nextBrackets[$index]->getPlayers()) === 2 ||
						(count($nextBrackets[$index]->getParents()) === 1 && count($nextBrackets[$index]->getPlayers()) === 1)) {
						$index++;
					}

					$nextBrackets[$index]->addParent($brackets[$b]);
					$brackets[$b]->setChild($nextBrackets[$index]);
				}
			}
		}

	}