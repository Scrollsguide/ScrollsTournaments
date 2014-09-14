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
		
		public function enterAction($url){
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");
		
			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== false) {
				$regstate = $tournament->getRegState();
				if ($regstate === RegistrationState::OPEN){
					
				
				
					// notify user
					$this->getApp()->getSession()->getFlashBag()->add("tournament_message", "You are now participating!");
				} else if ($regstate === RegistrationState::INVITE_ONLY) {
					// notify user that registration is invite-only
					$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "Registrations are invite-only for this tournament.");
				} else {
					// notify user that registration is closed
					$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "Registrations are closed for this tournament.");
				}
				
				// redirect to tournament page
				$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $tournament->getUrl()));

				return new RedirectResponse($tournamentRoute);
			} else { // tournament not found
				return $this->p404();
			}
		}
		
		public function acceptInviteAction($code){
			
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