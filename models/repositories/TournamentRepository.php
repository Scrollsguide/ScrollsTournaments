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

	}