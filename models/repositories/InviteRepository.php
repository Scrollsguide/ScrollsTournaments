<?php

	class InviteRepository extends Repository {

		public function getEntityName() {
			return "Invite";
		}

		public function getTableName() {
			return "invites";
		}

		public function persist(Invite $i) {
			$sth = $this->getConnection()->prepare("INSERT INTO invites
					SET tournament_id = :t_id,
					code = :code");

			$sth->bindValue(":t_id", $i->getTournamentId(), PDO::PARAM_INT);
			$sth->bindValue(":code", $i->getCode(), PDO::PARAM_STR);

			$sth->execute();
		}
		
		public function addInvite(Tournament $tournament){
			if (($invite = $this->findOneBy("tournament_id", $tournament->getId())) !== null){
				$tournament->setInvite($invite);
			}
		}
	}