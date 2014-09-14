<?php

	class BaseController extends Controller {
	
		private $cache;
	
		public function __construct(App $app) {
			parent::__construct($app);
			
			$this->setCacheRules(array(
				"cache" => false
			));
			
			$this->cache = new CacheNew($app, "MySQL");
		}
		
		protected function getCache(){
			return $this->cache;
		}
		
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
		
		// redirects to admin login page
		protected function toLogin() {
			$loginRoute = $this->getApp()->getRouter()->getRoute("login");

			return new RedirectResponse($loginRoute->get("path"));
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