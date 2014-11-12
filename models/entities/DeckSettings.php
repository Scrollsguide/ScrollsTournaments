<?php
	class DeckSettings {

		private $tournament_id;

		private $decks_required = 0;

		private $sideboard_size = 0;

		public function getTournamentId() {
			return (int)$this->tournament_id;
		}

		public function setTournamentId($tournament_id) {
			$this->tournament_id = $tournament_id;
		}

		public function getDecksRequired() {
			return (int)$this->decks_required;
		}

		public function setDecksRequired($decks_required) {
			$this->decks_required = $decks_required;
		}

		public function getSideboardSize() {
			return (int)$this->sideboard_size;
		}

		public function setSideboardSize($sideboard_size) {
			$this->sideboard_size = $sideboard_size;
		}

	}