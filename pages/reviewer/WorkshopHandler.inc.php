<?php

/**
 * @file WorkshopHandler.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions. 
 */

// $Id$

import('classes.handler.Handler');

class WorkshopHandler extends Handler {
	/**
	 * Constructor
	 **/
	function WorkshopHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_REVIEWER)));		
	}

	/**
	 * Display reviewer index page.
	 */
	function workshop($args) { 
		$this->validate();
		$this->setupTemplate();
		
		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();
		
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		
		$workshop = $CBPPlatformDao->getWorkshop($journalId);
		if ($workshop == "structured") {
			exit();
		}
		
		$user =& Request::getUser();
		$userId = $user->getUserId();
		$templateMgr =& TemplateManager::getManager();
		
		$workshopArticles = $CBPPlatformDao->getWorkshopArticles($journalId, $userId);
		
		$templateMgr->assign('workshop', $workshop);
		$templateMgr->assign('workshopArticles', $workshopArticles);
		
		$templateMgr->display('reviewer/workshop.tpl');
	}

}

?>
