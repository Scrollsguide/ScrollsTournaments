<?php

	class CalendarController extends BaseController {

		public function indexAction($date = null) {
			// array of CalendarDate object to feed to twig
			$displayDays = array();

			if ($date !== null) {
				$split = explode("-", $date);

				$day = (int)$split[0];
				$month = (int)$split[1];
				$year = (int)$split[2];

				// check validity
				if (!checkdate($month, $day, $year)) {
					// not valid
					return $this->p404();
				}
			} else { // today
				$day = (int)date('d');
				$month = (int)date('n');
				$year = (int)date('Y');
			}

			// first of month:
			$fom = DateTime::createFromFormat("d/n/Y", sprintf("1/%d/%d", $month, $year));

			// check day offset, how many days to display from previous month
			$dow = (int)$fom->format('N');
			$daysInMonth = (int)$fom->format('t');
			if ($dow > 0) {
				$prevMonthDateTime = DateTimeHelper::previousMonth($fom);

				$offset = (int)$prevMonthDateTime->format('t') - $dow + 1;
				$prevMonthNr = (int)$prevMonthDateTime->format('n');
				$prevMonthYear = (int)$prevMonthDateTime->format('Y');
				// - 1 to skip first day of month
				for ($i = 1; $i <= $dow - 1; $i++) {
					$this->emptyDate($displayDays, $offset + $i, $prevMonthNr, $prevMonthYear);
				}
			}

			// add days from this month
			for ($i = 1; $i <= $daysInMonth; $i++) {
				$this->emptyDate($displayDays, $i, $month, $year);
			}

			// add days from next month if necessary to fill out the grid
			$totalDays = ($dow - 1 + $daysInMonth);
			$mod = (int)($totalDays / 7);
			$remaining = 7 - ($totalDays - (7 * $mod));

			if ($remaining !== 7) {
				$nextMonthDateTime = DateTimeHelper::nextMonth($fom);
				$nextMonthNr = (int)$nextMonthDateTime->format('n');
				$nextMonthYear = (int)$nextMonthDateTime->format('Y');
				for ($i = 1; $i <= $remaining; $i++) {
					$this->emptyDate($displayDays, $i, $nextMonthNr, $nextMonthYear);
				}
			}

			// now select all events surrounding those dates
			$em = $this->getApp()->get("EntityManager");
			$calendarRepo = $em->getRepository("Calendar");

			// get last date
			// make sure we select to the end of the last date
			$lastDay = end($displayDays);
			$lastDay = DateTimeHelper::nextDay($lastDay->getDate());
			$events = $calendarRepo->findAllBetweenDates($fom, $lastDay);

			foreach ($events as $event){
				$eventDate = $event->getDate();

				// no need to check, but to make sure...
				if (isset($displayDays[$eventDate])){
					$displayDays[$eventDate]->addEvent($event);
				}
			}


			return $this->render("calendar/index.html.twig", array(
				"current" => array(
					'day'   => $day,
					'month' => $month,
					'year'  => $year
				),
				"today"   => new DateTime(),
				"weeks"   => array_chunk($displayDays, 7)
			));
		}

		private function emptyDate(&$dates, $day, $month, $year) {
			$format = sprintf("%02d/%02d/%d", $day, $month, $year);
			$date = new CalendarDate();
			// | sets the time to 00:00:00
			$date->setDate(DateTime::createFromFormat("d/n/Y|", $format));

			$dates[$format] = $date;
		}

		public function tempAction() {
			return $this->render("calendar/index_temp.html.twig");
		}
	}