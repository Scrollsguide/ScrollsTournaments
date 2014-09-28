<?php

	class MatchController extends BaseController {

		public function viewMatchAction($id) {
			return $this->render("match.html.twig");
		}

	}