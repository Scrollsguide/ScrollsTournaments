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

				if ($this->getApp()->getRequest()->isAjax()) {
					return $this->dateViewAction($day, $month, $year);
				}
			} else { // today
				$day = (int)date('d');
				$month = (int)date('n');
				$year = (int)date('Y');
			}

			// current selected date:
			$current = DateTime::createFromFormat("!d/n/Y", sprintf('%d/%d/%d', $day, $month, $year));

			// first of month:
			$fom = DateTime::createFromFormat("!d/n/Y", sprintf("1/%d/%d", $month, $year));

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

			foreach ($events as $event) {
				$eventDate = $event->getDate();

				// no need to check, but to make sure...
				if (isset($displayDays[$eventDate])) {
					$displayDays[$eventDate]->addEvent($event);
				}
			}

			// select the current day's events:
			$currentFormattedDate = $current->format("d/n/Y");

			$currentEvents = isset($displayDays[$currentFormattedDate]) ? $displayDays[$currentFormattedDate]->getEvents() : "";

			return $this->render("calendar/index.html.twig", array(
				"title"   => "Scrolls Calendar",
				"current" => array(
					'day'    => $day,
					'month'  => $month,
					'year'   => $year,
					'date'   => $current,
					'events' => $currentEvents
				),
				"weeks"   => array_chunk($displayDays, 7)
			));
		}

		public function dateViewAction($day, $month, $year) {
			// now select all events for this date
			$em = $this->getApp()->get("EntityManager");
			$calendarRepo = $em->getRepository("Calendar");

			$format = sprintf("%02d/%02d/%d", $day, $month, $year);
			$events = $calendarRepo->findByDate(DateTime::createFromFormat("!d/n/Y", $format));

			return $this->render("calendar/partials/view_date.html.twig", array(
				'date' => array(
					'day'    => $day,
					'month'  => $month,
					'year'   => $year,
					'date'   => DateTime::createFromFormat("!d/n/Y", sprintf('%d/%d/%d', $day, $month, $year)),
					'events' => $events
				)
			));
		}

		private function emptyDate(&$dates, $day, $month, $year) {
			$format = sprintf("%02d/%02d/%d", $day, $month, $year);
			$date = new CalendarDate();
			// | sets the time to 00:00:00
			$date->setDate(DateTime::createFromFormat("!d/n/Y", $format));

			$dates[$format] = $date;
		}

		public function tempAction() {
			return $this->render("calendar/index_temp.html.twig");
		}
	}