<?php
	class CalendarEvent {

		private $id;

		private $title;

		private $subtitle;

		private $time;

		private $type;

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
			return (int)$this->time;
		}

		public function setTime($time) {
			$this->time = $time;
		}

		public function getDate(){
			$date = new DateTime();
			$date->setTimestamp($this->getTime());

			return $date->format("d/n/Y");
		}

		public function getType() {
			return (int)$this->type;
		}

		public function setType($type) {
			$this->type = $type;
		}


	}