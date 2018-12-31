<?php
class NewsFeed {
	private static $all;

	public static function loadAll() {
		global $Database;

		if (!isset(NewsFeed::$all)) {
			$sql = 'SELECT * FROM news_feed ORDER BY sequence';

			$stmt = $Database->query($sql);
			while ($row = $stmt->fetch()) {
				$newFeed = new NewsFeed();
				$newFeed->populateFromDbRow($row);
				NewsFeed::$all[$newFeed->_id] = $newFeed;
			}
		}
		return NewsFeed::$all;
	}

	public static function loadByUri($uri) {
		$uriParts = array_slice(explode('/', trim($uri, '/')), -1);
		if (count($uriParts) != 1) throw new ModelAccessException(ModelAccessException::BadUrl, $uri);

		foreach (NewsFeed::loadAll() as $feed) {
			if ($uriParts[0] == $feed->urlName()) return $feed;
		}

		throw new ModelAccessException(ModelAccessException::BadNewsFeedUrlName, $uriParts[0]);
	}

	public static function loadById($id) {
		$feeds = NewsFeed::loadAll();
		if (isset($feeds[$id])) {
			return $feeds[$id];
		} else {
			throw new ModelAccessException(ModelAccessException::BadNewsFeedId, $id);
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = (int) $row['feed_id'];
		$this->_urlName = $row['url_name'];
		$this->_name = $row['name'];
	}

	public function id() { return $this->_id; }
	public function urlName() { return $this->_urlName; }
	public function name() { return $this->_name; }

	private $_id, $_urlName, $_name;
}

class NewsPost {
	public static function loadByFeed($feed) {
		global $Database;

		$result = [];
		$sql = '
			SELECT np.*, Count(na.attachment_id) AS attach
			FROM news_post np
				LEFT JOIN news_attachment na ON np.post_id = na.post_id
			WHERE feed_id = ?
			GROUP BY np.post_id, np.feed_id, np.user_id, np.posted_date, np.title, np.homepage_text, np.detail_text
			ORDER BY posted_date DESC, post_id DESC';
		$stmt = $Database->prepare($sql);
		$stmt->execute([$feed->id()]);

		while ($row = $stmt->fetch()) {
			$newPost = new NewsPost();
			$newPost->_feed = $feed;
			$newPost->populateFromDbRow($row);
			$result[] = $newPost;
		}

		return $result;
	}

	/*public static function loadAll() {
		global $Database;

		$result = [];
		$sql = 'SELECT * FROM news_post ORDER BY posted_date DESC, post_id DESC';
		$stmt = $Database->query($sql);

		while ($row = $stmt->fetch()) {
			$newPost = new NewsPost();
			$newPost->populateFromDbRow($row);
			$result[] = $newPost;
		}

		return $result;
	}*/

	public static function loadRecent($count, $homepageOnly = true) {
		global $Database;

		$result = [];
		$sql = '
			SELECT np.*, Count(na.attachment_id) AS attach
			FROM news_post np
				LEFT JOIN news_attachment na ON np.post_id = na.post_id';
		if ($homepageOnly) $sql .= " WHERE homepage_text <> ''";
		$sql .= '
			GROUP BY np.post_id, np.feed_id, np.user_id, np.posted_date, np.title, np.homepage_text, np.detail_text
			ORDER BY posted_date DESC, post_id DESC LIMIT ?';
		$stmt = $Database->prepare($sql);
		$stmt->execute([$count]);

		while ($row = $stmt->fetch()) {
			$newPost = new NewsPost();
			$newPost->populateFromDbRow($row);
			$result[] = $newPost;
		}

		return $result;
	}

	function populateFromDbRow($row) {
		$this->_id = $row['post_id'];
		$this->_user = User::loadById($row['user_id'], true);
		$this->_date = strtotime($row['posted_date']);
		$this->_title = $row['title'];
		$this->_homepageText = $row['homepage_text'];
		$this->_detailText = $row['detail_text'];
		if (!isset($this->_feed)) $this->_feed = NewsFeed::loadById($row['feed_id']);

		// for now, this is to just note that we have attachments - they can be loaded later if needed
		if ($row['attach'] > 0) $this->_attachments = [];
	}

	public function id() { return $this->_id; }
	public function feed() { return $this->_feed; }
	public function user() { return $this->_user; }
	public function date() { return $this->_date; }
	public function title() { return $this->_title; }
	public function homepageText() { return $this->_homepageText; }
	public function detailText() { return $this->_detailText; }

