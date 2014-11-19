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

				// add invite
				if ($tournament->isInviteOnly()) {
					$inviteRepository = $em->getRepository("Invite");
					$inviteRepository->addInvite($tournament);
				}

				$isAdmin = false;
				if ($this->user->checkAccessLevel(AccessLevel::USER)) {
					$isAdmin = $tournamentRepository->getUserRole($tournament, $this->user->getUserData('id')) === TournamentPlayerRole::ADMIN;
				}

				// add data for this player to the twig renderer
				$playerData = array();

				// check if this user is participating
				$playerData['is_participating'] = $tournament->isPlayer($this->user);
				// ... and if so, load decks
				if ($playerData['is_participating']){
					$deckRepository = $em->getRepository("Deck");
					$decks = $deckRepository->findAllByTournamentUser($tournament, $this->user->getUserData("id"));
					$playerData['decks'] = $decks;
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
					'max_matchups' => $maxMatchups
				);

				return $this->render("tournament_user.html.twig", array(
					"tournament"       => $tournament,
					"renderdata"       => $renderData,
					"is_admin"         => $isAdmin,
					"playerdata" => $playerData
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

				// check whether this tournament has started already
				if ($tournament->getTournamentState() !== TournamentState::REGISTRATION) {
					// either started, finished or closed
					$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "The tournament can't start, not in registrations.");

					return new RedirectResponse($tournamentRoute);
				}

				// check whether there are enough players
				if (count($tournament->getPlayers()) < 3) {
					$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "The tournament can't start with less than 3 players.");

					return new RedirectResponse($tournamentRoute);
				}

				$bracketGenerator = new BracketGenerator($tournament);
				$bracketGenerator->setSeed(rand());

				$bracketRounds = $bracketGenerator->generateBrackets();

				// start inserting rounds in database
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
			$visibility = (int)$r->getParameter("visibility");
			if (!Visibility::valid($visibility)){
				$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "Not a valid visibility state.");

				return $this->toNewPage();
			}

			// initialize repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");


			$isNew = $r->getParameter("id", null) === null;
			if (!$isNew) { // edit old tournament
				// TODO: Check whether user is admin and has edit access
				$t = $tournamentRepository->findOneById((int)$r->getParameter("id"));
			} else { // create new tournament
				$t = new Tournament();

				$name = $r->getParameter("name");
				$t->setDate(time());
				$t->setName($name);
				$t->setUrl(URLUtils::makeBlob($name));
				$t->setRegState($regState);
				$t->setTournamentState(TournamentState::REGISTRATION);
				$t->setVisibility($visibility);

				// TODO: use request parameter for type
				$t->setTournamentType(TournamentType::SINGLE_ELIMINATION);
			}

			// parameters that can be edited after creation:
			$t->setDescription($r->getParameter("desc"));

			$tournamentRepository->persist($t);

			if ($isNew) {
				$t->setId($this->getApp()->get("database")->getConnection()->lastInsertId());

				// add user as admin
				$tournamentRepository->persistRole($t, $this->user->getUserData('id'), TournamentPlayerRole::ADMIN);

				// check for invite-only and generate invite
				if ($regState === RegistrationState::INVITE_ONLY) {
					$invite = new Invite();
					$invite->setTournamentId($t->getId());
					$invite->setCode(InviteHelper::generateCode());

					$inviteRepository = $em->getRepository("Invite");
					$inviteRepository->persist($invite);
				}

				// get deck settings
				$decks = (int)$r->getParameter("decks");
				$sideboard = (int)$r->getParameter("sideboard");

				$d = new DeckSettings();
				$d->setTournamentId($t->getId());
				$d->setDecksRequired($decks);
				$d->setSideboardSize($sideboard);
				$tournamentRepository->persistDeckSettings($d);

				// save create tournament action to log
				$this->saveLog($t, $em, "Tournament created.");

				// add notification
				$notification = "Tournament created.";
			} else { // old tournament that's edited
				$notification = "Tournament details updated.";
			}

			$this->getApp()->getSession()->getFlashBag()->add("tournament_message", $notification);

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