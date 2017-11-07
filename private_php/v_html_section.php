<?php
require_once 'p_html_functions.php';
require_once 'u_text.php';

class HtmlSectionView {
	public function __construct($section) {
		$this->_section = $section;
	}
	
	function displayName() {
		switch ($this->_section->season()) {
			case Season::Winter:
				return $this->_section->year() . '–' . ($this->_section->year() + 1) . ' ' . $this->_section->name();
				break;
			case Season::Summer:
				return $this->_section->year() . ' ' . $this->_section->name();
		}
	}

	function showDivisionIndex() {
		$divs = $this->_section->divisions();
		
		if (!empty($divs)) {
		?>
			<ul>
				<?php foreach ($divs as $div) { ?>
					<li><a href="division.php?did=<?php echo $div->id(); ?>"><?php echo htmlspecialchars($div->name); ?></a></li>
				<?php } ?>
			</ul>
		<?php
		}
		
		/*global $Database;

		$stmt = $Database->prepare('SELECT division_id, name FROM division WHERE section_id = ? AND year = ? ORDER BY sequence');
		$stmt->execute([$this->id, $this->year]);

		$row = $stmt->fetch();
		if ($row) {
			echo '<ul>';
			do {
				//$anyDivisions = true;
				echo "<li><a href='division.php?did=$row[division_id]'>", htmlspecialchars($row['name']), '</a></li>';
			} while ($row = $stmt->fetch());
			echo '</ul>';
			return true;
		} else {
			return false;
		}*/
	}
	private $_section;
}
?>