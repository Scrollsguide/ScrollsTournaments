<?php

	class TournamentPlayerRepository extends Repository {

		public function getTableName() {
			return "tournament_players";
		}

		public function getEntityName() {
			return "TournamentPlayer";
		}

		// saves tournament player to database
		public function persist(TournamentPlayer $tp) {
			$sth = $this->getConnection()->prepare("INSERT INTO tournament_players
					SET tournament_id = :t_id,
					player_id = :p_id");

			$sth->bindValue(":t_id", $tp->getTournamentId(), PDO::PARAM_INT);
			$sth->bindValue(":p_id", $tp->getPlayerId(), PDO::PARAM_INT);

			$sth->execute();
		}

	}