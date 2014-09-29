<?php

	class UserController extends BaseController {

		public function __construct(App $app) {
			parent::__construct($app);
			// don't cache the user pages
			$this->setCacheRules(array(
				"cache" => false
			));
		}

		public function loginAction() {
			return $this->render("login.html.twig", array(
				"title" => "User login"
			));
		}

		// contains POST login information
		public function doLoginAction() {
			$session = $this->getApp()->getSession();
			$r = $this->getApp()->getRequest();

			$username = $r->getParameter("username");
			$password = $r->getParameter("password");

			$bag = $session->getFlashBag();
			if (empty($username)) {
				$bag->add("login_message", "Fill out a username.");

				return $this->toLogin();
			}
			if (empty($password)) {
				$bag->add("login_message", "Fill out a password.");

				return $this->toLogin();
			}

			// set up Account Provider
			$accountProviderName = $this->getApp()->getConfig()->get("accountprovider") . "AccountProvider";
			$sgAccount = new $accountProviderName($this->getApp());

			if (!$session->getUser()->login($sgAccount, $username, $password)) {
				$bag->add("login_message", "Wrong password or nonexistent user.");

				return $this->toLogin();
			}

			// successfully logged in by now
			// now check the ingame name for the user
			// and save to database
			if (($ingameName = $session->getUser()->getUserData('ingame')) === null) {
				// no in-game name supplied yet, make sure people do that before being able to login
				$session->getUser()->logout();
				$session->getFlashBag()->add("login_message", "Please <a href='http://www.scrollsguide.com/forum/ucp.php?i=profile&mode=profile_info'>fill out an in-game username on your Scrollsguide profile</a> before continuing.");

				return $this->toLogin();
			} else {
				// save user details to database
				$em = $this->getApp()->get("EntityManager");
				$userRepo = $em->getRepository("User");
				$userRepo->persist($this->getApp()->getSession()->getUser());
			}

			$loginRoute = $this->getApp()->getRouter()->getRoute("index");

			return new RedirectResponse($loginRoute->get("path"));
		}

		public function doLogoutAction() {
			$this->getApp()->getSession()->getUser()->logout();
			$this->getApp()->getSession()->getFlashBag()->add("login_message", "Bye!");

			return $this->toLogin();
		}
	}