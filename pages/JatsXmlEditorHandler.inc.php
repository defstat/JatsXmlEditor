<?php

/**
 * @file plugins/generic/jatsXmlEditor/pages/JatsXmlEditorHandler.inc.php
 *
 * Copyright (c) National Documentation Centre
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsXmlEditorHandler
 * @ingroup plugins_generic_jatsXmlEditor
 *
 * @brief Handle requests for editor books for review functions.
 */

import('classes.handler.Handler');

class JatsXmlEditorHandler extends Handler {

	/**
	 * Create Jats XML.
	 */
	function createJatsXML($args = array(), &$request) {
		$jatsPlugin =& PluginRegistry::getPlugin('generic', JATS_XML_EDITOR_PLUGIN_NAME);
		$journal =& Request::getJournal();

		$articleId = $args[0];

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId, $journal->getId());

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);

		// Create the XML DOM and document.
		$jatsPlugin->import('classes.JatsExportDom');
		$dom = new JatsExportDom($request, $jatsPlugin, $journal, $article);
		$doc =& $dom->generate($objects);
		if ($doc === false) {
			$errors =& $dom->getErrors();
			return false;
		}

		$fileId = $articleFileManager->writeSuppFile('jats.xml', XMLCustomWriter::getXML($doc), 'xml', null, true);

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');

		$suppFile = new SuppFile();
		$suppFile->setArticleId($articleId);
		$suppFile->setFileId($fileId);
		$suppFileDao->insertSuppFile($suppFile);

		$articleDao->updateSetting($articleId, 'JatsXMLEditorPlugin::file', true, 'bool');
		$articleDao->updateSetting($articleId, 'JatsXMLEditorPlugin::fileId', $fileId, 'int');

		$request->redirect(null, 'editor', 'submission', $articleId);
	}

	/**
	 * Update Jats XML.
	 */
	function updateJatsXML($args = array(), &$request) {
		$jatsPlugin =& PluginRegistry::getPlugin('generic', JATS_XML_EDITOR_PLUGIN_NAME);
		$journal =& Request::getJournal();
		$articleId = $args[0];

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId, $journal->getId());

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);

		// Create the XML DOM and document.
		$jatsPlugin->import('classes.JatsExportDom');
		$dom = new JatsExportDom($request, $jatsPlugin, $journal, $article);
		$doc =& $dom->generate($objects);
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
				$suppFile->setArticleId($articleId);
				$suppFile->setFileId($fileId);

				$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
				$suppFileDao->insertSuppFile($suppFile);

				$articleDao->updateSetting($articleId, 'JatsXMLEditorPlugin::fileId', $fileId, 'int');
			}


		} else {
			$fileId = $articleFileManager->writeSuppFile('jats.xml', XMLCustomWriter::getXML($doc), 'xml', null, true);

			$suppFile = new SuppFile();
			$suppFile->setArticleId($articleId);
			$suppFile->setFileId($fileId);

			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$suppFileDao->insertSuppFile($suppFile);

			$articleDao->updateSetting($articleId, 'JatsXMLEditorPlugin::fileId', $fileId, 'int');
		}

		$request->redirect(null, 'editor', 'submission', $articleId);
	}

	/**
	 * Update Jats XML.
	 */
	function editJatsXML($args = array(), &$request) {
		$jatsPlugin =& PluginRegistry::getPlugin('generic', JATS_XML_EDITOR_PLUGIN_NAME);
		$jatsPlugin->import('EditXMLForm');
		$jatsPlugin->import('classes/JatsSection');
		$jatsPlugin->import('classes/JatsParagraph');

		if ($request->getUserVar('addSection')){
			$form = new EditXMLForm($jatsPlugin, $request);
			$form->readInputData();

			$sections = $form->getData('sections');

			if (!$sections) {
				$sections = array();
			}

			$newJatsSectionObject = new JatsSection();

			array_push($sections,$newJatsSectionObject->objectToArray());

			$form->setData('sections', $sections);
		} elseif ($request->getUserVar('addParagraph')) {
			$sectionId = key($request->getUserVar('addParagraph'));

			$form = new EditXMLForm($jatsPlugin, $request);
			$form->readInputData();

			$sections = $form->getData('sections');

			$newJatsParagraphObject = new JatsParagraph();
			$section = new JatsSection();
			$section->objectFromArray($sections[$sectionId]);
			$section->addParagraph($newJatsParagraphObject->objectToArray());
			$sections[$sectionId] = $section->objectToArray();

			$form->setData('sections', $sections);

		} elseif ($request->getUserVar('deleteParagraph')) {
			$sectionId = key($request->getUserVar('deleteParagraph'));
			$paragraphId = key($request->getUserVar('deleteParagraph')[$sectionId]);

			$form = new EditXMLForm($jatsPlugin, $request);
			$form->readInputData();

			$sections = $form->getData('sections');

			$section = new JatsSection();
			$section->objectFromArray($sections[$sectionId]);
			$section->deleteParagraphByIndex($paragraphId);
			$sections[$sectionId] = $section->objectToArray();

			$form->setData('sections', $sections);

		} elseif ($request->getUserVar('deleteSection')) {
			$sectionId = key($request->getUserVar('deleteSection'));

			$form = new EditXMLForm($jatsPlugin, $request);
			$form->readInputData();

			$sections = $form->getData('sections');

			array_splice($sections, $sectionId, 1);

			$form->setData('sections', $sections);

		} elseif ($request->getUserVar('save')) {
			$form = new EditXMLForm($jatsPlugin, $request);
			$form->readInputData();
			$form->execute();

		} else {

			$form = new EditXMLForm($jatsPlugin, $request);
			$form->initData();
		}

		$form->display();
	}

	/**
	 * Ensure that we have a journal, plugin is enabled, and user is editor.
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$bfrPlugin =& PluginRegistry::getPlugin('generic', JATS_XML_EDITOR_PLUGIN_NAME);

		if (!isset($bfrPlugin)) return false;

		if (!$bfrPlugin->getEnabled()) return false;

		if (!Validation::isEditor($journal->getId())) Validation::redirectLogin();;

		return parent::authorize($request, $args, $roleAssignments);
	}

	///**
	// * Setup common template variables.
	// * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	// */
	//function setupTemplate($subclass = false) {
	//    $templateMgr =& TemplateManager::getManager();
	//    $pageCrumbs = array(
	//        array(
	//            Request::url(null, 'user'),
	//            'navigation.user'
	//        ),
	//        array(
	//            Request::url(null, 'editor'),
	//            'user.role.editor'
	//        )
	//    );

	//    if ($subclass) {
	//        $returnPage = Request::getUserVar('returnPage');

	//        if ($returnPage != null) {
	//            $validPages =& $this->getValidReturnPages();
	//            if (!in_array($returnPage, $validPages)) {
	//                $returnPage = null;
	//            }
	//        }

	//        $pageCrumbs[] = array(
	//            Request::url(null, 'editor', 'booksForReview', $returnPage),
	//            AppLocale::Translate('plugins.generic.booksForReview.displayName'),
	//            true
	//        );
	//    }
	//    $templateMgr->assign('pageHierarchy', $pageCrumbs);

	//    $bfrPlugin =& PluginRegistry::getPlugin('generic', JATS_XML_EDITOR_PLUGIN_NAME);
	//    $templateMgr->addStyleSheet(Request::getBaseUrl() . '/' . $bfrPlugin->getStyleSheet());
	//}
}

?>
