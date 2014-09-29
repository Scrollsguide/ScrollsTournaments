<?php

	class TournamentPlayer {

		private $id;

		private $tournament_id;

		private $player_id;

		private $username;

		private $bracket_scores = array();

		private $ingamename;

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

		public function getUsername() {
			return $this->username;
		}

		public function setUsername($username) {
			$this->username = $username;
		}

		public function getIngameName() {
			return $this->ingamename;
		}

		public function setIngameName($ingamename) {
			$this->ingamename = $ingamename;
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
				return (int)$this->bracket_scores[$bracket_id]['score'];
			} else {
				return null;
			}
		}

		public function getBracketWin($bracket_id){
			if (isset($this->bracket_scores[$bracket_id])) {
				return (int)$this->bracket_scores[$bracket_id]['win'];
			} else {
				return 0;
			}
		}

		public function setBracketResult($bracket_id, $score, $win) {
			$this->bracket_scores[$bracket_id] = array(
				'score' => $score,
				'win'   => $win
			);
		}

	}