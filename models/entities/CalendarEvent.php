<?php
	class CalendarEvent {

		private $id;

		private $title;

		private $subtitle;

		private $time;

		private $type;

		private $allday;

		public function getId() {
			return (int)$this->id;
		}

		public function setId($id) {
			$this->id = $id;
		}

		public function getTitle() {
			return $this->title;
		}

		public function setTitle($title) {
			$this->title = $title;
		}

		public function getSubtitle() {
			return $this->subtitle;
		}

		public function setSubtitle($subtitle) {
			$this->subtitle = $subtitle;
		}

		public function getTime() {
			$date = new DateTime();
			$date->setTimestamp($this->getTimestamp());

			return $date->format("H:i");
		}

		public function setTimestamp($time) {
			$this->time = $time;
		}

		public function getDate(){
			$date = new DateTime();
			$date->setTimestamp($this->getTimestamp());

			return $date->format("d/n/Y");
		}

		public function getTimestamp(){
			return $this->time;
		}

		public function getType() {
			return (int)$this->type;
		}

		public function setType($type) {
			$this->type = $type;
		}

		public function getAllday() {
			return (int)$this->allday;
		}

		public function setAllday($allday) {
			$this->allday = $allday;
		}


	}