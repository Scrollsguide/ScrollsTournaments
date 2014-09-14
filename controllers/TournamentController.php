<?php
	class TournamentController extends BaseController {
		
		public function viewResultAction($name){
			return $this->render("results.html.twig", array(
				'title' => 'Results for ' . $name,
				'tournament' => $name
			));
		}
		
		public function viewAction($url){
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");
		
			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== false) {
				return $this->render("tournament_user.html.twig", array(
					"tournament" => $tournament,
				));
			} else { // tournament not found in the repository
				return $this->p404();
			}
		}
		
		public function newAction(){
			if (!$this->user->checkAccessLevel(AccessLevel::USER)) {
				return $this->toLogin();
			}
			
			return $this->render("create_tournament.html.twig");
		}
		
		public function saveAction(){
			if (!$this->user->checkAccessLevel(AccessLevel::USER)){
				return $this->toLogin();
			}
			
			$r = $this->getApp()->getRequest();
			
			$name = $r->getParameter("name");
			
			$t = new Tournament();
			
			$t->setDate(time());
			$t->setName($name);
			$t->setUrl(URLUtils::makeBlob($name));
			$t->setRegState($r->getParameter("regstate"));
			
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");
			
			$tournamentRepository->persist($t);
			
			// redirect to new tournament page
			$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $t->getUrl()));

			return new RedirectResponse($tournamentRoute);
		}
	}