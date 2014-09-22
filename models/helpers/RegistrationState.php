<?php

	class RegistrationState {

		const CLOSED = 0;
		const OPEN = 1;
		const INVITE_ONLY = 2;

		public static function valid($state) {
			return $state >= 0 && $state <= 2;
		}
	}