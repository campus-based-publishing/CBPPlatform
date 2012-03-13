<?php

/**
 * @file classes/sword/OJSSwordDeposit.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSSwordDeposit
 * @ingroup sword
 *
 * @brief Class providing a SWORD deposit wrapper for OJS articles
 */

// $Id: CaptchaManager.inc.php,v 1.7 2010/01/22 20:42:41 asmecher Exp $


require_once('./lib/pkp/lib/swordapp/swordappclient.php');
require_once('./lib/pkp/lib/swordapp/swordappentry.php');
require_once('./lib/pkp/lib/swordapp/packager_mets_swap.php');

class OJSSwordDeposit {
	/** @var $package SWORD deposit METS package */
	var $package;

	/** @var $outPath Complete path and directory name to use for package creation files */
	var $outPath;

	/** @var $journal */
	var $journal;

	/** @var $section */
	var $section;

	/** @var $issue */
	var $issue;

	/**
	 * Constructor.
	 * Create a SWORD deposit object for an OJS article.
	 */
	function OJSSwordDeposit(&$article) {
		// Create a directory for deposit contents
		$this->outPath = tempnam('/tmp', 'sword');
		unlink($this->outPath);
		mkdir($this->outPath);
		mkdir($this->outPath . '/files');

		// Create a package
		$this->package = new PackagerMetsSwap(
			$this->outPath,
			'files',
			$this->outPath,
			'deposit.zip'
		);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$this->journal =& $journalDao->getJournal($article->getJournalId());

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$this->section =& $sectionDao->getSection($article->getSectionId());

		$this->article =& $article;
	}

	/**
	 * Register the article's metadata with the SWORD deposit.
	 */
	function setMetadata() {
		$this->package->setCustodian($this->journal->getSetting('contactName'));
		$this->package->setTitle(html_entity_decode($this->article->getTitle($this->journal->getPrimaryLocale()), ENT_QUOTES, 'UTF-8'));
		$this->package->setAbstract(html_entity_decode(strip_tags($this->article->getAbstract($this->journal->getPrimaryLocale())), ENT_QUOTES, 'UTF-8'));
		$this->package->setType($this->section->getIdentifyType($this->journal->getPrimaryLocale()));

		// The article can be published or not. Support either.
		if (is_a($this->article, 'PublishedArticle')) {
			$doi = $this->article->getDOI();
			if ($doi !== null) $this->package->setIdentifier($doi);
		}

		foreach ($this->article->getAuthors() as $author) {
			$creator = $author->getFullName(true);
			$affiliation = $author->getAffiliation($this->journal->getPrimaryLocale());
			if (!empty($affiliation)) $creator .= "; $affiliation";
			$this->package->addCreator($creator);
		}

		// The article can be published or not. Support either.
		if (is_a($this->article, 'PublishedArticle')) {
			$plugin =& PluginRegistry::loadPlugin('citationFormats', 'bibtex');
			$this->package->setCitation(html_entity_decode(strip_tags($plugin->fetchCitation($this->article, $this->issue, $this->journal)), ENT_QUOTES, 'UTF-8'));
		}
	}

	/**
	 * Add a file to a package. Used internally.
	 */
	function _addFile(&$file) {
		$targetFilename = $this->outPath . '/files/' . $file->getFilename();
		copy($file->getFilePath(), $targetFilename);
		$this->package->addFile($file->getFilename(), $file->getFileType());
	}

	/**
	 * Add all article galleys to the deposit package.
	 */
	function addGalleys() {
		foreach ($this->article->getGalleys() as $galley) {
			$this->_addFile($galley);
		}
	}

	/**
	 * Add the single most recent editorial file to the deposit package.
	 * @return boolean true iff a file was successfully added to the package
	 */
	function addEditorial() {
		// Move through signoffs in reverse order and try to use them.
		foreach (array('SIGNOFF_LAYOUT', 'SIGNOFF_COPYEDITING_FINAL', 'SIGNOFF_COPYEDITING_AUTHOR', 'SIGNOFF_COPYEDITING_INITIAL') as $signoffName) {
			$file =& $this->article->getFileBySignoffType($signoffName);
			if ($file) {
				$this->_addFile($file);
				return true;
			}
			unset($file);
		}

		// If that didn't work, try the Editor Version.
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($this->article->getId());
		$file =& $sectionEditorSubmission->getEditorFile();
		if ($file) {
			$this->_addFile($file);
			return true;
		}
		unset($file);

		// Try the Review Version.
		$file =& $sectionEditorSubmission->getReviewFile();
		if ($file) {
			$this->_addFile($file);
			return true;
		}
		unset($file);

		// Otherwise, don't add anything (best not to go back to the
		// author version, as it may not be vetted)
		return false;
	}

	/**
	 * Build the package.
	 */
	function createPackage() {
		return $this->package->create();
	}

	/**
	 * Deposit the package.
	 * @param $url string SWORD deposit URL
	 * @param $username string SWORD deposit username (i.e. email address for DSPACE)
	 * @param $password string SWORD deposit password
	 */
	function deposit($url, $username, $password) {
		$client = new SWORDAPPClient();
		$response = $client->deposit(
			$url, $username, $password,
			'',
			$this->outPath . '/deposit.zip',
			'http://purl.org/net/sword-types/METSDSpaceSIP',
			'application/zip', false, true
		);
		return $response;
	}

	/**
	 * Clean up after a deposit, i.e. removing all created files.
	 */
	function cleanup() {
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		$fileManager->rmtree($this->outPath);
	}
}

?>
