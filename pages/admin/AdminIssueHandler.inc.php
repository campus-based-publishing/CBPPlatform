<?php

/**
 * @file pages/admin/AdminIssueHandler.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminIssueHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for issue/book management in site administration.
 */

import('classes.handler.Handler');

class AdminIssueHandler extends Handler {
	
	/**
	 * Constructor
	 **/
	function AdminIssueHandler() {
		parent::Handler();
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN)));
		$this->addCheck(new HandlerValidatorCustom($this, true, null, null, create_function(null, 'return Request::getRequestedJournalPath() == \'index\';')));
	}
	
	/**
	 * Display a list of pending issues
	 */
	function issue() {
		$this->validate();
		$this->setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$user =& Request::getUser();
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		if (Request::getUserVar('filter')) {
			$filter = (int)Request::getUserVar('filter');
		}
		$pendingIssuesList = $CBPPlatformDao->getPendingIssues();
		foreach($pendingIssuesList as $pendingIssue) {
			$journal = $journalDao->getJournal($pendingIssue['journal_id']);
			$journalsWithPendingIssues[$journal->getJournalTitle()] = $journal->getId();
		}
		ksort($journalsWithPendingIssues);
		$pendingIssuesList = $CBPPlatformDao->getPendingIssues($filter);
		function in_array_r($needle, $haystack) {
			foreach ($haystack as $item) {
				if ($item === $needle || (is_array($item) && in_array_r($needle, $item))) {
			    	return true;
			    }
			}
			return false;
		}
		//%CBP% if the admin has asked to publish a book, validate its prescence in the pending array
		if (Request::getUserVar('publish')) {
			$issueId = Request::getUserVar('publish');
			if (in_array_r($issueId, $pendingIssuesList)) {
				$issue = $issueDao->getIssueById($issueId);
				$issue->setPublished(1);
				$issue->setDatePublished(Core::getCurrentDate());
				$issue->setCurrent(1);
				$issueDao->updateCurrentIssue($issue->getJournalId(),$issue);
				$CBPPlatformDao->setIssuePending($issueId, 0);
				
				//TODO enable CBP notifications
				// %CBP% send notifications to users that book has been published
				/*import('lib.pkp.classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$notificationUsers = array();
				$allUsers = $roleDao->getUsersByJournalId($issue->getJournalId());
				while (!$allUsers->eof()) {
					$user =& $allUsers->next();
					$notificationUsers[] = array('id' => $user->getId());
					unset($user);
				}
				$url = Request::url(null, 'issue', 'current');
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.issuePublished',
						null, $url, 1, NOTIFICATION_TYPE_PUBLISHED_ISSUE
					);
				}*/
				/*$notificationManager->sendToMailingList(
					$notificationManager->createNotification(
						0, 'notification.type.issuePublished',
						null, $url, 1, NOTIFICATION_TYPE_PUBLISHED_ISSUE
					)
				);*/
				Request::redirect(Request::getRequestedJournalPath(), Request::getRequestedPage(), Request::getRequestedOp());
			}
		}
		if (Request::getUserVar('issueId')) {
			$issueId = Request::getUserVar('issueId');
			if (in_array_r($issueId, $pendingIssuesList)) {	
				$comment = Request::getUserVar('comment');
				$CBPPlatformDao->setReviewerIssueComment($issueId, $user->getUserId(), $comment);
				Request::redirect(Request::getRequestedJournalPath(), Request::getRequestedPage(), Request::getRequestedOp());
			}
		}
		foreach($pendingIssuesList as $pendingIssue) {
			$issues[] = $issueDao->getIssueById($pendingIssue['issue_id']);
			
			$issueComments[$pendingIssue['issue_id']] = $CBPPlatformDao->getReviewerIssueComments($pendingIssue['issue_id']);
			$issueObject = $CBPPlatformDao->getFedoraIssueObjectInformation($pendingIssue['issue_id']);
			$issueObjects[$pendingIssue['issue_id']]['pid'] = $issueObject['fedora_namespace'] . ":" . $issueObject['fedora_pid'];
			$issueObjects[$pendingIssue['issue_id']]['dsid'] = $issueObject['fedora_dsid'];
			
			$publishedArticles[$pendingIssue['issue_id']] = $publishedArticleDao->getPublishedArticles($pendingIssue['issue_id']);
		}
		$pendingIssues = $issues;
		$templateMgr->assign('journalsWithPendingIssues', $journalsWithPendingIssues);
		$templateMgr->assign('pendingIssues', $pendingIssues);
		$templateMgr->assign('publishedArticles', $publishedArticles);
		$templateMgr->assign('issueObjects', $issueObjects);
		$templateMgr->assign('issueComments', $issueComments);
		$templateMgr->assign('user', $userDao);
		$templateMgr->assign('filter', $filter);
		$templateMgr->display('admin/issue.tpl');	
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_OJS_ADMIN, LOCALE_COMPONENT_OJS_MANAGER));
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'admin'), 'admin.siteAdmin'))
				: array(array(Request::url(null, 'user'), 'navigation.user'))
		);
	}
	
}