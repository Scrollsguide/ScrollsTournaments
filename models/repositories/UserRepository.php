<?php

	class UserRepository extends Repository {

		public function getTableName() {
			return "users";
		}

		public function getEntityName() {
			return "TournamentPlayer";
		}

		// saves user to database
		public function persist(User $u) {
			// since username is a unique key, the ingamename will get updated
			// once the SG user object is updated
			$sth = $this->getConnection()->prepare("INSERT INTO users
					SET id = :id,
					username = :username,
					ingamename = :ingamename
					ON DUPLICATE KEY UPDATE
					ingamename = :ingamename");

			$sth->bindValue(":id", $u->getUserData('id'), PDO::PARAM_INT);
			$sth->bindValue(":username", $u->getUsername(), PDO::PARAM_STR);
			$sth->bindValue(":ingamename", $u->getUserData('ingame'), PDO::PARAM_STR);

			$sth->execute();
		}

	}