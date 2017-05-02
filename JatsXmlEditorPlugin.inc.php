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

		return parent::manage($verb, $args, $message, $messageParams);

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
