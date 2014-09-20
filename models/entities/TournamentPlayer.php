<?php
	class TournamentPlayer {

		private $id;

		private $tournament_id;

		private $player_id;

		public function setId($id) {
			$this->id = $id;
		}

		public function getId() {
			return (int)$this->id;
		}

		public function setPlayerId($player_id) {
			$this->player_id = $player_id;
		}

		public function getPlayerId() {
			return (int)$this->player_id;
		}

		public function setTournamentId($tournament_id) {
			$this->tournament_id = $tournament_id;
		}

		public function getTournamentId() {
			return (int)$this->tournament_id;
		}



	}