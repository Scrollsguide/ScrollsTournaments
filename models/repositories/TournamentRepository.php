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
					regstate = :regstate");
					
			$sth->bindValue(":name", $tournament->getName(), PDO::PARAM_STR);
			$sth->bindValue(":url", $tournament->getUrl(), PDO::PARAM_STR);
			$sth->bindValue(":date", $tournament->getDate(), PDO::PARAM_INT);
			$sth->bindValue(":regstate", $tournament->getRegState(), PDO::PARAM_INT);
			
			$sth->execute();
		}
		
		public function addTournamentLog(Tournament $t){
			$sth = $this->getConnection()->prepare("SELECT time, line
						FROM tournament_log
						WHERE tournament_id = :t_id");
			$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);

			$sth->execute();

			$logLines = $sth->fetchAll(PDO::FETCH_ASSOC);
			foreach ($logLines as $line) {
				$t->addLogLine($line);
			}

			return $logLines;
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
		}
	}