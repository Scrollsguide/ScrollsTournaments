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

					$winner = null;
					foreach ($players as $p) {
						if ($p->getBracketWin($bracket->getId()) === 1) {
							$winner = $p;
						}
					}

					return $this->render($view,
						array(
							'tournament' => $tournament,
							'player_1'   => $players[0],
							'player_2'   => $players[1],
							'bracket'    => $bracket,
							'matchid'    => $matchId,
							'winner'     => $winner
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
			// TODO: check for admin
			$em = $this->getApp()->get("EntityManager");

			$tournamentRepository = $em->getRepository("Tournament");

			if (($tournament = $tournamentRepository->findOneBy("url", $tournamentUrl)) !== null) {
				$tournamentRepository->addTournamentPlayers($tournament);
				$tournamentRepository->addBracket($tournament);

				if (($bracket = $tournament->getBracketById((int)$bracketId)) !== null) {
					$r = $this->getApp()->getRequest();

					$winner = (int)$r->getParameter("winner");
					$winnerPlayerObj = null;

					// is there a winner for this bracket?
					$defaultWin = $winner !== -1 ? 0 : -1;

					$matchId = $bracket->getMatchId();

					$players = $bracket->getPlayers();
					foreach ($players as $player) {
						$playerScore = $r->getParameter('score-player-' . $player->getId());

						$win = $defaultWin;
						if ($winner === $player->getId()) {
							$win = 1;
							$winnerPlayerObj = $player;
						}

						// save this score to the bracket that's being edited
						$player->setBracketResult($bracket->getId(), $playerScore, $win);

						$tournamentRepository->persistBracketResult($bracket, $player);
					}

					// update child bracket with new winner
					if (($child = $bracket->getChild()) !== null && $winnerPlayerObj !== null) {
						// there is a child, this is not the finals
						// check whether there is still a spot left
						if (count($child->getPlayers() < 2)) {
							$child->addPlayer($winnerPlayerObj);

							// add this player to the child bracket as a player, no score set yet
							$winnerPlayerObj->setBracketResult($child->getId(), -1, -1);

							$tournamentRepository->persistBracketResult($child, $winnerPlayerObj);
						}
					}

					return $this->render("admin/partials/update_bracket_modal.html.twig",
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

	}