<?php

	class Bracket {

		private $id;

		private $players = array();

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

	}