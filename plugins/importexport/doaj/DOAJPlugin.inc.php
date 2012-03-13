<?php

/**
 * @file DOAJPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief DOAJ import/export plugin
 */

// $Id$


import('lib.pkp.classes.xml.XMLCustomWriter');

import('classes.plugins.ImportExportPlugin');

define('DOAJ_XSD_URL', 'http://www.doaj.org/schemas/doajArticles.xsd');

class DOAJPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'DOAJPlugin';
	}

	/**
	 * Get the display name for this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.importexport.doaj.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.importexport.doaj.description');
	}

	/**
	 * Display the plugin
	 * @param $args array
	 */
	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);
		$journal =& Request::getJournal();
		
		switch (array_shift($args)) {
			case 'export':
				// export an xml file with the journal's information
				$this->exportJournal($journal);
				break;
			case 'contact':
				// present a form autofilled with journal information to send to the DOAJ representative
				$this->contact($journal);
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	/**
	 * Export a journal's content
	 * @param $journal object
	 * @param $outputFile string
	 */
	function exportJournal(&$journal, $outputFile = null) {
		$this->import('DOAJExportDom');
		$doc =& XMLCustomWriter::createDocument();
		
		$journalNode =& DOAJExportDom::generateJournalDom($doc, $journal);
		$journalNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$journalNode->setAttribute('xsi:noNamespaceSchemaLocation', DOAJ_XSD_URL);
		XMLCustomWriter::appendChild($doc, $journalNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'wb'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"journal-" . $journal->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	/**
	 * Auto-fill the DOAJ form.
	 * @param $journal object
	 */
	function contact(&$journal, $send = false) {
		$user =& Request::getUser();

		$issn = $journal->getSetting('printIssn');

		$paramArray = array(
			'name' => $user->getFullName(),
			'email' => $user->getEmail(),
			'title' => $journal->getLocalizedTitle(),
			'description' => String::html2text($journal->getLocalizedSetting('focusScopeDesc')),
			'url' => $journal->getUrl(),
			'charging' => $journal->getSetting('submissionFee') > 0 ? 'Y' : 'N',
			'issn' => $issn,
			'eissn' => $journal->getSetting('onlineIssn'),
			'pub' => $journal->getSetting('publisherInstitution'),
			'language' => Locale::getLocale(),
			'keywords' => $journal->getLocalizedSetting('searchKeywords'),
			'contact_person' => $journal->getSetting('contactName'),
			'contact_email' => $journal->getSetting('contactEmail')
		);
		$url = 'http://www.doaj.org/doaj?func=suggest&owner=1';
		foreach ($paramArray as $name => $value) {
			$url .= '&' . urlencode($name) . '=' . urlencode($value);
		}
		Request::redirectUrl($url);
	}
}

?>