	public function setFeed($feed) { $this->_feed = $feed; }
	public function setUser($user) { $this->_user = $user; }
	public function setDate($date) { $this->_date = $date; }
	public function setTitle($title) { $this->_title = $title; }
	public function setHomepageText($text) { $this->_homepageText = $text; }
	public function setDetailText($text) { $this->_detailText = $text; }

	private $_id = 0, $_feed, $_user, $_date, $_title, $_homepageText, $_detailText;
	private $_attachments = null;

	public function addAttachment($fileName, $mimeType, $tmpName, $displayName) {
		if ($this->_attachments == null) $this->_attachments = [];
		$this->_attachments[] = new NewsAttachment($this, count($this->_attachments) + 1, $fileName, $mimeType, $tmpName, $displayName);
	}

	public function hasAttachment() {
		return $this->_attachments !== null;
	}

	public function attachments() {
		if ($this->_attachments === []) $this->_attachments = NewsAttachment::loadByPost($this);
		return $this->_attachments;
	}

	public function save($silentFail = false) {
		global $Database;

		if (!isset($this->_id) || $this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO news_post(feed_id, user_id, posted_date,
					title, homepage_text, detail_text)
				VALUES(?, ?, ?, ?, ?, ?)');

			$stmt->execute([$this->_feed->id(), $this->_user->id(), date('c', $this->_date),
				$this->_title, $this->_homepageText, $this->_detailText]);
			$this->_id = $Database->lastInsertId();

			foreach ($this->_attachments as $att) $att->save();

		} else if (!$silentFail) {
			throw new Exception('Saving to an existing news post not implemented');
		}
	}
}

class NewsAttachment {
	public function __construct($post = null, $sequence = null, $fileName = null, $mimeType = null, $tmpName = null, $displayName = null) {
		$this->_post = $post;
		$this->_sequence = $sequence;
		$this->_fileName = $fileName;
		$this->_mimeType = $mimeType;
		$this->_tmpName = $tmpName;
		$this->_displayName = $displayName;
	}

	public static function loadByPost($post) {
		global $Database;

		$result = [];
		$sql = 'SELECT * FROM news_attachment WHERE post_id = ? ORDER BY sequence';
		$stmt = $Database->prepare($sql);
		$stmt->execute([$post->id()]);

		while ($row = $stmt->fetch()) {
			$newAttachment = new NewsAttachment($post);
			$newAttachment->populateFromDbRow($row);
			$result[] = $newAttachment;
		}

		return $result;
	}

	//public post() { return $this->_post; }
	//public sequence { return $this->_sequence; }
	public function fileName() { return $this->_fileName; }
	//public mimeType { return $this->_mimeType; }
	//public tmpName { return $this->_tmpName; }
	public function displayName() { return $this->_displayName; }

	private $_id, $_post, $_sequence, $_fileName, $_mimeType, $_tmpName, $_displayName;

	public function save() {
		if (!isset($this->_id) || $this->_id == 0) {
			global $Database, $UploadTempFolder, $UriBase;

			$tempFolder = $UploadTempFolder . 'lrca_' . $_COOKIE['session'] . '/';
			$finalFolder = $_SERVER['DOCUMENT_ROOT'] . $UriBase . 'att/';

			$stmt = $Database->prepare('
				INSERT INTO news_attachment(post_id, sequence, file_name, mime_type, display_name)
				VALUES(?, ?, ?, ?, ?)');

			$stmt->execute([$this->_post->id(), $this->_sequence, $this->_fileName, $this->_mimeType, $this->_displayName]);
			$this->_id = $Database->lastInsertId();

			// prefix the original filename with the internal ID, to ensure uniqueness
			$this->_fileName = $this->_id . '_' . $this->_fileName;
			rename($tempFolder . $this->_tmpName, $finalFolder . $this->_fileName);

			$stmt = $Database->prepare('
				UPDATE news_attachment SET file_name = ?
				WHERE attachment_id = ?');

			$stmt->execute([$this->_fileName, $this->_id]);
		} else {
			throw new Exception('Saving to an existing news attachment not implemented');
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = (int) $row['attachment_id'];
		$this->_sequence = $row['sequence'];
		$this->_fileName = $row['file_name'];
		$this->_mimeType = $row['mime_type'];
		$this->_displayName = $row['display_name'];
	}
}
?>