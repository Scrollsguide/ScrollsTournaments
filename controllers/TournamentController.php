<?php
	class TournamentController extends BaseController {
		
		public function viewResultAction($name){
			return $this->render("results.html.twig", array(
				'title' => 'Results for ' . $name,
				'tournament' => $name
			));
		}
		
		public function createAction(){
			return $this->render("create_tournament.html.twig");
		}
		
	}