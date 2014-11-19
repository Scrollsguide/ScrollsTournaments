<?php

	class AdminController extends BaseController {

		public function indexAction() {
			if (!$this->userPerms()) {
				return $this->toLogin(array('to' => 'admin_index'));
			}

			return $this->render("admin/index.html.twig");
		}

		private function userPerms() {
			$u = $this->getApp()->getSession()->getUser();

			return $u->checkAccessLevel(AccessLevel::ADMIN);
		}
	}