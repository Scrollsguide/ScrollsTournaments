<?php
	class Invite {
		
		private $tournament_id;
		
		private $code;
		
		public function getTournamentId(){
			return (int)$this->tournament_id;
		}
		
		public function setTournamentId($tournament_id){
			$this->tournament_id = $tournament_id;
		}
		
		public function getCode(){
			return $this->code;
		}
		
		public function setCode($code){
			$this->code = $code;
		}
	
	}
	
	class InviteHelper {
	
		public static function generateCode($length = 10){
			$p = '0123456789abcdefghijklmnopqrstuvwxyz';
			
			$out = '';
			for ($i = 0; $i < $length; $i++){
				$out.= $p[rand(0, strlen($p))];
			}
			
			return $out;
		}
	
	}