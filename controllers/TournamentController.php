<?php

	class TournamentController extends BaseController {

		public function viewResultAction($name) {
			return $this->render("results.html.twig", array(
				'title'      => 'Results for ' . $name,
				'tournament' => $name
			));
		}

		public function viewAction($url) {
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== false) {
				$tournamentRepository->addTournamentLog($tournament);
			
				return $this->render("tournament_user.html.twig", array(
					"tournament" => $tournament,
				));
			} else { // tournament not found in the repository
				return $this->p404();
			}
		}

		public function enterAction($url) {
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== false) {
				$regstate = $tournament->getRegState();
				if ($regstate === RegistrationState::OPEN) {
				
					// register this player
					$tpRepo = $em->getRepository("TournamentPlayer");


					$tournamentPlayer = new TournamentPlayer();
					$tournamentPlayer->setPlayerId(2);
					$tournamentPlayer->setTournamentId($tournament->getId());

					$tpRepo->persist($tournamentPlayer);
					
					// add log
					$this->addLog($tournament, $em, "Player ... just joined the tournament.");


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

		public function startAction($url) {
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== false) {
				// TODO: check whether user is admin for this tournament

				for ($i = 0; $i < 4; $i++) {
					$p = new TournamentPlayer();
					$p->setTournamentId($tournament->getId());
					$p->setPlayerId($i);
					$tournament->addPlayer($p);
				}

				$bracketGenerator = new BracketGenerator($tournament);

				$brackets = $bracketGenerator->generateBrackets();

				// add start action to log
				$this->addLog($tournament, $em, sprintf("The tournament has started with %d players!", count($tournament->getPlayers())));
				
				var_dump($brackets);
				die();
			} else { // tournament not found
				return $this->p404();
			}
		}

		public function acceptInviteAction($code) {

		}

		public function newAction() {
			if (!$this->user->checkAccessLevel(AccessLevel::USER)) {
				return $this->toLogin();
			}

			return $this->render("create_tournament.html.twig");
		}

		public function saveAction() {
			if (!$this->user->checkAccessLevel(AccessLevel::USER)) {
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
			
			$t->setId($this->getApp()->get("database")->getConnection()->lastInsertId());
			
			// save create tournament action to log
			$this->saveLog($t, $em, "Tournament created.");

			// redirect to new tournament page
			$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $t->getUrl()));

			return new RedirectResponse($tournamentRoute);

		}

		private function saveLog(Tournament $t, EntityManager $em, $line){
			$tl = new TournamentLog();
			$tl->setTournamentId($t->getId());
			$tl->setTime(time());
			$tl->setLine($line);
			
			$logRepository = $em->getRepository("TournamentLog");
			$logRepository->persist($tl);
		}
		
	}