<?php
	class Tournament {
	
		private $id;
		
		private $name;
		
		private $date;
		
		private $url;
		
		// state of registration: see below
		private $regstate;
		
		public function __construct(){
		
		}
		
		public function getId(){
			return (int)$this->id;
		}
		
		public function setId($id){
			$this->id = $id;
		}
		
		public function getName(){
			return $this->name;
		}
		
		public function setName($name){
			$this->name = $name;
		}
		
		public function getDate(){
			return (int)$this->date;
		}
		
		public function setDate($date){
			$this->date = $date;
		}
		
		public function getUrl(){
			return $this->url;
		}
		
		public function setUrl($url){
			$this->url = $url;
		}
		
		public function getRegState(){
			return (int)$this->regstate;
		}
		
		public function setRegState($regstate){
			$this->regstate = $regstate;
		}
	
	}
	
	class RegistrationState {
	
		const CLOSED = 0;
		const OPEN = 1;
		const INVITE_ONLY = 2;
	
	}