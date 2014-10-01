<?php

	class CalendarController extends BaseController {

		public function indexAction() {
			return $this->render("calendar/index.html.twig");
		}
	}