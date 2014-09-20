<?php

	class TournamentLogRepository extends Repository {

		public function getTableName() {
			return "tournament_log";
		}

		public function getEntityName() {
			return "TournamentLog";
		}

		// saves tournament log to database
		public function persist(TournamentLog $tl) {
			$sth = $this->getConnection()->prepare("INSERT INTO tournament_log
					SET tournament_id = :t_id,
					`time` = :time,
					line = :line");

			$sth->bindValue(":t_id", $tl->getTournamentId(), PDO::PARAM_INT);
			$sth->bindValue(":time", $tl->getTime(), PDO::PARAM_INT);
			$sth->bindValue(":line", $tl->getLine(), PDO::PARAM_STR);

			$sth->execute();
		}

	}