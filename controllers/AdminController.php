<?php

	class AdminController extends BaseController {

		public function indexAction() {
			if (!$this->userPerms()) {
				return $this->toLogin(array('to' => 'admin_index'));
			}

			return $this->render("admin/index.html.twig");
		}

		public function calendarAction(){
			if (!$this->userPerms()){
				return $this->toLogin(array('to' => 'admin_calendar'));
			}

			$em = $this->getApp()->get("EntityManager");
			$calendarRepo = $em->getRepository("Calendar");

			$events = $calendarRepo->findAll();

			return $this->render("admin/calendar.html.twig", array(
				'events' => $events
			));
		}

		public function newEventAction(){
			return $this->render("admin/new_event.html.twig");
		}

		private function userPerms() {
			$u = $this->getApp()->getSession()->getUser();

			return $u->checkAccessLevel(AccessLevel::ADMIN);
		}
	}