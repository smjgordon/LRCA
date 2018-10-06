<?php
require_once 'm_news.php';
require_once 'p_exceptions.php';

class PostNewsController {
	public function __construct() {
		$this->_feedId = @$_POST['fid'] or $this->_feedId = '';
		$this->_title = trim(@$_POST['title']) or $this->_title = '';
		$this->_homepageText = trim(@$_POST['homepageText']) or $this->_homepageText = '';
		$this->_detailText = trim(@$_POST['detailText']) or $this->_detailText = '';
		$this->_attach = (@$_POST['attach'] == 'on');
		$this->_confirm = (@$_POST['confirm'] == 'yes');
		
		$this->_newAttachments = $this->_attachmentDisplayNames = [];
		for ($i = 0; $i < 5; ++$i) {
			if (isset($_FILES["att$i"])) {
				$file = $_FILES["att$i"];
				if ($file['tmp_name']) {
					$this->_newAttachments[] = $file;
				} else {
					$this->_newAttachments[] = null;
				}
			} else {
				$this->_newAttachments[] = null;
			}
			$this->_attachmentDisplayNames[] = @$_POST["att$i" . 'dname'];
		}
		if (empty($this->_newAttachments)) $this->_newAttachments = null;

		$this->_pulledAttachments = [];
		for ($i = 0; $i < 5; ++$i) {
			$name = @$_POST["att$i" . 'name'];
			$type = @$_POST["att$i" . 'type'];
			$tmpName = @$_POST["att$i" . 'tmp'];
			$displayName = @$_POST["att$i" . 'dname'];
			if (!empty($name) && !empty($type) && !empty($tmpName) && !empty($displayName)) {
				$this->_pulledAttachments[] = [
					'name' => $name,
					'type' => $type,
					'tmp_name' => $tmpName,
					'display_name' => $displayName
				];
			} else if (!empty($name) || !empty($type) || !empty($tmpName)) {
				errorPage(HttpStatus::BadRequest);
			}
		}
	}

	public function process() {
		global $CurrentUser, $UploadTempFolder, $Database;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (!$this->_feedId) throw new UserInputException(UserInputException::NewsPostMissingFeed);

			if (!is_numeric($this->_feedId)) throw new ModelAccessException(ModelAccessException::BadNewsFeedId);
			$this->_feedId = (int) $this->_feedId;
			$feed = NewsFeed::loadById($this->_feedId);

			if ($this->_title == '') throw new UserInputException(UserInputException::NewsPostMissingTitle);
			if ($this->_homepageText == '' && $this->_detailText == '') {
				throw new UserInputException(UserInputException::NewsPostMissingText);
			}

			self::validateMarkup($this->_homepageText);
			self::validateMarkup($this->_detailText);

			// info entered
			$post = new NewsPost();
			$post->setFeed($feed);
			$post->setUser($CurrentUser);
			$post->setDate(time());
			$post->setTitle($this->_title);
			$post->setHomepageText($this->_homepageText);
			$post->setDetailText($this->_detailText);
			
			$attachmentFolder = $UploadTempFolder . 'lrca_' . $_COOKIE['session'] . '/';

			// new attachments
			if (!$this->_confirm && $this->_newAttachments != null) {
				// first, validate combination of display name and attachment
				foreach ($this->_newAttachments as $i => $att) {
					if ($att != null && empty($this->_attachmentDisplayNames[$i])) {
						throw new UserInputException(UserInputException::NewsPostAttachmentWithoutDisplayName, $post);
					} else if ($att == null && !empty($this->_attachmentDisplayNames[$i])) {
						throw new UserInputException(UserInputException::NewsPostDisplayNameWithoutAttachment, $post);
					}
				}
				
				$this->_pulledAttachments = [];
				
				if (!is_dir($attachmentFolder)) mkdir($attachmentFolder);
				
				foreach ($this->_newAttachments as $i => $att) if ($att != null) {
					$tmpName = $att['tmp_name'];
					move_uploaded_file($tmpName, $attachmentFolder . basename($tmpName));
					$att['display_name'] = $this->_attachmentDisplayNames[$i];
					$this->_pulledAttachments[] = $att;
					
					// for the preview
					$post->addAttachment($att['name'], $att['type'], $att['tmp_name'], $att['display_name']);
				}
			}
			
			if ($this->_confirm) {
				foreach ($this->_pulledAttachments as $att) {
					if (!is_file($attachmentFolder . $att['tmp_name'])) {
						throw new UserInputException('Attachment failed due to a loss of session data.  Please try again.', $post);
					}
					$post->addAttachment($att['name'], $att['type'], $att['tmp_name'], $att['display_name']);
				}
				$Database->beginTransaction();
				$post->save();
				$Database->commit();
				redirect(HttpStatus::RedirectSeeOther, 'posted');
			} else {
				return $post;
			}
		} else {
			// initial entry to page
			return null;
		}
	}

	public function feedId() { return $this->_feedId; }
	public function title() { return $this->_title; }
	public function homepageText() { return $this->_homepageText; }
	public function detailText() { return $this->_detailText; }
	public function attachmentRequested() { return $this->_attach; }

	public function carryForwardAttachments() {
		if ($this->_pulledAttachments != null) {
			foreach ($this->_pulledAttachments as $i => $att) {
			?>	<input type="hidden" name="att<?php echo $i; ?>name" value="<?php echo htmlspecialchars($att['name']); ?>" />
				<input type="hidden" name="att<?php echo $i; ?>type" value="<?php echo htmlspecialchars($att['type']); ?>" />
				<input type="hidden" name="att<?php echo $i; ?>tmp" value="<?php echo htmlspecialchars(basename($att['tmp_name'])); ?>" />
				<input type="hidden" name="att<?php echo $i; ?>dname" value="<?php echo htmlspecialchars(basename($att['display_name'])); ?>" />
			<?php
			}
		}
	}
	
	private $_feedId, $_title, $_homepageText, $_detailText, $_confirm, $_attach;
	private $_newAttachments, $_attachmentDisplayNames, $_pulledAttachments;

	private static function validateMarkup($text) {
		$linkStart = strpos($text, '[[');
		if ($linkStart !== FALSE) {
			if (!(substr($text, $linkStart, 7) == '[[http:' || substr($text, $linkStart, 8) == '[[https:')) {
				throw new UserInputException(UserInputException::IllegalUri);
			}
		}
	}
}
?>