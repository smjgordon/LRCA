<?php
require_once 'u_text.php';

class HtmlNewsPostView {
	public function __construct($post) {
		$this->_post = $post;
	}
	
	public function fullPost() {
		$post = $this->_post;
		$result = '<h3 id="p' . $post->id() . '">' . htmlspecialchars($post->title()) . '</h3>';
		$result .= '<p class="sub">' . htmlspecialchars($post->user()->fullName()) . ' ' . formatDate($post->date()) . '</p>';
		$result .= $this->formatArticleText($post->homepageText());
		$result .= $this->formatArticleText($post->detailText());
		
		if ($post->attachments()) foreach ($post->attachments() as $att) {
			$result .= '<p><a href="' . backToLevel(0) . 'news/att/' . htmlspecialchars($att->fileName()) . '">'
				. htmlspecialchars($att->displayName()) . '</a></p>';
		}
		return $result;
	}
	
	public function homePagePost() {
		$post = $this->_post;
		if ($post->feed()->id() == 1) {
			// hide news feed name for Main News
			$result = '<h3>' . htmlspecialchars($post->title())
				. ' – ' . formatDate($post->date()) . '</h3>';
		} else {
			$result = '<h3>' . htmlspecialchars($post->feed()->name()) . ' – ' . htmlspecialchars($post->title())
				. ' – ' . formatDate($post->date()) . '</h3>';
		}
		if ($post->detailText() == '' && !$post->hasAttachment()) {
			$result .= $this->formatArticleText($post->homepageText());
		} else {
			$result .= $this->formatArticleText($post->homepageText(), false);
			$result .= ' <a href="' . backToLevel(0) . 'news/' . $post->feed()->urlName() . '#p' . $post->id() . '">Full article</a></p>';
		}
		return $result;
	}
	
	private $_post;
	
	private function formatArticleText($text, $closeParagraph = true) {
		if ($text == '') return '';
		
		$lines = explode("\n", $text);
		$newParagraph = true;
		$result = '';
		
		foreach ($lines as $line) {
			$line = htmlspecialchars(trim($line));
			if ($line == '') {
				if (!$newParagraph) {
					$result .= '</p>';
					$newParagraph = true;
				}
			} else {
				if ($newParagraph) {
					$result .= '<p>';
					$newParagraph = false;
				} else {
					$result .= '<br />';
				}
				
				// translate markup
				// [[url|link text]]
				$line = preg_replace('/\[\[([^|]+)\|([^]]+)\]\]/', '<a href="$1">$2</a>', $line);
				// **strong text**
				$line = preg_replace('/\*\*((\*?[^*])+)\*\*/', '<strong>$1</strong>', $line);
				// __emphasised text__
				$line = preg_replace('/__((_?[^_])+)__/', '<em>$1</em>', $line);
				
				$result .= $line;
			}
		}
		if ($closeParagraph) $result .= '</p>';
		return $result;
	}
}
?>