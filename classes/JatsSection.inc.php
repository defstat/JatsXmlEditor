<?php

/**
 * @file plugins/jatsXmlEditor/classes/JatsSection.inc.inc.php
 *
 * Copyright (c) National Documentation Centre
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsSection
 *
 */



class JatsSection {
	/** @var $paragraphs array of Paragraph */
	var $paragraphs = array();

	/** @var $id String UUID */
	var $id;

	/** @var $title String */
	var $title;

	/** @var $secType String of enum SECTION_TYPES */
	var $secType;


	/**
	 * Constructor
	 */
	function Section($title = '', $secType = SECTION_TYPES::__default) {
		$this->id = uniqid();
		$this->title = $title;
		$this->secType = $secType;
	}

	function addParagraph($paragraph) {
		array_push($this->paragraphs, $paragraph);
	}

	function deleteParagraphByIndex($index) {
		array_splice($this->paragraphs, $index, 1);
	}

	function setTitle($title) {
		$this->title = $title;
	}

	function setSecType($secType) {
		$this->secType = $secType;
	}

	function objectToArray() {
		$sectionTypes = new SECTION_TYPES();

		$paragraphsArray = array();

		foreach($this->paragraphs as $paragraph){
			$jatsParagraph = new JatsParagraph();
			$jatsParagraph->objectFromArray($paragraph);
			array_push($paragraphsArray, $jatsParagraph->objectToArray());
		}

		return array(
			'title' => $this->title,
			'secType' => $sectionTypes->getKey($this->secType),
			'paragraphs' => $paragraphsArray
		);
	}

	function objectFromArray($array){
		$sectionTypes = new SECTION_TYPES();

		$this->title = $array['title'];
		$this->secType = $sectionTypes->getValue($array['secType']);
		if ($array['paragraphs']) {
			foreach($array['paragraphs'] as $paragraph) {
				$paragraphAdd = new JatsParagraph();
				$paragraphAdd->objectFromArray($paragraph);
				$this->addParagraph($paragraphAdd->objectToArray());
			}
		}
	}
}

class SECTION_TYPES {
    const __default = '';

    const CASES = 'cases';
    const CONCLUSIONS = 'conclusions';
    const DISCUSSION = 'discussion';
    const INTRO = 'intro';
    const MATERIALS = 'materials';
    const METHODS = 'methods';
    const RESULTS = 'results';
    const SUBJECTS = 'subjects';
    const SUPPLEMENTARY_MATERIAL = "supplementary-material";

	function toArray() {
		return array(
			'DEFAULT' => '',
			'CASES' => SECTION_TYPES::CASES,
			'CONCLUSIONS' => SECTION_TYPES::CONCLUSIONS,
			'DISCUSSION' => SECTION_TYPES::DISCUSSION,
			'INTRO' => SECTION_TYPES::INTRO,
			'MATERIALS' => SECTION_TYPES::MATERIALS,
			'METHODS' => SECTION_TYPES::METHODS,
			'RESULTS' => SECTION_TYPES::RESULTS,
			'SUBJECTS' => SECTION_TYPES::SUBJECTS,
			'SUPPLEMENTARY_MATERIAL' => SECTION_TYPES::SUPPLEMENTARY_MATERIAL,
		);
	}

	function getValue($key) {
		$keyValues = $this->toArray();
		return $keyValues[$key];
	}

	function getKey($value) {
		$keyValues = $this->toArray();
		return array_search($value, $keyValues);
	}
}
