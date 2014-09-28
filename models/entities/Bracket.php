<?php

	class Bracket {

		private $id;

		private $players = array();
		
		private $match_id;

		public function getId() {
			return (int)$this->id;
		}

		public function setId($id) {
			$this->id = $id;
		}

		public function addPlayer(TournamentPlayer $p) {
			if (count($this->players) === 2) {
				throw new Exception("Bracket cannot contain more than two players.");
			}

			$this->players[] = $p;
		}

		public function getPlayers() {
			return $this->players;
		}
		
		public function getMatchId(){
			return isset($this->match_id) ? (int)$this->match_id : -1;
		}
		
		public function setMatchId($match_id){
			$this->match_id = $match_id;
		}

	}