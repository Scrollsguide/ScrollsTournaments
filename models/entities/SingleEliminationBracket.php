<?php
	class SingleEliminationBracket extends Bracket {
		
		private $parents = array();

		private $child;

		public function getParents() {
			return $this->parents;
		}

		public function addParent(SingleEliminationBracket $parent) {
			if (count($this->parents) === 2) {
				throw new Exception("Bracket cannot have more than two parents.");
			}

			$this->parents[] = $parent;
		}

		public function getChild() {
			return $this->child;
		}

		public function setChild(SingleEliminationBracket $child) {
			$this->child = $child;
		}
		
	}