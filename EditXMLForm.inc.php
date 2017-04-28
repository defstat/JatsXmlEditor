<?php

/**
 * @file plugins/generic/jatsXmlEditor/JatsXmlEditorForm.inc.php
 *
 * Copyright (c) 2017 National Documentation Centre
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsXmlEditorForm
 * @ingroup plugins_generic_jatsXmlEditor
 *
 * @brief Form for JatsXmlEditor
 */


import('lib.pkp.classes.form.Form');

class EditXMLForm extends Form {
	/** @var $request object */
	var $request;

	/** @var $articleId int */
	var $articleId;

	/** @var $plugin JatsXmlEditorPlugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 */
	function EditXMLForm(&$plugin, $request) {
		parent::Form($plugin->getTemplatePath() . 'editXMLForm.tpl');

		$this->request =& $request;
		$this->articleId = $request->getUserVar('articleId');
		$this->plugin =& $plugin;
		$journal =& $request->getJournal();

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get the names of fields for which data should be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$request =& $this->request;
		$user =& $request->getUser();
		$journal =& $request->getJournal();


		$sectionTypeObject = new SECTION_TYPES();
		$sectionTypes = $sectionTypeObject->toArray();

		$templateMgr->assign('sectionTypes', $sectionTypes);

		parent::display();
	}

	/**
	 * Initialize form data for a new form.
	 */
	function initData() {
		$this->plugin->import("classes.JatsSection");
		$this->plugin->import("classes.JatsParagraph");

		$this->_data = array();

		$request =& $this->request;
		$journal =& $request->getJournal();
		$this->_data = array(
			'sections' => array()
		);

		$journal =& Request::getJournal();
		$articleId = $this->articleId;
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId, $journal->getId());

		$fileId = $article->getData('JatsXMLEditorPlugin::fileId');

		if ($fileId) {
			$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
			$suppFile =& $articleFileDao->getArticleFile($fileId);

			if ($suppFile){
				$xmlParser = new XMLParser();
				$tree = $xmlParser->parse($suppFile->getFilePath());

				$bodyTags = array();

				if ($tree) {
					foreach ($tree->getChildren() as $setting) {
						$nameNode =& $setting->getName();

						if (isset($nameNode) && $nameNode=='body') {
							foreach ($setting->getChildren() as $bodyTag) {
								$this->getSectionChildrenToObjectArray($bodyTags, $bodyTag);
							}

						}
					}

					$xmlParser->destroy();
				}

				$this->_data = array(
					'sections' => $bodyTags
				);
			}
		}
	}

	function getSectionChildrenToObjectArray(&$array, $xmlNode, &$jatsSection=null) {
		$name = $xmlNode->getName();

		if ($xmlNode->getChildren()){
			if (isset($name) && $name=='sec') {
				$section = new JatsSection();

				$sectionAttributes = $xmlNode->getAttributes();
				if (is_array($sectionAttributes)) {
					foreach($sectionAttributes as $key => $value) {
						if (isset($key) && $key=='sec-type') {
							$section->setSecType($value);
						}
					}
				}

				foreach ($xmlNode->getChildren() as $child) {
					if (isset($child->name) && $child->name=='p') {

						$paragraph = new JatsParagraph();
						$paragraph->setContent($child->getValue());
						$section->addParagraph($paragraph->objectToArray());
					} elseif (isset($child->name) && $child->name=='title') {
						$section->title = $child->getValue();
					}
				}

				array_push($array, $section->objectToArray());
			}
		}
	}

	function getChildrenToArray(&$array, $xmlNode) {
		if ($xmlNode->getChildren()){
			$array[$xmlNode->getName()] = array();

			foreach ($xmlNode->getChildren() as $child) {
				$this->getChildrenToArray($array[$xmlNode->getName()], $child);
			}
		} else {
			$array[$xmlNode->getName()] = $xmlNode->getValue();
		}
	}



	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'sections'
			)
		);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$application =& PKPApplication::getApplication();
		$user =& $this->request->getUser();
		$router =& $this->request->getRouter();
		$journal =& Request::getJournal();

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($this->articleId, $journal->getId());

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($this->articleId);

		// Create the XML DOM and document.
		$this->plugin->import('classes.JatsExportDom');
		$dom = new JatsExportDom($this->request, $this->plugin, $journal, $article, $this->getData('sections'));
		$doc =& $dom->generate();
		if ($doc === false) {
			$errors =& $dom->getErrors();
			return false;
		}

		$fileId = $article->getData('JatsXMLEditorPlugin::fileId');
		if ($fileId) {
			$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
			$suppFile =& $articleFileDao->getArticleFile($fileId);

			if ($suppFile){
				$articleFileManager->writeSuppFile('jats.xml', XMLCustomWriter::getXML($doc), 'xml', $fileId, true);
			} else {
				$fileId = $articleFileManager->writeSuppFile('jats.xml', XMLCustomWriter::getXML($doc), 'xml', null, true);

				$suppFile = new SuppFile();
				$suppFile->setArticleId($this->articleId);
				$suppFile->setFileId($fileId);

				$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
				$suppFileDao->insertSuppFile($suppFile);

				$articleDao->updateSetting($this->articleId, 'JatsXMLEditorPlugin::fileId', $fileId, 'int');
			}


		} else {
			$fileId = $articleFileManager->writeSuppFile('jats.xml', XMLCustomWriter::getXML($doc), 'xml', null, true);

			$suppFile = new SuppFile();
			$suppFile->setArticleId($this->articleId);
			$suppFile->setFileId($fileId);

			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$suppFileDao->insertSuppFile($suppFile);

			$articleDao->updateSetting($this->articleId, 'JatsXMLEditorPlugin::fileId', $fileId, 'int');
		}

		$this->request->redirect(null, 'editor', 'submission', $this->articleId);
	}
}

?>
