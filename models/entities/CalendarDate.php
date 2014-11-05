<?php

	class CalendarDate {

		private $date; // DateTime

		private $events = array();

		public function __construct(){

		}

		public function getEvents(){
			return $this->events;
		}

		public function addEvent(CalendarEvent $event) {
			$this->events[] = $event;
		}

		public function getDate(){
			return $this->date;
		}

		public function setDate(DateTime $date){
			$this->date = $date;
		}
	}