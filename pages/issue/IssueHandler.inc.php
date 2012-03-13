<?php

/**
 * @file IssueHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueHandler
 * @ingroup pages_issue
 *
 * @brief Handle requests for issue functions.
 */

// $Id$


import ('classes.issue.IssueAction');
import('classes.handler.Handler');

class IssueHandler extends Handler {
	/**
	 * Constructor
	 **/
	function IssueHandler() {
		parent::Handler();
		
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorCustom($this, false, null, null, create_function('$journal', 'return $journal->getSetting(\'publishingMode\') != PUBLISHING_MODE_NONE;'), array(Request::getJournal())));
	}

	/**
	 * Display about index page.
	 */
	function index($args) {
		$this->current();
	}

	/**
	 * Display current issue page.
	 */
	function current($args = null) {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();
		
		$articleDao =& DAORegistry::getDAO('ArticleDAO');

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getCurrentIssue($journal->getId(), true);
		
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');

		$templateMgr =& TemplateManager::getManager();
		
		//%CBP% if the book hasn't yet being published, don't show the preview
		if ($issue != null && $issue->getPublished()) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			if ($styleFileName = $issue->getStyleFileName()) {
				$templateMgr->addStyleSheet(
					Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $styleFileName
				);
			}

			$issueHeadingTitle = $issue->getIssueIdentification(false, true);
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

			$arg = isset($args[0]) ? $args[0] : '';
			$showToc = ($arg == 'showToc') ? true : false;

			$locale = Locale::getLocale();
			$templateMgr->assign('locale', $locale);

			$coverPagePath = Request::getBaseUrl() . '/';
			$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
			$templateMgr->assign('coverPagePath', $coverPagePath);
			
			if ($issue->getFileName($locale) && $issue->getShowCoverPage($locale) && !$issue->getHideCoverPageCover($locale)) {
				//%CBP% show thumbnail version rather than full-size front cover
				$filename = $issue->getThumbFileName($issue->getFileName($locale));
				$templateMgr->assign('fileName', $filename);
				$templateMgr->assign('coverPageAltText', $issue->getCoverPageAltText($locale));
				$templateMgr->assign('originalFileName', $issue->getOriginalFileName($locale));
				
				$atomistic = $templateMgr->_tpl_vars; $atomistic = $atomistic['atomistic'];
				//%CBP% if it's an 'atomistic' imprint/journal, show the 5 latest books/issues and exit
				if ($atomistic == 1) {
					$this->archive(5);
					exit();
				}
				
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true, $setGalleys);
				
				$templateMgr->assign_by_ref('collection', $collection); 
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
			}

			$templateMgr->assign_by_ref('issue', $issue);
			$templateMgr->assign('showToc', $showToc);
			
			$issueObject = $CBPPlatformDao->getFedoraIssueObjectInformation($issue->getIssueId());
			$objectPid = $issueObject['fedora_namespace'] . ":" . $issueObject['fedora_pid'];
			$objectDsid = $issueObject['fedora_dsid'];
			$templateMgr->assign_by_ref('repositoryObjectPid', $objectPid);
			$templateMgr->assign_by_ref('repositoryObjectDsid', $objectDsid);
			
			$isbn = $CBPPlatformDao->getIssueISBN($issue->getIssueId());
			$templateMgr->assign_by_ref('isbn', $isbn);

			// Subscription Access
			import('classes.issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser($journal);
			$subscribedDomain = IssueAction::subscribedDomain($journal);
			$subscriptionExpiryPartial = $journal->getSetting('subscriptionExpiryPartial');
			
			if ($showToc && $subscriptionRequired && !$subscribedUser && !$subscribedDomain && $subscriptionExpiryPartial) {
				$templateMgr->assign('subscriptionExpiryPartial', true);
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticlesTemp =& $publishedArticleDao->getPublishedArticles($issue->getId());

				$articleExpiryPartial = array();
				foreach ($publishedArticlesTemp as $publishedArticle) {
					$partial = IssueAction::subscribedUser($journal, $issue->getId(), $publishedArticle->getId());
					if (!$partial) IssueAction::subscribedDomain($journal, $issue->getId(), $publishedArticle->getId()); 
					$articleExpiryPartial[$publishedArticle->getId()] = $partial;
				}
				$templateMgr->assign_by_ref('articleExpiryPartial', $articleExpiryPartial);
			}
			
			//%CBP% show associated artefacts (supplementary files) related to the submission
			foreach($publishedArticles as $section) {
				foreach ($section['articles'] as $article) {
					$supplementaryFilesTemp[] = $article->getSuppFiles();
				}
			}
			foreach ($supplementaryFilesTemp as $supplementaryFile) {
				if ($supplementaryFile[0]) {
					$suppFile = $supplementaryFile[0];
					$article = $articleDao->getArticle($suppFile->getArticleId());
					$title = $suppFile->getTitle();
					if (stristr($title['en_US'], "Author Biography") == false) { // don't list author biographies
						$supplementaryFiles[] = array('title' => $title['en_US'], 'articleTitle' => $article->getArticleTitle() , 'articleId' => $article->getArticleId(), 'fileId' => $suppFile->getFileId());
					}
				}
			}
			$templateMgr->assign_by_ref('supplementaryFiles', $supplementaryFiles);
	
				$templateMgr->assign('subscriptionRequired', $subscriptionRequired);
				$templateMgr->assign('subscribedUser', $subscribedUser);
				$templateMgr->assign('subscribedDomain', $subscribedDomain);
				$templateMgr->assign('showGalleyLinks', $journal->getSetting('showGalleyLinks'));
	
				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager =& OJSPaymentManager::getManager();
				if ( $paymentManager->onlyPdfEnabled() ) {
					$templateMgr->assign('restrictOnlyPdf', true);
				}
				if ( $paymentManager->purchaseArticleEnabled() ) {
					$templateMgr->assign('purchaseArticleEnabled', true);
				}			
			} else {
				$issueCrumbTitle = Locale::translate('current.noCurrentIssue');
				$issueHeadingTitle = Locale::translate('current.noCurrentIssue');
			}
	 
			// Display creative commons logo/licence if enabled
			$templateMgr->assign('displayCreativeCommons', $journal->getSetting('includeCreativeCommons'));
			$templateMgr->assign('issueCrumbTitle', $issueCrumbTitle);
			$templateMgr->assign('issueHeadingTitle', $issueHeadingTitle);
			$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'issue', 'current'), 'current.current')));
			$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
			$templateMgr->display('issue/viewPage.tpl');
	}

	/**
	 * Display issue view page.
	 */
	function view($args) {
		$this->validate();
		$this->setupTemplate();

		$issueId = isset($args[0]) ? $args[0] : 0;
		$showToc = isset($args[1]) ? $args[1] : '';

		$journal =& Request::getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		if ($journal->getSetting('enablePublicIssueId')) {
			$issue =& $issueDao->getIssueByBestIssueId($issueId, $journal->getId());
		} else {
			$issue =& $issueDao->getIssueById((int) $issueId, null, true);
		}

		if (!$issue) Request::redirect(null, null, 'current');

		$templateMgr =& TemplateManager::getManager();
		$this->setupIssueTemplate($issue, ($showToc == 'showToc') ? true : false);
		
		//%CBP% get repository object information for download links 
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$issueObject = $CBPPlatformDao->getFedoraIssueObjectInformation($issue->getIssueId());
		$objectPid = $issueObject['fedora_namespace'] . ":" . $issueObject['fedora_pid'];
		$objectDsid = $issueObject['fedora_dsid'];
		$templateMgr->assign_by_ref('repositoryObjectPid', $objectPid);
		$templateMgr->assign_by_ref('repositoryObjectDsid', $objectDsid);
		
		$isbn = $CBPPlatformDao->getIssueISBN($issue->getIssueId());
		$templateMgr->assign_by_ref('isbn', $isbn);

		// Display creative commons logo/licence if enabled
		$templateMgr->assign('displayCreativeCommons', $journal->getSetting('includeCreativeCommons'));
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'issue', 'archive'), 'archive.archives')));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('issue/viewPage.tpl');

	}

	/**
	 * Given an issue, set up the template with all the required variables for
	 * issues/view.tpl to function properly.
	 * @param $issue object The issue to display
	 * @param $showToc boolean iff false and a custom cover page exists,
	 * 	the cover page will be displayed. Otherwise table of contents
	 * 	will be displayed.
	 */
	function setupIssueTemplate(&$issue, $showToc = false) {
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$templateMgr =& TemplateManager::getManager();
		if (isset($issue) && ($issue->getPublished() || Validation::isEditor($journalId) || Validation::isLayoutEditor($journalId) || Validation::isProofreader($journalId)) && $issue->getJournalId() == $journalId) {

			$issueHeadingTitle = $issue->getIssueIdentification(false, true);
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

			$locale = Locale::getLocale();

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$coverPagePath = Request::getBaseUrl() . '/';
			$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
			$templateMgr->assign('coverPagePath', $coverPagePath);
			$templateMgr->assign('locale', $locale);

			if ($issue->getFileName($locale) && $issue->getShowCoverPage($locale) && !$issue->getHideCoverPageCover($locale)) {
				//%LP% show thumbnail version rather than full-size front cover
				$filename = $issue->getThumbFileName($issue->getFileName($locale));
				$templateMgr->assign('fileName', $filename);
				$templateMgr->assign('coverPageAltText', $issue->getCoverPageAltText($locale));
				$templateMgr->assign('originalFileName', $issue->getOriginalFileName($locale));
				
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true);

				$publicFileManager = new PublicFileManager();
				$templateMgr->assign('publishedArticles', $publishedArticles);
			}
			$templateMgr->assign('showToc', $showToc);
			$templateMgr->assign('issueId', $issue->getBestIssueId());
			$templateMgr->assign('issue', $issue);

			// Subscription Access
			import('classes.issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser($journal);
			$subscribedDomain = IssueAction::subscribedDomain($journal);
			$subscriptionExpiryPartial = $journal->getSetting('subscriptionExpiryPartial');
			
			if ($showToc && $subscriptionRequired && !$subscribedUser && !$subscribedDomain && $subscriptionExpiryPartial) {
				$templateMgr->assign('subscriptionExpiryPartial', true);
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticlesTemp =& $publishedArticleDao->getPublishedArticles($issue->getId());

				$articleExpiryPartial = array();
				foreach ($publishedArticlesTemp as $publishedArticle) {
					$partial = IssueAction::subscribedUser($journal, $issue->getId(), $publishedArticle->getId());
					if (!$partial) IssueAction::subscribedDomain($journal, $issue->getId(), $publishedArticle->getId()); 
					$articleExpiryPartial[$publishedArticle->getId()] = $partial;
				}
				$templateMgr->assign_by_ref('articleExpiryPartial', $articleExpiryPartial);
			}

			$templateMgr->assign('subscriptionRequired', $subscriptionRequired);
			$templateMgr->assign('subscribedUser', $subscribedUser);
			$templateMgr->assign('subscribedDomain', $subscribedDomain);
			$templateMgr->assign('showGalleyLinks', $journal->getSetting('showGalleyLinks'));

			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			if ( $paymentManager->onlyPdfEnabled() ) {
				$templateMgr->assign('restrictOnlyPdf', true);
			}
			if ( $paymentManager->purchaseArticleEnabled() ) {
				$templateMgr->assign('purchaseArticleEnabled', true);
			}			

		} else {
			$issueCrumbTitle = Locale::translate('archive.issueUnavailable');
			$issueHeadingTitle = Locale::translate('archive.issueUnavailable');
		}

		if ($styleFileName = $issue->getStyleFileName()) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$templateMgr->addStyleSheet(
				Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journalId) . '/' . $styleFileName
			);
		}
		
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		
		//%CBP% get repository object information for download links
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$issueObject = $CBPPlatformDao->getFedoraIssueObjectInformation($issue->getIssueId());
		$objectPid = $issueObject['fedora_namespace'] . ":" . $issueObject['fedora_pid'];
		$objectDsid = $issueObject['fedora_dsid'];
		$templateMgr->assign_by_ref('repositoryObjectPid', $objectPid);
		$templateMgr->assign_by_ref('repositoryObjectDsid', $objectDsid);
		
		$locale = Config::getVar('i18n', 'locale');
		//%LP% show associated artefacts (supplementary files) related to the submission
		foreach($publishedArticles as $section) {
			foreach ($section['articles'] as $article) {
				$supplementaryFilesTemp[] = $article->getSuppFiles();
			}
		}
		foreach ($supplementaryFilesTemp as $supplementaryFile) {
			if ($supplementaryFile[0]) {
				$suppFile = $supplementaryFile[0];
				$article = $articleDao->getArticle($suppFile->getArticleId());
				$title = $suppFile->getTitle();
				if (stristr($title['en_US'], "Author Biography") == false) { // don't list author biographies
					$supplementaryFiles[] = array('title' => $title['en_US'], 'articleTitle' => $article->getArticleTitle() , 'articleId' => $article->getArticleId(), 'fileId' => $suppFile->getFileId());
				}
			}
		}
		$templateMgr->assign_by_ref('supplementaryFiles', $supplementaryFiles);
		
		$atomistic = $templateMgr->_tpl_vars; $atomistic = $atomistic['atomistic'];
		$templateMgr->assign_by_ref('atomistic', $atomistic);
		
		//%CBP% show issue/book ISBN
		$isbn = $CBPPlatformDao->getIssueISBN($issue->getIssueId());
		$templateMgr->assign_by_ref('isbn', $isbn);
		$templateMgr->assign('pageCrumbTitleTranslated', $issueCrumbTitle);
		$templateMgr->assign('issueHeadingTitle', $issueHeadingTitle);
	}

	/**
	 * Display the issue archive listings
	 */
	function archive($rangeDisplay = null) {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$rangeInfo = Handler::getRangeInfo('issues');
		$templateMgr =& TemplateManager::getManager();
		
		if (is_int($rangeDisplay)) { // if a range has been specified (i.e. if this method is being used for atomistic...
			$rangeInfo->count = $range; 
			$templateMgr->assign('pageTitleCurrent', 'Current');
		}

		$publishedIssuesIterator = $issueDao->getPublishedIssues($journal->getId(), $rangeInfo);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$coverPagePath = Request::getBaseUrl() . '/';
		$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
		
		$templateMgr->assign('coverPagePath', $coverPagePath);
		$templateMgr->assign('locale', Locale::getLocale());
		$templateMgr->assign_by_ref('issues', $publishedIssuesIterator);
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('issue/archive.tpl');
	}
		
	function setupTemplate() {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_OJS_EDITOR));
	}
}

?>
