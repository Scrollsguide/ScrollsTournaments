<?php

	class BracketController extends BaseController {

		public function viewAction($tournamentUrl, $bracketId) {
			return $this->renderBracketModal($tournamentUrl, $bracketId, "partials/bracket_modal.html.twig");
		}

		private function renderBracketModal($tournamentUrl, $bracketId, $view) {
			// TODO: check for admin
			$em = $this->getApp()->get("EntityManager");

			$tournamentRepository = $em->getRepository("Tournament");

			if (($tournament = $tournamentRepository->findOneBy("url", $tournamentUrl)) !== null) {
				$tournamentRepository->addTournamentPlayers($tournament);
				$tournamentRepository->addBracket($tournament);

				if (($bracket = $tournament->getBracketById((int)$bracketId)) !== null) {

					$matchId = $bracket->getMatchId();
					if ($matchId !== -1) {
						// find matches associated with the brackets
						// set up connection to new database first
						/*
						$config = $this->getApp()->getConfig();
						$matchDB = new Database($this->getApp());
						$matchDB->setHost($config->get("pdo_host"), $config->get("pdo_port"));
						$matchDB->setCredentials($config->get("pdo_match_user"), $config->get("pdo_match_pass"));
						$matchDB->setDatabaseName($config->get("pdo_match_db"));
						*/
					}

					$players = $bracket->getPlayers();

					return $this->render($view,
						array(
							'tournament' => $tournament,
							'player_1'   => $players[0],
							'player_2'   => $players[1],
							'bracket'    => $bracket,
							'matchid'    => $matchId
						));
				}
			}

			return $this->render("partials/bracket_404.html.twig",
				array('err' => "Bracket not found for this tournament."));
		}

		public function viewAdminAction($tournamentUrl, $bracketId) {
			return $this->renderBracketModal($tournamentUrl, $bracketId, "admin/partials/update_bracket_modal.html.twig");
		}

		public function updateBracketAction($tournamentUrl, $bracketId) {
			/*
			// TODO: check for admin
			$em = $this->getApp()->get("EntityManager");

			$tournamentRepository = $em->getRepository("Tournament");

			if (($tournament = $tournamentRepository->findOneBy("url", $tournamentUrl)) !== null) {
				$tournamentRepository->addTournamentPlayers($tournament);
				$tournamentRepository->addBracket($tournament);

				if (($bracket = $tournament->getBracketById((int)$bracketId)) !== null) {
					var_dump($bracket);
					die();
				}
			}

			return $this->render("partials/bracket_404.html.twig",
				array('err' => "Bracket not found for this tournament."));
			*/
			$r = new HtmlResponse();
			$r->setContent("yoyo");
			return $r;
		}

	}