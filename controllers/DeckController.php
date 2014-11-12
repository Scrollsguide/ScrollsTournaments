<?php
	class DeckController extends BaseController {

		public function saveAction(){
			// is user logged in?
			if (!$this->user->isLoggedIn()){
				$this->getApp()->getSession()->getFlashBag()->add("login_message", "Please login to the website.");
				return $this->toLogin();
			}

			$r = $this->getApp()->getRequest();

			// get repository
			$em = $this->getApp()->get("EntityManager");
			$tournamentRepository = $em->getRepository("Tournament");

			$tournament_id = $r->getParameter("id", 0);
			// look for tournament in the repo
			if (($tournament = $tournamentRepository->findOneById($tournament_id)) !== null) {
				if (!$tournament->registrationsOpen()){
					// can't edit decks when not in registrations
					$this->getApp()->getSession()->getFlashBag()->add("tournament_error", "You can't edit your decks while the tournament is in progress.");
				} else {
					$deckRepository = $em->getRepository("Deck");

					// save decks
					$decks = $this->getApp()->getRequest()->getParameter("decks");

					$deckRepository->persistDecks($tournament, $this->user, $decks);

					$this->getApp()->getSession()->getFlashBag()->add("tournament_message", "Your decks have been saved.");
				}
			} else {
				return $this->p404();
			}

			// redirect to tournament page
			$tournamentRoute = $this->getApp()->getRouter()->generateUrl("tournament_view", array("name" => $tournament->getUrl()));

			return new RedirectResponse($tournamentRoute);
		}

	}