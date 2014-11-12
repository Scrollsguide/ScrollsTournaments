<?php
	class Visibility {

		const INVISIBLE = 0;
		const VISIBLE = 1;
		const WITH_URL = 2;

		public static function valid($visibility){
			return $visibility >= 0 && $visibility <= 2;
		}

	}