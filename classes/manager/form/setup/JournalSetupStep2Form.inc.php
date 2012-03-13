<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep2Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 2 of journal setup.
 */

// $Id$


import('classes.manager.form.setup.JournalSetupForm');

class JournalSetupStep2Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep2Form() {
		parent::JournalSetupForm(
			2,
			array(
				'focusScopeDesc' => 'string',
				'numWeeksPerReview' => 'int',
				'remindForInvite' => 'bool',
				'remindForSubmit' => 'bool',
				'numDaysBeforeInviteReminder' => 'int',
				'numDaysBeforeSubmitReminder' => 'int',
				'rateReviewerOnQuality' => 'bool',
				'restrictReviewerFileAccess' => 'bool',
				'reviewerAccessKeysEnabled' => 'bool',
				'showEnsuringLink' => 'bool',
				'reviewPolicy' => 'string',
				'mailSubmissionsToReviewers' => 'bool',
				'reviewGuidelines' => 'string',
				'authorSelectsEditor' => 'bool',
				'privacyStatement' => 'string',
				'customAboutItems' => 'object',
				'enableLockss' => 'bool',
				'lockssLicense' => 'string',
				'reviewerDatabaseLinks' => 'object',
				'notifyAllAuthorsOnDecision' => 'bool'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('focusScopeDesc', 'reviewPolicy', 'reviewGuidelines', 'privacyStatement', 'customAboutItems', 'lockssLicense');
	}

	/**
	 * Display the form.
	 */
	function display($request, $dispatcher) {
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}
		// %CBP% Check which type of workflow model is selected
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$templateMgr->assign_by_ref('workflowModel', $CBPPlatformDao->getWorkshop($journal->getId()));
		
		parent::display($request, $dispatcher);
	}
}

?>
