<?php

	class Tournament {

		private $id;

		private $name;

		private $description;

		private $date;

		private $url;

		private $maxplayers = 0; // default, no max

		// state of registration: see below
		private $regstate;

		private $tournamenttype;

		private $tournamentstate;

		private $visibility;

		private $players = array();

		private $logLines = array();

		private $rounds = array();

		private $deckSettings;

		private $invite;

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

		public function getDescription() {
			return $this->description;
		}

		public function setDescription($description) {
			$this->description = $description;
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

		public function getLogLines() {
			return $this->logLines;
		}

		public function addLogLine(TournamentLog $line) {
			$this->logLines[] = $line;
		}

		public function getRounds() {
			return $this->rounds;
		}

		public function addRound(BracketRound $round) {
			$this->rounds[] = $round;
		}

		public function getTournamentType() {
			return (int)$this->tournamenttype;
		}

		public function setTournamentType($type) {
			$this->tournamenttype = $type;
		}

		public function getTournamentState() {
			return (int)$this->tournamentstate;
		}

		public function setTournamentState($state) {
			$this->tournamentstate = $state;
		}

		public function getInvite() {
			return $this->invite;
		}

		public function setInvite(Invite $invite) {
			$this->invite = $invite;
		}

		// helper for twig
		public function isInviteOnly() {
			return $this->getRegState() === RegistrationState::INVITE_ONLY;
		}

		public function registrationsOpen() {
			return $this->getTournamentState() === TournamentState::REGISTRATION;
		}

		public function inProgress() {
			return $this->getTournamentState() === TournamentState::STARTED;
		}

		public function isFinished() {
			return $this->getTournamentState() === TournamentState::FINISHED;
		}

		public function getBracketById($id) {
			foreach ($this->getRounds() as $r) {
				foreach ($r->getBrackets() as $b) {
					if ($b->getId() === $id) {
						return $b;
					}
				}
			}

			return null;
		}

		public function isPlayer(User $u) {
			if (($ingameName = $u->getUserData('ingame')) !== null) {
				foreach ($this->players as $p) {
					if ($p->getIngameName() === $ingameName) {
						return true;
					}
				}
			}

			return false;
		}

		public function getPlayerById($id){
			foreach ($this->players as $p){
				if ($p->getPlayerId() === $id){
					return $p;
				}
			}
			return null;
		}

		public function getVisibility() {
			return (int)$this->visibility;
		}

		public function setVisibility($visibility) {
			$this->visibility = $visibility;
		}

		public function getDeckSettings() {
			return $this->deckSettings;
		}

		public function setDeckSettings(DeckSettings $deckSettings) {
			$this->deckSettings = $deckSettings;
		}

	}