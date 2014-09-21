<?php

	class Tournament {

		private $id;

		private $name;

		private $date;

		private $url;

		private $maxplayers = 0; // default, no max

		// state of registration: see below
		private $regstate;

		private $players = array();
		
		private $logLines = array();
		
		private $rounds = array();

		public function __construct() {

		}

		public function getId() {
			return (int)$this->id;
		}

		public function setId($id) {
			$this->id = $id;
		}

		public function getName() {
			return $this->name;
		}

		public function setName($name) {
			$this->name = $name;
		}

		public function getDate() {
			return (int)$this->date;
		}

		public function setDate($date) {
			$this->date = $date;
		}

		public function getUrl() {
			return $this->url;
		}

		public function setUrl($url) {
			$this->url = $url;
		}

		public function getRegState() {
			return (int)$this->regstate;
		}

		public function setRegState($regstate) {
			$this->regstate = $regstate;
		}

		public function setMaxplayers($maxplayers) {
			$this->maxplayers = $maxplayers;
		}

		public function getMaxplayers() {
			return (int)$this->maxplayers;
		}

		public function getPlayers() {
			return $this->players;
		}

		public function addPlayer(TournamentPlayer $player) {
			$this->players[] = $player;
		}

		public function getLogLines(){
			return $this->logLines;
		}
		
		public function addLogLine(TournamentLog $line){
			$this->logLines[] = $line;
		}

		public function getRounds(){
			return $this->rounds;
		}
		
		public function addRound(BracketRound $round){
			$this->rounds[] = $round;
		}
	}

	class RegistrationState {

		const CLOSED = 0;
		const OPEN = 1;
		const INVITE_ONLY = 2;

	}