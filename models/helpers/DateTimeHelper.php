<?php
	class DateTimeHelper{

		public static function previousMonth(DateTime $month){
			$temp = clone $month;
			$temp->sub(new DateInterval("P1M"));

			return $temp;
		}

		public static function nextMonth(DateTime $month){
			$temp = clone $month;
			$temp->add(new DateInterval("P1M"));

			return $temp;
		}

		public static function nextDay(DateTime $day){
			$temp = clone $day;
			$temp->add(new DateInterval("P1D"));

			return $temp;
		}

	}