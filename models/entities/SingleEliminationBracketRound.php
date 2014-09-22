<?php

	class SingleEliminationBracketRound extends BracketRound {

		private $isByesRound = false;

		public function setIsByesRound($isByesRound) {
			$this->isByesRound = $isByesRound;
		}

		public function isByesRound() {
			return $this->isByesRound;
		}

	}