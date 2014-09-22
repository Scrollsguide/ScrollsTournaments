<?php

	class BracketRound {

		private $name;

		private $match_count;

		private $brackets = array();

		private $tournament_id;

		private $round_nr;

		public function __construct() {
		}

		public function getTournamentId() {
			return (int)$this->tournament_id;
		}

		public function setTournamentId($id) {
			$this->tournament_id = $id;
		}

		public function getName() {
			return $this->name;
		}

		public function setName($name) {
			$this->name = $name;
		}

		public function getRoundNr() {
			return (int)$this->round_nr;
		}

		public function setRoundNr($nr) {
			$this->round_nr = $nr;
		}

		public function getMatchCount() {
			return $this->match_count;
		}

		public function setMatchCount($count) {
			$this->match_count = $count;
		}

		/**
		 * @return Bracket array
		 */
		public function getBrackets() {
			return $this->brackets;
		}

		public function addBracket(Bracket $bracket) {
			$this->brackets[] = $bracket;
		}

	}