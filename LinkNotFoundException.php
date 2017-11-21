<?php
class LinkNotFoundException extends Exception {
	private $linkId;

	public function __construct($linkId) {
		$this->linkId = $linkId;
	}

	public function getLinkId() {
		return $this->linkId();
	}
}
?>
