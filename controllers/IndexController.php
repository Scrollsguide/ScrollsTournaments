<?php
	class IndexController extends BaseController {
		
		public function indexAction(){
			return $this->render("index.html.twig");
		}
		
		public function p404Action(){
			return $this->p404();
		}
	}