<?php

	class TournamentLog {

		private $id;

		private $tournament_id;

		private $time;

		private $line;

		public function getTournamentId() {
			return (int)$this->tournament_id;
		}

		public function setTournamentId($id) {
			$this->tournament_id = $id;
		}

		public function getTime() {
			return (int)$this->time;
		}

		public function setTime($time) {
			$this->time = $time;
		}

		public function getLine() {
			return $this->line;
		}

		public function setLine($line) {
			$this->line = $line;
		}

	}