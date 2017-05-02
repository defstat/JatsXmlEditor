<?php

/**
 * @file plugins/jatsXmlEditor/classes/JatsParagraph.inc.inc.php
 *
 * Copyright (c) National Documentation Centre
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsParagraph
 *
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
