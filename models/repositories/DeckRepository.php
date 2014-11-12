<?php
	class DeckRepository extends Repository {

		public function getEntityName() {
			return "TournamentDeck";
		}

		public function getTableName() {
			return "tournament_decks";
		}

		public function persistDecks(Tournament $t, User $u, array $decks = array()){
			$sth = $this->getConnection()->prepare("INSERT INTO tournament_decks
						SET tournament_id = :t_id,
						user_id = :u_id,
						deck = :deck");

			$this->getConnection()->beginTransaction();
			foreach ($decks as $deck){
				$sth->bindValue(":t_id", $t->getId(), PDO::PARAM_INT);
				$sth->bindValue(":u_id", $u->getUserData("id"), PDO::PARAM_INT);
				$sth->bindValue(":deck", $deck, PDO::PARAM_STR);

				$sth->execute();
			}
			$this->getConnection()->commit();
		}

	}