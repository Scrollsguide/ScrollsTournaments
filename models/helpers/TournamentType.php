<?php
	
	class TournamentType {
		
		const ROUND_ROBIN = 0;
		const SINGLE_ELIMINATION = 1;
		const DOUBLE_ELIMINATION = 2;

		public static function valid($state){
			return $state >= 0 && $state <= 2;
		}
		
	}