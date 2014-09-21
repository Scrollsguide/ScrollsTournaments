<?php

	class TwigHelper {

		private $app;

		public function __construct(App $app) {
			$this->app = $app;
		}

		public static function registerHelpers(App $app, Twig_Environment $twig) {
			$t = new self($app);

			// functions
			$twig->addFunction(new Twig_SimpleFunction("dump", function ($arg) {
				return var_dump($arg);
			}));
			$twig->addFunction(new Twig_SimpleFunction("path", array($t, "path")));
			$twig->addFunction(new Twig_SimpleFunction("fullPath", array($t, "fullPath")));

			$twig->addFunction(new Twig_SimpleFunction("cdn_asset", array($t, "cdnAsset")));

			$twig->addFunction(new Twig_SimpleFunction("currentRoute", array($t, "currentRoute")));
			$twig->addFunction(new Twig_SimpleFunction("categoryIcon", array($t, "categoryIcon")));
			$twig->addFunction(new Twig_SimpleFunction("imagePath", array($t, "imagePath")));
			
			$twig->addFunction(new Twig_SimpleFunction("tournamentType", array($t, "tournamentType")));

			// filters
			$twig->addFilter(new Twig_SimpleFilter("cut", array($t, "cut")));

			// variables
			$twig->addGlobal('app', $app);

			//set the timezone so the date helper can work properly
			$twig->getExtension('core')->setTimezone('Europe/Paris');
		}

		/**
		 * path() function in twig templates
		 * Usage: path("routeId", { "param1": "paramval" });
		 * routeParams is optional
		 */
		public function path($routeId, $routeParams = array()) {
			return $this->app->getRouter()->generateUrl($routeId, $routeParams);
		}

		// TODO: replace with proper get of domain
		public function fullPath($routeId, $routeParams = array()) {
			return $this->app->getRequest()->getURL()->getBaseURL() . $this->path($routeId, $routeParams);
		}

		public function cdnAsset($relativeUrl) {
			$config = $this->app->getConfig();
			if ($config->exists(Config::CDN_DOMAIN)) {
				return $config->get(Config::CDN_DOMAIN) . $relativeUrl;
			} else {
				return $relativeUrl;
			}
		}

		public function currentRoute() {
			return $this->app->getRoute();
		}

		public function imagePath($filename) {
			return $this->cdnAsset('/assets/images/user-imgs/' . $filename);
		}

		public function cut($str, $length = 30, $toSpace = true, $last = "...") {
			if (strlen($str) <= $length) {
				return $str;
			}

			if ($toSpace) {
				if (($break = strpos($str, " ", $length)) !== false) {
					$length = $break;
				}
			}

			return rtrim(substr($str, 0, $length)) . $last;
		}
		
		/*
			Generates the font-awesome icon for a corresponding category
		*/
		public function categoryIcon($category) {
			$iconMap = array(
				'about'        => 'info',
				'submit'       => 'upload',
				'beginner'     => 'cogs',
				'intermediate' => 'star',
				'master'       => 'trophy',
				'video'        => 'video-camera',
				'strategy'     => 'puzzle-piece',
				'decks'        => 'inbox',
				'judgement'    => 'flag',
				'ui'           => 'laptop',
				'404'          => 'exclamation',
				'forum'        => 'comments',
				'wiki'         => 'globe',
				'series'       => 'sort-alpha-asc'
			);
			$category = strtolower($category);

			return isset($iconMap[$category]) ? $iconMap[$category] : '';
		}
		
		public function tournamentType($t){
			switch ($t->getTournamentType()){
				case TournamentType::ROUND_ROBIN:
					return "Round Robin";
				case TournamentType::SINGLE_ELIMINATION:
					return "Single Elimination";
				case TournamentType::DOUBLE_ELIMINATION:
					return "Double Elimination";
			}
		}
	}