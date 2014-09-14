<?php
	class IndexController extends BaseController {
		
		public function indexAction(){
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");
			
			$tournaments = $tournamentRepository->findAll();
			
			return $this->render("index.html.twig", array(
				'tournaments' => $tournaments
			));
		}
		
		public function p404Action(){
			return $this->p404();
		}
	}