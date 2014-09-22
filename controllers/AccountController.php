<?php

	class AccountController extends BaseController {

		public function loginAction() {
			return $this->render("login.html.twig");
		}
	}