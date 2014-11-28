<?php

	class BracketUtils {

		public static function highestBase($n) {
			$i = 0;
			while (pow(2, $i) < $n) {
				$i++;
			}

			return $i;
		}

		// calculate number of byes for $n players
		public static function calcByes($n) {
			$rounds = BracketUtils::highestBase($n);

			return pow(2, $rounds) - $n;
		}

		public static function matchBrackets(Tournament $t, $br, $brackets, $players) {
			// Add scores to TournamentPlayers
			// convert players to hashmap
			$tournamentPlayers = array();
			foreach ($t->getCheckedInPlayers() as $p) {
				$tournamentPlayers[$p->getId()] = $p;
			}

			foreach ($players as $p) {
				$tournamentPlayers[$p['player_id']]->setBracketResult($p['bracket_id'], $p['score'], $p['win']);
			}

			// hashmap for rounds too
			$rounds = array();
			foreach ($br as $round) {
				$t->addRound($round);

				$rounds[$round->getRoundNr()] = $round;
			}

			// now make brackets
			$tournamentBrackets = array();
			foreach ($brackets as $b) {
				$bObj = new SingleEliminationBracket();
				$bObj->setId($b['id']);
				$bObj->setMatchId($b['match_id']);

				$tournamentBrackets[$b['id']] = $bObj;
			}

			// add children and players
			foreach ($brackets as $b) {
				if ($b['child_bracket_id'] != 0) {
					$tournamentBrackets[$b['id']]->setChild($tournamentBrackets[$b['child_bracket_id']]);
				}

				$rounds[$b['round']]->addBracket($tournamentBrackets[$b['id']]);
			}

			foreach ($rounds as $round) {
				$round->setMatchCount(count($round->getBrackets()));
			}

			foreach ($players as $p) {
				$tournamentBrackets[$p['bracket_id']]->addPlayer($tournamentPlayers[$p['player_id']]);
			}
		}

		public static function shuffleRand($players, $seed) {
			srand($seed);
			$order = array_map(function () {
				return rand();
			}, range(1, count($players)));

			array_multisort($order, $players);

			return $players;
		}

	}