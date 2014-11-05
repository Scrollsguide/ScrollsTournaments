<?php
	class CalendarRepository extends Repository {

		public function getEntityName() {
			return "CalendarEvent";
		}

		public function getTableName() {
			return "calendar";
		}

		public function findAllBetweenDates(DateTime $first, DateTime $last){
			$firstTimestamp = $first->getTimestamp();
			$lastTimestamp = $last->getTimestamp();

			$sth = $this->getConnection()->prepare("SELECT * FROM calendar
						WHERE time >= :start
						AND time <= :end
						ORDER BY time ASC");
			$sth->bindValue(":start", $firstTimestamp, PDO::PARAM_INT);
			$sth->bindValue(":end", $lastTimestamp, PDO::PARAM_INT);
			$sth->execute();

			$events = $sth->fetchAll(PDO::FETCH_CLASS, $this->getEntityName());

			return $events;
		}
	}