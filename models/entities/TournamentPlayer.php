<?php

	class TournamentPlayer {

		private $id;

		private $tournament_id;

		private $player_id;
		
		private $username;

		private $bracket_scores = array();

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
		
		public function getUsername(){
			return $this->username;
		}
		
		public function setUsername($username){
			$this->username = $username;
		}

		public function setTournamentId($tournament_id) {
			$this->tournament_id = $tournament_id;
		}

		public function getTournamentId() {
			return (int)$this->tournament_id;
		}

		public function getBracketScores() {
			return $this->bracket_scores;
		}

		public function getBracketScore($bracket_id) {
			if (isset($this->bracket_scores[$bracket_id])) {
				return (int)$this->bracket_scores[$bracket_id];
			} else {
				return null;
			}
		}

		public function setBracketScore($bracket_id, $score) {
			$this->bracket_scores[$bracket_id] = $score;
		}

	}