<?php

/**
 * @file IndexHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndexHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

// $Id$


import('classes.handler.Handler');

class IndexHandler extends Handler {
	/**
	 * Constructor
	 **/
	function IndexHandler() {
		parent::Handler();
	}

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 * @param $args array
	 * @param $request Request
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		$router =& $request->getRouter();
		$templateMgr =& TemplateManager::getManager();
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalPath = $router->getRequestedContextPath($request);
		$templateMgr->assign('helpTopicId', 'user.home');
		$journal =& $router->getContext($request);
		if ($journal) {
			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $journal->getLocalizedPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $journal->getLocalizedPageHeaderLogo(true));
			$templateMgr->assign('displayPageHeaderTitleAltText', $journal->getLocalizedSetting('homeHeaderTitleImageAltText'));
			$templateMgr->assign('displayPageHeaderLogoAltText', $journal->getLocalizedSetting('homeHeaderLogoImageAltText'));
			$templateMgr->assign('additionalHomeContent', $journal->getLocalizedSetting('additionalHomeContent'));
			$templateMgr->assign('homepageImage', $journal->getLocalizedSetting('homepageImage'));
			$templateMgr->assign('homepageImageAltText', $journal->getLocalizedSetting('homepageImageAltText'));
			$templateMgr->assign('journalDescription', $journal->getLocalizedSetting('description'));
			
			$displayCurrentIssue = $journal->getSetting('displayCurrentIssue');
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getCurrentIssue($journal->getId(), true);
			if ($displayCurrentIssue && isset($issue)) {
				import('pages.issue.IssueHandler');
				// The current issue TOC/cover page should be displayed below the custom home page.
				IssueHandler::setupIssueTemplate($issue);
			}

			// Display creative commons logo/licence if enabled
			$templateMgr->assign('displayCreativeCommons', $journal->getSetting('includeCreativeCommons'));

			$enableAnnouncements = $journal->getSetting('enableAnnouncements');
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $journal->getSetting('enableAnnouncementsHomepage');
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $journal->getSetting('numAnnouncementsHomepage');
					$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
					$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			}
			$templateMgr->assign_by_ref('journalHomepage', $home = true);
			$templateMgr->display('index/journal.tpl');
		} else {
			$site =& Request::getSite();

			if ($site->getRedirect() && ($journal = $journalDao->getJournal($site->getRedirect())) != null) {
				$request->redirect($journal->getPath());
			}
			
			//%CBP% Get top 10 'articles' to display on homepage
			$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
			$popularArticles = $CBPPlatformDao->getArticlesByCriteria(3);
			$templateMgr->assign('popularArticles', $popularArticles);
			
			//%CBP% Get top 3 latest published articles to display on homepage
			$newArticles = $CBPPlatformDao->getArticlesByCriteria(3, "date_published");
			$newIssues = $CBPPlatformDao->getLatestIssues(3);
			if (!$issueDao) $issueDao =& DAORegistry::getDAO('IssueDAO');
			
			for($i=0; $i<count($newIssues); $i++) {
				//%CBP% show thumbnail version rather than full-size front cover
				$issue = $issueDao->getIssueById($newIssues[$i]['issue_id']);
				$filename = $issue->getThumbFileName($issue->getFileName('en_US'));

				$newIssues[$i]['cover'] =  Config::getVar('general', 'base_url') . "/" . Config::getVar('files', 'public_files_dir') . "/journals/" . $newIssues[$i]['journal_id'] . "/" . $filename;//cover_issue_" . $newIssues[$i]['issue_id'] . "_en_US.jpg";
				$journal = $journalDao->getJournal($newIssues[$i]['journal_id']);
				$newIssues[$i]['journal'] = $journal->getTitle('en_US');
			}
			$templateMgr->assign('newIssues', $newIssues);
			$templateMgr->assign('intro', $site->getLocalizedIntro());
			$templateMgr->assign('journalFilesPath', $request->getBaseUrl() . '/' . Config::getVar('files', 'public_files_dir') . '/journals/');
			$journals =& $journalDao->getEnabledJournals();
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->assign_by_ref('homepage', $home = true);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
			$templateMgr->display('index/site.tpl');
		}
	}
}

?>
