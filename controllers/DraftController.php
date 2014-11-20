<?php
	class DraftController extends BaseController {

		public function draftAction(){
			return $this->render("draft/draft.html.twig");
		}

	}