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
				$tournamentRepository->addTournamentPlayers($tournament);
				$tournamentRepository->addBracket($tournament);
			
				// add some rendering data
				$maxMatchups = 0;
				foreach ($tournament->getRounds() as $round){
					if ($round->getMatchCount() > $maxMatchups){
						$maxMatchups = $round->getMatchCount();
					}
				}
				
				$renderData = array(
					'total_width' => count($tournament->getRounds()) * 190 + 10,
					'max_matchups' => $maxMatchups,
					'num_byes' => BracketUtils::calcByes(count($tournament->getPlayers()))
				);
			
				return $this->render("tournament_user.html.twig", array(
					"tournament" => $tournament,
					"renderdata" => $renderData
				));
			} else { // tournament not found in the repository
				return $this->p404();
			}
		}
		
		public function registerAction($url){
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== false) {
				return $this->render("tournament_register.html.twig", array(
					'tournament' => $tournament
				));
			} else { // tournament not found
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
					$user = $this->getApp()->getSession()->getUser();
					$userId = $user->getUserData()['id'];
					
					$tpRepo = $em->getRepository("TournamentPlayer");
					
					$tournamentPlayer = new TournamentPlayer();
					$tournamentPlayer->setPlayerId($userId);
					$tournamentPlayer->setTournamentId($tournament->getId());

					$tpRepo->persist($tournamentPlayer);
					
					// add log
					$this->saveLog($tournament, $em, sprintf("Player %s just joined the tournament.", $user->getUsername()));

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
				$tournamentRepository->addTournamentPlayers($tournament);

				$bracketGenerator = new BracketGenerator($tournament);
				$bracketGenerator->setSeed(rand());

				$bracketRounds = $bracketGenerator->generateBrackets();
				
				// start inserting rounds in database
				$pdo = $this->getApp()->get("database")->getConnection();
				for ($round = count($bracketRounds) - 1; $round >= 0; $round--){
					$tournamentRepository->persistRound($bracketRounds[$round]);
					
					$brackets = $bracketRounds[$round]->getBrackets();
					for ($bracket = 0; $bracket < count($brackets); $bracket++){
						$bracketDBId = $tournamentRepository->persistBracket($brackets[$bracket], $tournament, $bracketRounds[$round]);
						
						// set db id to parent of next children
						$brackets[$bracket]->setId($bracketDBId);
					}
				}

				// add start action to log
				$this->saveLog($tournament, $em, sprintf("The tournament has started with %d players!", count($tournament->getPlayers())));
			} else { // tournament not found
				return $this->p404();
			}
		}

		public function acceptInviteAction($code) {
			// load tournament for invite
			
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

			$regState = (int)$r->getParameter("regstate");
			if (!RegistrationState::valid($regState)){
				$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "Not a valid registration state.");				
				return $this->toNewPage();
			}
			
			$name = $r->getParameter("name");

			$t = new Tournament();

			$t->setDate(time());
			$t->setName($name);
			$t->setUrl(URLUtils::makeBlob($name));
			$t->setRegState($regState);
			
			// TODO: use request parameter for type
			$t->setTournamentType(TournamentType::SINGLE_ELIMINATION);

			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			$tournamentRepository->persist($t);
			
			$t->setId($this->getApp()->get("database")->getConnection()->lastInsertId());
			
			// check for invite-only and generate invite
			if ($regState === RegistrationState::INVITE_ONLY){
				$invite = new Invite();
				$invite->setTournamentId($t->getId());
				$invite->setCode(InviteHelper::generateCode());
				
				$tournamentRepository->persistInvite($invite);
			}
			
			// save create tournament action to log
			$this->saveLog($t, $em, "Tournament created.");

			// redirect to new tournament page
			$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $t->getUrl()));

			return new RedirectResponse($tournamentRoute);
		}
		
		private function toNewPage(){			
			$newTournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_new");
			return new RedirectResponse($newTournamentRoute);
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