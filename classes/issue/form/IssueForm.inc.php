
<?php

/**
 * @defgroup issue_form
 */

/**
 * @file classes/form/IssueForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueForm
 * @ingroup issue_form
 * @see Issue
 *
 * @brief Form to create or edit an issue
 */

// $Id$


import('lib.pkp.classes.form.Form');
import('classes.issue.Issue'); // Bring in constants
import('classes.CBPPlatform.conv.phpthumb.ThumbLib');

class IssueForm extends Form {

	/**
	 * Constructor.
	 */
	function IssueForm($template) {
		parent::Form($template);
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of fields for which localization should be used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		return $issueDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$journal =& Request::getJournal();

		// set up the accessibility options pulldown
		$templateMgr->assign('enableDelayedOpenAccess', $journal->getSetting('enableDelayedOpenAccess'));

		$templateMgr->assign('accessOptions', array(
			ISSUE_ACCESS_OPEN => Locale::Translate('editor.issues.openAccess'),
			ISSUE_ACCESS_SUBSCRIPTION => Locale::Translate('editor.issues.subscription')
		));

		$templateMgr->assign('enablePublicIssueId', $journal->getSetting('enablePublicIssueId'));

		parent::display();
	}

	/**
	 * Validate the form
	 */
	function validate($issueId = 0) {
		if ($this->getData('showVolume')) {
			$this->addCheck(new FormValidatorCustom($this, 'volume', 'required', 'editor.issues.volumeRequired', create_function('$volume', 'return ($volume > 0);')));
		}

		if ($this->getData('showNumber')) {
			$this->addCheck(new FormValidatorCustom($this, 'number', 'required', 'editor.issues.numberRequired', create_function('$number', 'return ($number > 0);')));
		}

		if ($this->getData('showYear')) {
			$this->addCheck(new FormValidatorCustom($this, 'year', 'required', 'editor.issues.yearRequired', create_function('$year', 'return ($year > 0);')));
		}

		if ($this->getData('showTitle')) {
			$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'editor.issues.titleRequired'));
		}

		// check if public issue ID has already used
		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$publicIssueId = $this->getData('publicIssueId');
		if ($publicIssueId && $issueDao->publicIssueIdExists($publicIssueId, $issueId, $journal->getId())) {
			$this->addError('publicIssueId', Locale::translate('editor.issues.issuePublicIdentificationExists'));
			$this->addErrorField('publicIssueId');
		}

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$type = $publicFileManager->getUploadedFileType('coverPage');
			if (!$publicFileManager->getImageExtension($type)) {
				$this->addError('coverPage', Locale::translate('editor.issues.invalidCoverPageFormat'));
				$this->addErrorField('coverPage');
			}
		}

		if ($publicFileManager->uploadedFileExists('styleFile')) {
			$type = $publicFileManager->getUploadedFileType('styleFile');
			if ($type != 'text/plain' && $type != 'text/css') {
				$this->addError('styleFile', Locale::translate('editor.issues.invalidStyleFormat'));
			}
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current issue.
	 * returns issue id that it initialized the page with
	 */
	function initData($issueId = null) {
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');

		// retrieve issue by id, if not specified, then select first unpublished issue
		if (isset($issueId)) {
			$issue =& $issueDao->getIssueById($issueId);
		}

		if (isset($issue)) {
			$this->_data = array(
				'title' => $issue->getTitle(null), // Localized
				'volume' => $issue->getVolume(),
				'number' => $issue->getNumber(),
				'year' => $issue->getYear(),
				'datePublished' => $issue->getDatePublished(),
				'description' => $issue->getDescription(null), // Localized
				'publicIssueId' => $issue->getPublicIssueId(),
				'accessStatus' => $issue->getAccessStatus(),
				'openAccessDate' => $issue->getOpenAccessDate(),
				'showVolume' => $issue->getShowVolume(),
				'showNumber' => $issue->getShowNumber(),
				'showYear' => $issue->getShowYear(),
				'showTitle' => $issue->getShowTitle(),
				'fileName' => $issue->getFileName(null), // Localized
				'originalFileName' => $issue->getOriginalFileName(null), // Localized
				'coverPageDescription' => $issue->getCoverPageDescription(null), // Localized
				'coverPageAltText' => $issue->getCoverPageAltText(null), // Localized
				'showCoverPage' => $issue->getShowCoverPage(null), // Localized
				'hideCoverPageArchives' => $issue->getHideCoverPageArchives(null), // Localized
				'hideCoverPageCover' => $issue->getHideCoverPageCover(null), // Localized
				'styleFileName' => $issue->getStyleFileName(),
				'originalStyleFileName' => $issue->getOriginalStyleFileName(),
				'ISBN'=> $CBPPlatformDao->getIssueISBN($issue->getIssueId())
			);
			return $issue->getId();

		} else {
			$journal =& Request::getJournal();
			$showVolume = $journal->getSetting('publicationFormatVolume');
			$showNumber = $journal->getSetting('publicationFormatNumber');
			$showYear = $journal->getSetting('publicationFormatYear');
			$showTitle = $journal->getSetting('publicationFormatTitle');

			$this->setData('showVolume', $showVolume);
			$this->setData('showNumber', $showNumber);
			$this->setData('showYear', $showYear);
			$this->setData('showTitle', $showTitle);

			// set up the default values for volume, number and year
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getLastCreatedIssue($journal->getId());

			if (isset($issue)) {
				$volumePerYear = $journal->getSetting('volumePerYear');
				$issuePerVolume = $journal->getSetting('issuePerVolume');
				$number = $issue->getNumber();
				$volume = $issue->getVolume();
				$year = $issue->getYear();

				if ($showVolume && $showNumber && $showYear) {
					$number++;
					if ($issuePerVolume && $number > $issuePerVolume) {
						$number = 1;
						$volume++;
						if ($volumePerYear && $volume > $volumePerYear) {
							$volume = 1;
							$year++;
						}
					}
				} elseif ($showVolume && $showNumber) {
					$number++;
					if ($issuePerVolume && $number > $issuePerVolume) {
						$number = 1;
						$volume++;
					}
				} elseif ($showVolume && $showYear) {
					$number = 0;
					$volume++;
					if ($volumePerYear && $volume > $volumePerYear) {
						$volume = 1;
						$year++;
					}
				} elseif ($showYear) {
					$volume = $number = 0;
					$year++;
				} else {
					$year = $volume = $number = 0;
				}
			} else {
				$volume = $journal->getSetting('initialVolume');
				$number = $journal->getSetting('initialNumber');
				$year = $journal->getSetting('initialYear');
			}

			switch ($journal->getSetting('publishingMode')) {
				case PUBLISHING_MODE_SUBSCRIPTION:
				case PUBLISHING_MODE_NONE:
					$accessStatus = ISSUE_ACCESS_SUBSCRIPTION;
					break;
				case PUBLISHING_MODE_OPEN:
				default:
					$accessStatus = ISSUE_ACCESS_OPEN;
					break;
			}

			$this->_data = array(
				'showVolume' => $showVolume,
				'showNumber' => $showNumber,
				'showYear' => $showYear,
				'showTitle' => $showTitle,
				'volume' => $volume,
				'number' => $number,
				'year' => $year,
				'accessStatus' => $accessStatus
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'title',
			'volume',
			'number',
			'year',
			'description',
			'publicIssueId',
			'accessStatus',
			'enableOpenAccessDate',
			'showVolume',
			'showNumber',
			'showYear',
			'showTitle',
			'fileName',
			'originalFileName',
			'coverPageDescription',
			'coverPageAltText',
			'showCoverPage',
			'hideCoverPageArchives',
			'hideCoverPageCover',
			'articles',
			'styleFileName',
			'originalStyleFileName'
		));

		$this->readUserDateVars(array('datePublished', 'openAccessDate'));

		$this->addCheck(new FormValidatorCustom($this, 'showVolume', 'required', 'editor.issues.issueIdentificationRequired', create_function('$showVolume, $showNumber, $showYear, $showTitle', 'return $showVolume || $showNumber || $showYear || $showTitle ? true : false;'), array($this->getData('showNumber'), $this->getData('showYear'), $this->getData('showTitle'))));

	}

	/**
	 * Save issue settings.
	 */
	function execute($issueId = 0) {
				
		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		if ($issueId) {
			$issue = $issueDao->getIssueById($issueId);
			$isNewIssue = false;
		} else {
			$issue = new Issue();
			$isNewIssue = true;
		}
		$volume = $this->getData('volume');
		$number = $this->getData('number');
		$year = $this->getData('year');

		$showVolume = $this->getData('showVolume');
		$showNumber = $this->getData('showNumber');
		$showYear = $this->getData('showYear');
		$showTitle = $this->getData('showTitle');

		$issue->setJournalId($journal->getId());
		$issue->setTitle($this->getData('title'), null); // Localized
		$issue->setVolume(empty($volume) ? 0 : $volume);
		$issue->setNumber(empty($number) ? 0 : $number);
		$issue->setYear(empty($year) ? 0 : $year);
		if (!$isNewIssue) {
			$issue->setDatePublished($this->getData('datePublished'));
		}
		$issue->setDescription($this->getData('description'), null); // Localized
		$issue->setPublicIssueId($this->getData('publicIssueId'));
		$issue->setShowVolume(empty($showVolume) ? 0 : $showVolume);
		$issue->setShowNumber(empty($showNumber) ? 0 : $showNumber);
		$issue->setShowYear(empty($showYear) ? 0 : $showYear);
		$issue->setShowTitle(empty($showTitle) ? 0 : $showTitle);
		$issue->setCoverPageDescription($this->getData('coverPageDescription'), null); // Localized
		$issue->setCoverPageAltText($this->getData('coverPageAltText'), null); // Localized
		$showCoverPage = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('showCoverPage'));
		foreach (array_keys($this->getData('coverPageDescription')) as $locale) {
			if (!array_key_exists($locale, $showCoverPage)) {
				$showCoverPage[$locale] = 0;
			}
		}
		$issue->setShowCoverPage($showCoverPage, null); // Localized

		$hideCoverPageArchives = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('hideCoverPageArchives'));
		foreach (array_keys($this->getData('coverPageDescription')) as $locale) {
			if (!array_key_exists($locale, $hideCoverPageArchives)) {
				$hideCoverPageArchives[$locale] = 0;
			}
		}
		$issue->setHideCoverPageArchives($hideCoverPageArchives, null); // Localized

		$hideCoverPageCover = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('hideCoverPageCover'));
		foreach (array_keys($this->getData('coverPageDescription')) as $locale) {
			if (!array_key_exists($locale, $hideCoverPageCover)) {
				$hideCoverPageCover[$locale] = 0;
			}
		}
		$issue->setHideCoverPageCover($hideCoverPageCover, null); // Localized

		$issue->setAccessStatus($this->getData('accessStatus'));
		if ($this->getData('enableOpenAccessDate')) $issue->setOpenAccessDate($this->getData('openAccessDate'));
		else $issue->setOpenAccessDate(null);

		// if issueId is supplied, then update issue otherwise insert a new one
		if ($issueId) {
			$issue->setIssueId($issueId);
			$issueDao->updateIssue($issue);
		} else {
			$issue->setPublished(0);
			$issue->setCurrent(0);

			$issueId = $issueDao->insertIssue($issue);
			$issue->setIssueId($issueId);
		}
		
		//%CBP% add Issue ISBN to table
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$CBPPlatformDao->setIssueISBN($issueId, Request::getUserVar('isbn'));

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$journal = Request::getJournal();
			$originalFileName = $publicFileManager->getUploadedFileName('coverPage');
			$type = $publicFileManager->getUploadedFileType('coverPage');
			$newFileName = 'cover_issue_' . $issueId . '_' . $this->getFormLocale() . $publicFileManager->getImageExtension($type);
			$publicFileManager->uploadJournalFile($journal->getId(), 'coverPage', $newFileName);
			$issue->setOriginalFileName($publicFileManager->truncateFileName($originalFileName, 127), $this->getFormLocale());
			$issue->setFileName($newFileName, $this->getFormLocale());
			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $newFileName);
			$issue->setWidth($width, $this->getFormLocale());
			$issue->setHeight($height, $this->getFormLocale());
			//%CBP% generate thumbnail for web view, also
			try {
				$options = array('resizeUp' => true, 'jpegQuality' => 80);
				$thumb = PhpThumbFactory::create($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $newFileName, $options);
				$thumb->resize(350, 350);
				$thumb->save($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . 'cover_issue_' . $issueId . '_' . $this->getFormLocale() . "-t" . $publicFileManager->getImageExtension($type));
			} catch (Exception $e) {
				//do nothing
			}
			$issueDao->updateIssue($issue);
		}

		if ($publicFileManager->uploadedFileExists('styleFile')) {
			$journal = Request::getJournal();
			$originalFileName = $publicFileManager->getUploadedFileName('styleFile');
			$newFileName = 'style_' . $issueId . '.css';
			$publicFileManager->uploadJournalFile($journal->getId(), 'styleFile', $newFileName);
			$issue->setStyleFileName($newFileName);
			$issue->setOriginalStyleFileName($publicFileManager->truncateFileName($originalFileName, 127));
			$issueDao->updateIssue($issue);
		}

		return $issueId;
	}
}

?>
