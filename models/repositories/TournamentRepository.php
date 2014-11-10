<?php

	class TournamentRepository extends Repository {

		public function getTableName() {
			return "tournaments";
		}

		public function getEntityName() {
			return "Tournament";
		}

		// saves tournaments to database
		public function persist(Tournament $tournament) {
			if ($tournament->getId() === 0){ // new tournament
				$sth = $this->getConnection()->prepare("INSERT INTO tournaments
							SET name = :name,
							url = :url,
							tournamenttype = :t_type,
							date = :date,
							description = :desc,
							regstate = :regstate,
							tournamentstate = :t_state");

				$sth->bindValue(":name", $tournament->getName(), PDO::PARAM_STR);
				$sth->bindValue(":desc", $tournament->getDescription(), PDO::PARAM_STR);
				$sth->bindValue(":url", $tournament->getUrl(), PDO::PARAM_STR);
				$sth->bindValue(":date", $tournament->getDate(), PDO::PARAM_INT);
				$sth->bindValue(":regstate", $tournament->getRegState(), PDO::PARAM_INT);
				$sth->bindValue(":t_type", $tournament->getTournamentType(), PDO::PARAM_INT);
				$sth->bindValue(":t_state", $tournament->getTournamentState(), PDO::PARAM_INT);

				$sth->execute();
			} else { // edit tournament
				$sth = $this->getConnection()->prepare("UPDATE tournaments
							SET description = :desc,
							tournamentstate = :t_state
							WHERE id = :t_id");

				$sth->bindValue(":desc", $tournament->getDescription(), PDO::PARAM_STR);
				$sth->bindValue(":t_state", $tournament->getTournamentState(), PDO::PARAM_INT);
				$sth->bindValue(":t_id", $tournament->getId(), PDO::PARAM_INT);

				$sth->execute();
			}
		}

		public function addTournamentLog(Tournament $t) {
			$sth = $this->getConnection()->prepare("SELECT id, tournament_id, time, line
						FROM tournament_log
						WHERE tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);

			$sth->execute();

			$logLines = $sth->fetchAll(PDO::FETCH_CLASS, "TournamentLog");
			foreach ($logLines as $line) {
				$t->addLogLine($line);
			}

			return $logLines;
		}

		public function addTournamentPlayers(Tournament $t) {
			$sth = $this->getConnection()->prepare("SELECT TP.id, TP.tournament_id, TP.player_id, U.ingamename
						FROM tournament_players TP, users U
						WHERE TP.tournament_id = :t_id
						AND U.id = TP.player_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);

			$sth->execute();

			$players = $sth->fetchAll(PDO::FETCH_CLASS, "TournamentPlayer");
			foreach ($players as $player) {
				$t->addPlayer($player);
			}
		}

		public function addBracket(Tournament $t) {
			$sth = $this->getConnection()->prepare("SELECT tournament_id, round_nr, name
						FROM bracket_rounds
						WHERE tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);

			$sth->execute();

			$rounds = $sth->fetchAll(PDO::FETCH_CLASS, "BracketRound");

			$sth = $this->getConnection()->prepare("SELECT B.id, B.round, BM.match_id, B.child_bracket_id
						FROM bracket B
                        LEFT JOIN bracket_matches BM
                        ON BM.bracket_id = B.id
						WHERE B.tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			$sth->execute();

			$brackets = $sth->fetchAll(PDO::FETCH_ASSOC);

			// create list of ids for use in next query
			$bracket_ids = array();
			foreach ($brackets as $b) {
				$bracket_ids[] = $b['id'];
			}

			$sth = $this->getConnection()->prepare("SELECT bracket_id, player_id, score, win
						FROM bracket_players
						WHERE bracket_id IN (" . implode(",", $bracket_ids) . ")");
			$sth->execute();

			$players = $sth->fetchAll(PDO::FETCH_ASSOC);

			BracketUtils::matchBrackets($t, $rounds, $brackets, $players);
		}

		public function persistRound(BracketRound $br) {
			$sth = $this->getConnection()->prepare("INSERT INTO bracket_rounds
					SET tournament_id = :t_id,
					round_nr = :r_nr,
					`name` = :name");

			$sth->bindValue(":t_id", $br->getTournamentId(), PDO::PARAM_INT);
			$sth->bindValue(":r_nr", $br->getRoundNr(), PDO::PARAM_INT);
			$sth->bindValue(":name", $br->getName(), PDO::PARAM_STR);

			$sth->execute();
		}

		public function persistBracket(Bracket $b, Tournament $t, BracketRound $br) {
			if (($child = $b->getChild()) !== null) {
				$c_id = $child->getId();
			} else {
				$c_id = 0;
			}

			$sth = $this->getConnection()->prepare("INSERT INTO bracket
					SET tournament_id = :t_id,
					round = :r_nr,
					child_bracket_id = :c_id");

			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			$sth->bindValue(":r_nr", $br->getRoundNr(), PDO::PARAM_INT);
			$sth->bindValue(":c_id", $c_id, PDO::PARAM_INT);

			$sth->execute();

			$bracketDBId = $this->getConnection()->lastInsertId();

			// add players to brackets
			$this->getConnection()->beginTransaction();
			$sth = $this->getConnection()->prepare("INSERT INTO bracket_players
					SET bracket_id = :b_id,
						player_id = :p_id");
			foreach ($b->getPlayers() as $player) {
				$sth->bindValue(":b_id", $bracketDBId, PDO::PARAM_INT);
				$sth->bindValue(":p_id", $player->getId(), PDO::PARAM_INT);

				$sth->execute();
			}
			// finish inserting players
			$this->getConnection()->commit();

			return $bracketDBId;
		}

		public function persistBracketResult(Bracket $b, TournamentPlayer $tp){
			$setQuery = "score = :score, win = :win";
			$sth = $this->getConnection()->prepare("INSERT INTO bracket_players
						SET bracket_id = :b_id,
						player_id = :p_id,
						" . $setQuery . "
						ON DUPLICATE KEY UPDATE " . $setQuery);
			$sth->bindValue(":b_id", $b->getId(), PDO::PARAM_INT);
			$sth->bindValue(":p_id", $tp->getId(), PDO::PARAM_INT);
			$sth->bindValue(":score", $tp->getBracketScore($b->getId()));
			$sth->bindValue(":win", $tp->getBracketWin($b->getId()));

			$sth->execute();
		}

		public function persistRole(Tournament $t, $userId, $role){
			$sth = $this->getConnection()->prepare("INSERT INTO roles
						SET tournament_id = :t_id,
						user_id = :u_id,
						role = :role");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			$sth->bindValue(":u_id", $userId, PDO::PARAM_INT);
			$sth->bindValue(":role", $role, PDO::PARAM_INT);

			$sth->execute();
		}

		public function getUserRole(Tournament $t, $userId){
			$sth = $this->getConnection()->prepare("SELECT role
						FROM roles
						WHERE tournament_id = :t_id
						AND user_id = :u_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			$sth->bindValue(":u_id", $userId, PDO::PARAM_INT);
			$sth->execute();

			if (($role = $sth->fetch(PDO::FETCH_ASSOC)) !== false){
				return (int)$role['role'];
			} else {
				return TournamentPlayerRole::NONE;
			}
		}
	}