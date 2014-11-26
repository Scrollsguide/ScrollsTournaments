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
			if ($tp->getId() === 0){ // new tournamentplayer
				$sth = $this->getConnection()->prepare("INSERT INTO tournament_players
						SET tournament_id = :t_id,
						player_id = :p_id");

				$sth->bindValue(":t_id", $tp->getTournamentId(), PDO::PARAM_INT);
				$sth->bindValue(":p_id", $tp->getPlayerId(), PDO::PARAM_INT);

				$sth->execute();
			} else { // update tournamentplayer
				$sth = $this->getConnection()->prepare("UPDATE tournament_players
						SET checked_in = :checked_in
						WHERE id = :id");

				$sth->bindValue(":id", $tp->getId(), PDO::PARAM_INT);
				$sth->bindValue(":checked_in", $tp->getCHeckedIn(), PDO::PARAM_INT);

				$sth->execute();
			}
		}

	}