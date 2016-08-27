<?php
class HtmlDivisionView {
	public function __construct($division) {
		$this->_division = $division;
	}
	
	// TODO: update when/if section is refactored
	public function bodyTitle() {
		return $this->_division->section->displayName() . ' – ' . $this->_division->name;
	}
	
	public function headerTitle() {
		return $this->_division->name . ' – ' . $this->_division->section->displayName();
	}
	
	// TODO: support breakdown by round
	public function breakdown() {
		$months = $this->_division->playedMatchMonths();
		if ($months) {
			echo '<ul>';
			foreach ($months as $yearAndMonth) {
				$monthName = monthNameFromIso($yearAndMonth);
				echo "<li><a href='results_by_month.php?did=$this->id&amp;month=$yearAndMonth'>$monthName</a></li>";
			}
			echo '</ul>';
		}
	}
	
	private _division;
}
?>