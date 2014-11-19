<?php
	class TournamentDeck {

		private $id;

		private $tournament_id;

		private $user_id;

		private $deck;

		private $is_sideboard;


		public function getId() {
			return (int)$this->id;
		}

		public function setId($id) {
			$this->id = $id;
		}

		public function getTournamentId() {
			return (int)$this->tournament_id;
		}

		public function setTournamentId($tournament_id) {
			$this->tournament_id = $tournament_id;
		}

		public function getUserId() {
			return (int)$this->user_id;
		}

		public function setUserId($user_id) {
			$this->user_id = $user_id;
		}

		public function getDeck() {
			return $this->deck;
		}

		public function setDeck($deck) {
			$this->deck = $deck;
		}

		public function getIsSideboard() {
			return (int)$this->is_sideboard;
		}

		public function setIsSideboard($is_sideboard) {
			$this->is_sideboard = $is_sideboard;
		}

	}