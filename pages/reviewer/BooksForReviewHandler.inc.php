<?php

/**
 * @file BooksForReviewHandler.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BooksForReviewHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer book functions. 
 */

// $Id$


import('classes.handler.Handler');

class BooksForReviewHandler extends Handler {
	/**
	 * Constructor
	 **/
	function BooksForReviewHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_REVIEWER)));		
	}

	/**
	 * Index.
	 */
	function booksForReview() { 
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();
		$user =& Request::getUser();
		
		$templateMgr =& TemplateManager::getManager();
		
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		
		$pendingIssuesList = $CBPPlatformDao->getPendingIssues($journalId);
		
		if (Request::getUserVar('issueId')) {
			$issueId = Request::getUserVar('issueId');
			function in_array_r($needle, $haystack) {
			    foreach ($haystack as $item) {
			        if ($item === $needle || (is_array($item) && in_array_r($needle, $item))) {
			            return true;
			        }
			    }
			    return false;
			}
			if (in_array_r($issueId, $pendingIssuesList)) {	
				$comment = Request::getUserVar('comment');
				$CBPPlatformDao->setReviewerIssueComment($issueId, $user->getUserId(), $comment);
				Request::redirect(Request::getRequestedJournalPath(), Request::getRequestedPage(), Request::getRequestedOp());
			}
		}
		
		if (count($pendingIssuesList) > 1) {
			foreach($pendingIssuesList as $pendingIssue) {
				$issues[] = $issueDao->getIssueById($pendingIssue['issue_id']);
				$issueObject = $CBPPlatformDao->getFedoraIssueObjectInformation($pendingIssue['issue_id']);
				$issueComments[$pendingIssue['issue_id']] = $CBPPlatformDao->getReviewerIssueComments($pendingIssue['issue_id']);
				$issueObjects[$pendingIssue['issue_id']]['pid'] = $issueObject['fedora_namespace'] . ":" . $issueObject['fedora_pid'];
				$issueObjects[$pendingIssue['issue_id']]['dsid'] = $issueObject['fedora_dsid'];
				$publishedArticles[$pendingIssue['issue_id']] = $publishedArticleDao->getPublishedArticles($pendingIssue['issue_id']);
			}
			$pendingIssues = $issues;
		} else {
			$pendingIssues = 0;
		}
		
		$templateMgr->assign('user', $userDao);
		$templateMgr->assign('pendingIssues', $pendingIssues);
		$templateMgr->assign('issueComments', $issueComments);
		$templateMgr->assign('publishedArticles', $publishedArticles);
		$templateMgr->assign('issueObjects', $issueObjects);
		
		$templateMgr->display('reviewer/booksForReview.tpl');
	}
	
}

?>
