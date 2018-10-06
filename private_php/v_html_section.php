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
			$backToSection = backToLevel(2);
		?>	<ul><?php
				foreach ($divs as $div) {
					?><li><a href="<?php echo $backToSection . $div->urlName(); ?>/"><?php echo htmlspecialchars($div->name()); ?></a></li><?php
				}
			?></ul>
		<?php
		}
	}
	private $_section;
}
?>