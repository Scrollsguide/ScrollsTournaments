<?php

	class BaseController extends Controller {

		protected function render($templatePath, array $parameters = array(), $statusCode = 200) {
			// add default parameters for every page
			$parameters['title'] = $this->getPageTitle($parameters);

			$twig = $this->getApp()->get("twig");
			$template = $twig->loadTemplate($templatePath);

			$response = new HtmlResponse();
			$response->setStatusCode($statusCode);
			$response->setContent($template->render($parameters));

			return $response;
		}


		public function p404(){
			return $this->render("404.html.twig", array(
				'title' => 'Page not found'
			), 404);
		}

		private function getPageTitle($parameters = array()) {
			if (isset($parameters['title'])) {
				return $parameters['title'] . " - Scrolls Tournaments";
			}

			return "Scrolls Tournaments";
		}

	}