<?php

/**
 * @file plugins/importexport/crossref/classes/CrossRefExportDom.inc.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportDom
 * @ingroup plugins_importexport_crossref_classes
 *
 * @brief CrossRef XML export format implementation.
 */


if (!class_exists('JatsDom')) {
	import('plugins.generic.jatsXmlEditor.classes.JatsDom');
}

// XML attributes
define('MATHML_MML' , 'http://www.w3.org/1998/Math/MathML');
define('XLINK_XMLNS' , 'http://www.w3.org/1999/xlink');
define('JATS_SCHEMAVERSION' , '1.1d1');
define('JATS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('JATS_ARTICLE_TYPE' , 'research-article');

class JatsExportDom extends JatsDom {

	/** @var $bodyTags array */
	var $bodyTags;


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $request Request
	 * @param $plugin DOIExportPlugin
	 * @param $journal Journal
	 * @param $article Article
	 */
	function JatsExportDom(&$request, &$plugin, &$journal, &$article, $bodyTags=null) {
		// Configure the DOM.
		parent::JatsDom($request, $plugin, $journal, $article);

		$this->bodyTags = $bodyTags;
	}


	//
	// Public methods
	//
	/**
	 * @see DOIExportDom::generate()
	 */
	function &generate() {
		$journal =& $this->getJournal();

		// Create the XML document and its root element.
		$doc =& $this->getDoc();
		$rootElement =& $this->rootElement();
		XMLCustomWriter::appendChild($doc, $rootElement);

		// Create Head Node and all parts inside it
		$head =& $this->_generateFrontDom($doc, $journal);
		// attach it to the root node
		XMLCustomWriter::appendChild($rootElement, $head);

		if($this->bodyTags) {
			// Create Body Node and all parts inside it
			$body =& $this->_generateBodyDom($doc);
			// attach it to the root node
			XMLCustomWriter::appendChild($rootElement, $body);
		}

		return $doc;
	}

	//
	// Implementation of template methods from DOIExportDom
	//
	/**
	 * @see DOIExportDom::getRootElementName()
	 */
	function getRootElementName() {
		return 'article';
	}

	/**
	 * @see DOIExportDom::getNamespace()
	 */
	function getNamespace() {
		return JATS_ARTICLE_TYPE;
	}

	/**
	 * @see DOIExportDom::getXmlSchemaVersionn()
	 */
	function getXmlSchemaVersion() {
		return JATS_SCHEMAVERSION;
	}

	/**
	 * @see DOIExportDom::getXmlSchemaLocation()
	 */
	function getXmlSchemaLocation() {
		return MATHML_MML;
	}

	///**
	// * @see DOIExportDom::retrievePublicationObjects()
	// */
	//function &retrievePublicationObjects(&$object) {
	//    // Initialize local variables.
	//    $nullVar = null;
	//    $journal =& $this->getJournal();
	//    $cache =& $this->getCache();

	//    // Retrieve basic OJS objects.
	//    $publicationObjects = parent::retrievePublicationObjects($object);

	//    // Retrieve additional related objects.
	//    // For articles: no additional objects needed for CrossRef:
	//    // galleys are not considered and
	//    // supp files will be retrieved when crating the XML
	//    // Note: article issue is already retrieved by the parent method
	//    if (is_a($object, 'PublishedArticle')) {
	//        $article =& $publicationObjects['article'];
	//    }

	//    // For issues: Retrieve all articles of the issue:
	//    if (is_a($object, 'Issue')) {
	//        // Articles by issue.
	//        assert(isset($publicationObjects['issue']));
	//        $issue =& $publicationObjects['issue'];
	//        $publicationObjects['articlesByIssue'] =& $this->retrieveArticlesByIssue($issue);
	//    }

	//    return $publicationObjects;
	//}


	//
	// Private helper methods
	//
	/**
	 * Generate the <front> tag that accompanies each submission
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @return XMLNode
	 */
	function &_generatefrontDom(&$doc, &$journal) {
		$head =& XMLCustomWriter::createElement($doc, 'front');

		// Journal META
		$journalMeta = XMLCustomWriter::createElement($doc, 'journal-meta');
		$journalId = XMLCustomWriter::createChildWithText($doc, $journalMeta, 'journal-id', $journal->getSetting('initials', $journal->getPrimaryLocale()));
		XMLCustomWriter::setAttribute($journalId, 'journal-id-type', 'publisher');
		XMLCustomWriter::appendChild($head, $journalMeta);
		XMLCustomWriter::createChildWithText($doc, $journalMeta, 'issn', $journal->getSetting('printIssn'));
		$publisher = XMLCustomWriter::createElement($doc, 'publisher');
		XMLCustomWriter::createChildWithText($doc, $publisher, 'publisher-name', $journal->getSetting('publisherInstitution'));
		XMLCustomWriter::appendChild($journalMeta, $publisher);

		// Article META
		$articleMeta = XMLCustomWriter::createElement($doc, 'article-meta');
		$articleId = XMLCustomWriter::createChildWithText($doc, $articleMeta, 'article-id', $this->_article->getPubId('doi'));
		XMLCustomWriter::setAttribute($articleId, 'pub-id-type', 'other');
		XMLCustomWriter::appendChild($head, $articleMeta);
		$titleGroup = XMLCustomWriter::createElement($doc, 'title-group');
		XMLCustomWriter::createChildWithText($doc, $titleGroup, 'article-title', $this->_article->getTitle($this->_article->getLocale()));
		XMLCustomWriter::appendChild($articleMeta, $titleGroup);
		$contribGroup = XMLCustomWriter::createElement($doc, 'contrib-group');

		/* AuthorList */
		$contributorsNode =& XMLCustomWriter::createElement($doc, 'contributors');
		$isFirst = true;
		foreach ($this->_article->getAuthors() as $author) {
			$authorNode =& $this->_generateAuthorDom($doc, $author, $isFirst);
			$isFirst = false;
			XMLCustomWriter::appendChild($contribGroup, $authorNode);
		}
		XMLCustomWriter::appendChild($articleMeta, $contribGroup);

		/* Abstract */
		$articleId = XMLCustomWriter::createChildWithText($doc, $articleMeta, 'abstract', '<p>'.String::html2utf(strip_tags($this->_article->getAbstract($journal->getPrimaryLocale()))).'<p>');

		return $head;
	}

	/**
	 * Generate the <body> tag that accompanies each submission
	 * @param $doc XMLNode
	 * @return XMLNode
	 */
	function &_generateBodyDom(&$doc) {
		import('plugins.generic.jatsXmlEditor.classes.JatsParagraph');
		import('plugins.generic.jatsXmlEditor.classes.JatsSection');

		$body =& XMLCustomWriter::createElement($doc, 'body');

		if ($this->bodyTags) {
			foreach($this->bodyTags as $sectionArray) {
				$section = new JatsSection();
				$section->objectFromArray($sectionArray);

				$sectionTag = XMLCustomWriter::createElement($doc, 'sec');
				if ($section->title != ''){
					XMLCustomWriter::createChildWithText($doc, $sectionTag, 'title', $section->title);
				}
				if ($section->secType){
					XMLCustomWriter::setAttribute($sectionTag, 'sec-type', $section->secType);
				}
				if (count($section->paragraphs)) {
					foreach($section->paragraphs as $paragraphArray) {
						$paragraph = new JatsParagraph();
						$paragraph->objectFromArray($paragraphArray);

						if ($paragraph->content != ''){
							XMLCustomWriter::createChildWithText($doc, $sectionTag, 'p', $paragraph->content);
						}
					}
				}
				XMLCustomWriter::appendChild($body, $sectionTag);
			}
		}

		return $body;
	}

	/**
	 * Generate author node
	 * @param $doc XMLNode
	 * @param $author Author
	 * @return XMLNode
	 */
	function &_generateAuthorDom(&$doc, &$author, $isFirst = false) {
	    $authorNode =& XMLCustomWriter::createElement($doc, 'contrib');
	    XMLCustomWriter::setAttribute($authorNode, 'contrib-type', 'author');
	    $authorName =& XMLCustomWriter::createElement($doc, 'name');
	    XMLCustomWriter::createChildWithText($doc, $authorName, 'surname', ucfirst($author->getLastName()));
	    XMLCustomWriter::createChildWithText($doc, $authorName, 'given_name', ucfirst($author->getFirstName()).(($author->getMiddleName())?' '.ucfirst($author->getMiddleName()):''));
	    XMLCustomWriter::appendChild($authorNode, $authorName);

	    if ($author->getData('orcid')) {
	        $authorORCID = XMLCustomWriter::createChildWithText($doc, $authorNode, 'contrib-id', $author->getData('orcid'));
	        XMLCustomWriter::setAttribute($authorORCID, 'contrib-id-type', 'orcid');
	    }

	    return $authorNode;
	}
}

?>
