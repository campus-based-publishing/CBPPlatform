<?php

/**
 * @file PendingUsersHandler.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PendingUsersHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for pending user account requests.
 */

// $Id$

import('pages.manager.ManagerHandler');

class PendingUsersHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function PendingUsersHandler() {
		parent::ManagerHandler();
		
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_JOURNAL_MANAGER)));
	}
	
	function pendingUsers() {
		$this->validate();
		$this->setupTemplate();
		
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		
		$journalId = $journal->getJournalId();
		
		if (Request::getUserVar('approve') && Request::getUserVar('role')) {
			$userId = Request::getUserVar('approve');
			$roleId = Request::getUserVar('role');
			if ($CBPPlatformDao->setUserRegistration($userId, $roleId, $journalId) == true) {
				$templateMgr->assign('usersApproved', true);
			}
		}
		
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$pendingUsers = $CBPPlatformDao->getPendingUserRegistrations($journalId);
		foreach ($pendingUsers as &$pendingUser) {
			$role = $roleDao->getRole($pendingUser['journal_id'], $pendingUser['user_id'], $pendingUser['role_id']);
			$roleArr = explode(".", $role->getRoleName());
			$pendingUser['role'] = ucfirst($roleArr[2]);
		}
		
		$templateMgr->assign('pendingUsers', $pendingUsers);
		$templateMgr->display('manager/pendingUsers/index.tpl');
	}

}

?>
