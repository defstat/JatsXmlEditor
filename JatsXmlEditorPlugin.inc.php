<?php

/**
 * @file plugins/generic/jatsXmlEditor/JatsXmlEditorPlugin.inc.php
 *
 * Copyright (c) 2017 National Documentation Centre
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsXmlEditorPlugin
 * @ingroup plugins_generic_jatsXmlEditor
 *
 * @brief JatsXmlEditorPlugin
 */


import('classes.plugins.GenericPlugin');

class JatsXmlEditorPlugin extends GenericPlugin {

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.customBlockManager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Get the handler path for this plugin.
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * Get the handler path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';

	}

	/**
	 * Enable editor book for review management.
	 */
	function setupEditorHandler($hookName, $params) {
		$page =& $params[0];

		if ($page == 'editor') {
			$op =& $params[1];

			if ($op) {
				$editorPages = array(
					'createJatsXML',
					'updateJatsXML',
					'editJatsXML'

				);

				if (in_array($op, $editorPages)) {
					define('HANDLER_CLASS', 'JatsXmlEditorHandler');
					define('JATS_XML_EDITOR_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_EDITOR);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'JatsXmlEditorHandler.inc.php';
				}
			}
		}
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		switch ($verb) {
			case 'settings':
				$this->import('CustomBlockPlugin');

				$journal =& Request::getJournal();

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$pageCrumbs = array(
					array(
						Request::url(null, 'user'),
						'navigation.user'
					),
					array(
						Request::url(null, 'manager'),
						'user.role.manager'
					),
					array(
						Request::url(null, 'manager', 'plugins'),
						__('manager.plugins'),
						true
					)
				);
				$templateMgr->assign('pageHierarchy', $pageCrumbs);

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());
				$form->readInputData();

				if (Request::getUserVar('addBlock')) {
					// Add a block
					$editData = true;
					$blocks = $form->getData('blocks');
					array_push($blocks, '');
					$form->_data['blocks'] = $blocks;

				} else if (($delBlock = Request::getUserVar('delBlock')) && count($delBlock) == 1) {
					// Delete an block
					$editData = true;
					list($delBlock) = array_keys($delBlock);
					$delBlock = (int) $delBlock;
					$blocks = $form->getData('blocks');
					if (isset($blocks[$delBlock]) && !empty($blocks[$delBlock])) {
						$deletedBlocks = explode(':', $form->getData('deletedBlocks'));
						array_push($deletedBlocks, $blocks[$delBlock]);
						$form->setData('deletedBlocks', join(':', $deletedBlocks));
					}
					array_splice($blocks, $delBlock, 1);
					$form->_data['blocks'] = $blocks;

				} else if ( Request::getUserVar('save') ) {
					$editData = true;
					$form->execute();

					// Enable the block plugin and place it in the right sidebar
					if ($form->validate()) {
						foreach ($form->getData('blocks') as $block) {
							$blockPlugin = new CustomBlockPlugin($block, $this->getName());

							// Default the block to being enabled
							if (!is_bool($blockPlugin->getEnabled())) {
								$blockPlugin->setEnabled(true);
							}

							// Default the block to the right sidebar
							if (!is_numeric($blockPlugin->getBlockContext())) {
								$blockPlugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
							}
						}
					}
				} else {
					$form->initData();
				}

				if ( !isset($editData) && $form->validate()) {
					$form->execute();
					$form->display();
					exit;
				} else {
					$form->display();
					exit;
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Display book for review metadata link in submission summary page.
	 */
	function displayCreateJatsXMLLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$submission =& $smarty->get_template_vars("submission");

			if ($submission) {
				$articleId = $submission->getId();
				$journal =& Request::getJournal();
				$journalId = $journal->getId();
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				$article = $articleDao->getArticle($articleId, $journalId);

				$templateMgr = TemplateManager::getManager();
				if (!$submission->getData('JatsXMLEditorPlugin::file')){
					$output = '<p><a href="' . $templateMgr->smartyUrl(array('page'=>'editor', 'op'=>'createJatsXML', 'path'=>$submission->getId(), 'callbackUrl'=>'test'), $smarty) . '" class="action">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.jatsXmlEditor.createJatsFileLink'), $smarty) . '</a></p>';
				} else {
					//$output = '<p><a href="' . $templateMgr->smartyUrl(array('page'=>'editor', 'op'=>'updateJatsXML', 'path'=>$submission->getId(), 'callbackUrl'=>'test'), $smarty) . '" class="action">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.jatsXmlEditor.updateJatsFileLink'), $smarty) . '</a> |
					//    <a href="' .$templateMgr->smartyUrl(array('page'=>'editor', 'op'=>'editJatsXML', 'path'=>$submission->getId(), 'callbackUrl'=>'edit', 'articleId'=>$submission->getId()), $smarty) . '" class="action">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.jatsXmlEditor.editJatsFileLink'), $smarty) . '</a></p>';

					$output = '<p><a href="' .$templateMgr->smartyUrl(array('page'=>'editor', 'op'=>'editJatsXML', 'path'=>$submission->getId(), 'callbackUrl'=>'edit', 'articleId'=>$submission->getId()), $smarty) . '" class="action">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.jatsXmlEditor.editJatsFileLink'), $smarty) . '</a></p>';
				}
			}
		}

		//$templateMgr =& TemplateManager::getManager();
		//$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

		return false;
	}


	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();

		// Editor link to book for review metadata in submission view
		HookRegistry::register('Templates::Submission::Metadata::Metadata::AdditionalEditItems', array($this, 'displayCreateJatsXMLLink'));

		// Handler for editor books for review pages
		HookRegistry::register('LoadHandler', array($this, 'setupEditorHandler'));

		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'JatsXmlEditorPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.JatsXmlEditor.displayName');
	}

	function getDescription() {
		return __('plugins.generic.JatsXmlEditor.description');
	}

	/**
	 * Save the submitted form
	 * @param $args array
	 */
	function saveSubmit($args, $request) {
		$templateMgr =& TemplateManager::getManager();

		$this->import('JatsXmlEditorForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$form = new JatsXmlEditorForm($this, $request);
		} else {
			$form =& new JatsXmlEditorForm($this, $request);
		}
		$form->readInputData();
		$formLocale = $form->getFormLocale();

		if ($request->getUserVar('addSection')) {
			$editData = true;
			$authors = $form->getData('authors');
			$authors[] = array();
			$form->setData('authors', $authors);
		} else if (($delAuthor = $request->getUserVar('delAuthor')) && count($delAuthor) == 1) {
			$editData = true;
			list($delAuthor) = array_keys($delAuthor);
			$delAuthor = (int) $delAuthor;
			$authors = $form->getData('authors');
			if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
				$deletedAuthors = explode(':', $form->getData('deletedAuthors'));
				array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
				$form->setData('deletedAuthors', join(':', $deletedAuthors));
			}
			array_splice($authors, $delAuthor, 1);
			$form->setData('authors', $authors);

			if ($form->getData('primaryContact') == $delAuthor) {
				$form->setData('primaryContact', 0);
			}
		} else if ($request->getUserVar('moveAuthor')) {
			$editData = true;
			$moveAuthorDir = $request->getUserVar('moveAuthorDir');
			$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
			$moveAuthorIndex = (int) $request->getUserVar('moveAuthorIndex');
			$authors = $form->getData('authors');

			if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
				$tmpAuthor = $authors[$moveAuthorIndex];
				$primaryContact = $form->getData('primaryContact');
				if ($moveAuthorDir == 'u') {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
					$authors[$moveAuthorIndex - 1] = $tmpAuthor;
					if ($primaryContact == $moveAuthorIndex) {
						$form->setData('primaryContact', $moveAuthorIndex - 1);
					} else if ($primaryContact == ($moveAuthorIndex - 1)) {
						$form->setData('primaryContact', $moveAuthorIndex);
					}
				} else {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
					$authors[$moveAuthorIndex + 1] = $tmpAuthor;
					if ($primaryContact == $moveAuthorIndex) {
						$form->setData('primaryContact', $moveAuthorIndex + 1);
					} else if ($primaryContact == ($moveAuthorIndex + 1)) {
						$form->setData('primaryContact', $moveAuthorIndex);
					}
				}
			}
			$form->setData('authors', $authors);
		} else if ($request->getUserVar('uploadSubmissionFile')) {
			$editData = true;
			$tempFileId = $form->getData('tempFileId');
			$tempFileId[$formLocale] = $form->uploadSubmissionFile('submissionFile');
			$form->setData('tempFileId', $tempFileId);
		}

		if ($request->getUserVar('createAnother') && $form->validate()) {
			$form->execute();
			$request->redirect(null, 'manager', 'generic', array('plugin', $this->getName()));
		} else if (!isset($editData) && $form->validate()) {
			$form->execute();
			$templateMgr->display($this->getTemplatePath() . 'submitSuccess.tpl');
		} else {
			$form->display();
		}

	}

	/**
	 * Extend the {url ...} for smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array('plugin',$this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

}

?>
