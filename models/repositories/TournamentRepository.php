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
			$sth = $this->getConnection()->prepare("INSERT INTO tournaments
					SET name = :name,
					url = :url,
					date = :date,
					regstate = :regstate,
					tournamenttype = :t_type");
					
			$sth->bindValue(":name", $tournament->getName(), PDO::PARAM_STR);
			$sth->bindValue(":url", $tournament->getUrl(), PDO::PARAM_STR);
			$sth->bindValue(":date", $tournament->getDate(), PDO::PARAM_INT);
			$sth->bindValue(":regstate", $tournament->getRegState(), PDO::PARAM_INT);
			$sth->bindValue(":t_type", $tournament->getTournamentType(), PDO::PARAM_INT);
			
			$sth->execute();
		}
		
		public function addTournamentLog(Tournament $t){
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
		
		public function addTournamentPlayers(Tournament $t){
			$sth = $this->getConnection()->prepare("SELECT id, tournament_id, player_id
						FROM tournament_players
						WHERE tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			
			$sth->execute();
			
			$players = $sth->fetchAll(PDO::FETCH_CLASS, "TournamentPlayer");
			foreach ($players as $player){
				$t->addPlayer($player);
			}
		}
		
		public function addBracket(Tournament $t){
			$sth = $this->getConnection()->prepare("SELECT tournament_id, round_nr, name
						FROM bracket_rounds
						WHERE tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			
			$sth->execute();
			
			$rounds = $sth->fetchAll(PDO::FETCH_CLASS, "BracketRound");
			
			$sth = $this->getConnection()->prepare("SELECT id, round, child_bracket_id
						FROM bracket
						WHERE tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
			$sth->execute();
			
			$brackets = $sth->fetchAll(PDO::FETCH_ASSOC);
			
			$bracket_ids = array();
			foreach ($brackets as $b){
				$bracket_ids[] = $b['id'];
			}
			
			$sth = $this->getConnection()->prepare("SELECT bracket_id, player_id, score
						FROM bracket_players
						WHERE bracket_id IN (" . implode(",", $bracket_ids) . ")");
			$sth->execute();
			
			$players = $sth->fetchAll(PDO::FETCH_ASSOC);
			
			BracketUtils::matchBrackets($t, $rounds, $brackets, $players);
		}

		public function persistRound(BracketRound $br){
			$sth = $this->getConnection()->prepare("INSERT INTO bracket_rounds
					SET tournament_id = :t_id,
					round_nr = :r_nr,
					`name` = :name");
					
			$sth->bindValue(":t_id", $br->getTournamentId(), PDO::PARAM_INT);
			$sth->bindValue(":r_nr", $br->getRoundNr(), PDO::PARAM_INT);
			$sth->bindValue(":name", $br->getName(), PDO::PARAM_STR);
			
			$sth->execute();
		}
		
		public function persistBracket(Bracket $b, Tournament $t, BracketRound $br){
			if (($child = $b->getChild()) !== null){
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
		
		public function persistInvite(Invite $i){
			$sth = $this->getConnection()->prepare("INSERT INTO invites
					SET tournament_id = :t_id,
					code = :code");
					
			$sth->bindValue(":t_id", $i->getTournamentId(), PDO::PARAM_INT);
			$sth->bindValue(":code", $i->getCode(), PDO::PARAM_STR);
			
			$sth->execute();
		}
	}