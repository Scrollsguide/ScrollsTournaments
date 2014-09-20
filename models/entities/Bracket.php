<?php

	class Bracket {

		private $id;

		private $players = array();

		private $parents = array();

		private $child;

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

		public function getParents() {
			return $this->parents;
		}

		public function addParent(Bracket $parent) {
			if (count($this->parents) === 2) {
				throw new Exception("Bracket cannot have more than two parents.");
			}

			$this->parents[] = $parent;
		}

		public function getChild() {
			return $this->child;
		}

		public function setChild(Bracket $child) {
			$this->child = $child;
		}
	}