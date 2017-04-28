<?php

/**
 * Paragraph short summary.
 *
 * Paragraph description.
 *
 * @version 1.0
 * @author defstathiou
 */
class JatsParagraph {
	/** @var String */
	var $content = '';

	/** @var $id String UUID */
	var $id;

	/**
	 * Constructor
	 * @param $content String
	 */
	function Paragraph($content='') {
		$this->id = uniqid();
		$this->content = $content;
	}

	function setContent($content) {
		$this->content = $content;
	}

	function objectToArray() {
		return array(
			'content' => $this->content
		);
	}

	function objectFromArray($array){
		$this->content = $array['content'];
	}
}
