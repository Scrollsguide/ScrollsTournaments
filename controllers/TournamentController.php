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
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== null) {
				$tournamentRepository->addTournamentLog($tournament);
				$tournamentRepository->addTournamentPlayers($tournament);
				$tournamentRepository->addBracket($tournament);

				$isAdmin = false;
				if ($this->user->checkAccessLevel(AccessLevel::USER)) {
					$isAdmin = $tournamentRepository->getUserRole($tournament, $this->user->getUserData('id')) === TournamentPlayerRole::ADMIN;
				}

				if ($tournament->isInviteOnly()) {
					$inviteRepository = $em->getRepository("Invite");
					$inviteRepository->addInvite($tournament);
				}

				// add some rendering data
				$maxMatchups = 0;
				foreach ($tournament->getRounds() as $round) {
					if ($round->getMatchCount() > $maxMatchups) {
						$maxMatchups = $round->getMatchCount();
					}
				}

				$renderData = array(
					'total_width'  => count($tournament->getRounds()) * 190 + 10,
					'max_matchups' => $maxMatchups,
					'num_byes'     => BracketUtils::calcByes(count($tournament->getPlayers()))
				);

				return $this->render("tournament_user.html.twig", array(
					"tournament"       => $tournament,
					"renderdata"       => $renderData,
					"is_admin"         => $isAdmin,
					"is_participating" => $tournament->isPlayer($this->user)
				));
			} else { // tournament not found in the repository
				return $this->p404();
			}
		}

		public function registerAction($url) {
			if (!$this->user->checkAccessLevel(AccessLevel::USER)) {
				return $this->toLogin();
			}

			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== null) {
				return $this->renderRegisterPage($tournament);
			} else { // tournament not found
				return $this->p404();
			}
		}

		public function renderRegisterPage(Tournament $tournament, Invite $invite = null) {
			if ($tournament->getRegState() === RegistrationState::CLOSED) {

			}

			return $this->render("tournament_register.html.twig", array(
				'tournament' => $tournament,
				'invite'     => $invite,
				'ingamename' => $this->user->getUserData('ingame')
			));
		}

		public function enterAction($url) {
			if (!$this->user->checkAccessLevel(AccessLevel::USER)) {
				return $this->toLogin();
			}

			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== null) {
				$regstate = $tournament->getRegState();
				if ($regstate === RegistrationState::OPEN) {

					$this->registerCurrentUser($tournament);
				} else if ($regstate === RegistrationState::INVITE_ONLY) {
					// check invite code

					$inviteCode = $this->getApp()->getRequest()->getParameter("invite");

					$inviteRepository = $em->getRepository("Invite");
					if (($invite = $inviteRepository->findOneBy("code", $inviteCode)) !== null) {
						if ($invite->getTournamentId() === $tournament->getId()) {
							// all good, register the user :)
							$this->registerCurrentUser($tournament);
						} else {
							// notify user that the code is wrong
							$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "The invite doesn't match this tournament.");
						}
					} else {
						// notify user that the code is wrong
						$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "The invite doesn't match this tournament.");
					}
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

		private function registerCurrentUser(Tournament $tournament) {
			$em = $this->getApp()->get("EntityManager");
			// register this player
			$userId = $this->user->getUserData('id');

			$tpRepo = $em->getRepository("TournamentPlayer");

			$tournamentPlayer = new TournamentPlayer();
			$tournamentPlayer->setPlayerId($userId);
			$tournamentPlayer->setTournamentId($tournament->getId());

			$tpRepo->persist($tournamentPlayer);

			// add front-end confirmation
			$this->getApp()->getSession()->getFlashBag()->add("tournament_message", "You are now participating!");

			// add log
			$this->saveLog($tournament, $em, sprintf("Player %s just joined the tournament.", $this->user->getUserData('ingame')));
		}

		public function startAction($url) {
			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneBy("url", $url)) !== null) {
				// TODO: check whether user is admin for this tournament
				$tournamentRepository->addTournamentPlayers($tournament);

				$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $tournament->getUrl()));

				// check whether there are enough players
				if (count($tournament->getPlayers()) < 3){
					$this->getApp()->getSession()->getFlashBag()->add("tournament_message", "The tournament can't start with less than 3 players.");

					return new RedirectResponse($tournamentRoute);
				}

				$bracketGenerator = new BracketGenerator($tournament);
				$bracketGenerator->setSeed(rand());

				$bracketRounds = $bracketGenerator->generateBrackets();

				// start inserting rounds in database
				$pdo = $this->getApp()->get("database")->getConnection();
				for ($round = count($bracketRounds) - 1; $round >= 0; $round--) {
					$tournamentRepository->persistRound($bracketRounds[$round]);

					$brackets = $bracketRounds[$round]->getBrackets();
					for ($bracket = 0; $bracket < count($brackets); $bracket++) {
						$bracketDBId = $tournamentRepository->persistBracket($brackets[$bracket], $tournament, $bracketRounds[$round]);

						// set db id to parent of next children
						$brackets[$bracket]->setId($bracketDBId);
					}
				}

				// add start action to log and notification
				$msg = sprintf("The tournament has started with %d players!", count($tournament->getPlayers()));
				$this->saveLog($tournament, $em, $msg);

				// add notification
				$this->getApp()->getSession()->getFlashBag()->add("tournament_message", $msg);

				// change tournament state
				$tournament->setTournamentState(TournamentState::STARTED);
				$tournamentRepository->persist($tournament);

				// redirect to tournament page

				return new RedirectResponse($tournamentRoute);
			} else { // tournament not found
				return $this->p404();
			}
		}

		public function acceptInviteAction($code) {
			if (!$this->user->checkAccessLevel(AccessLevel::USER)) {
				return $this->toLogin();
			}

			// set up entity and repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");
			$inviteRepository = $em->getRepository("Invite");

			if (($invite = $inviteRepository->findOneBy("code", $code)) !== null) {
				// find tournament also
				if (($tournament = $tournamentRepository->findOneById($invite->getTournamentId())) !== null) {
					return $this->renderRegisterPage($tournament, $invite);
				}
			}

			// TODO: invite/tournament not found page
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
			if (!RegistrationState::valid($regState)) {
				$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "Not a valid registration state.");

				return $this->toNewPage();
			}

			$name = $r->getParameter("name");

			$t = new Tournament();

			$t->setDate(time());
			$t->setName($name);
			$t->setDescription($r->getParameter("desc"));
			$t->setUrl(URLUtils::makeBlob($name));
			$t->setRegState($regState);
			$t->setTournamentState(TournamentState::REGISTRATION);

			// TODO: use request parameter for type
			$t->setTournamentType(TournamentType::SINGLE_ELIMINATION);

			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			$tournamentRepository->persist($t);

			$t->setId($this->getApp()->get("database")->getConnection()->lastInsertId());

			// add user as admin
			$tournamentRepository->persistRole($t->getId(), $this->user->getUserData('id'), TournamentPlayerRole::ADMIN);

			// check for invite-only and generate invite
			if ($regState === RegistrationState::INVITE_ONLY) {
				$invite = new Invite();
				$invite->setTournamentId($t->getId());
				$invite->setCode(InviteHelper::generateCode());

				$inviteRepository = $em->getRepository("Invite");
				$inviteRepository->persist($invite);
			}

			// save create tournament action to log
			$this->saveLog($t, $em, "Tournament created.");

			// redirect to new tournament page
			$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $t->getUrl()));

			return new RedirectResponse($tournamentRoute);
		}

		private function toNewPage() {
			$newTournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_new");

			return new RedirectResponse($newTournamentRoute);
		}

		private function saveLog(Tournament $t, EntityManager $em, $line) {
			$tl = new TournamentLog();
			$tl->setTournamentId($t->getId());
			$tl->setTime(time());
			$tl->setLine($line);

			$logRepository = $em->getRepository("TournamentLog");
			$logRepository->persist($tl);
		}

	}