<?php

/**
 * @file plugins/paymethod/manual/ManualPaymentPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentPlugin
 * @ingroup plugins_paymethod_manual
 *
 * @brief Manual payment plugin class
 *
 */

//$Id$

import('classes.plugins.PaymethodPlugin');

class ManualPaymentPlugin extends PaymethodPlugin {

	function getName() {
		return 'ManualPayment';
	}

	function getDisplayName() {
		return Locale::translate('plugins.paymethod.manual.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.paymethod.manual.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	function getSettingsFormFieldNames() {
		return array('manualInstructions');
	}

	function isConfigured() {
		$journal =& Request::getJournal();
		if (!$journal) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($journal->getId(), $settingName);
			if (empty($setting)) return false;
		}

		return true;
	}

	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		$journal =& Request::getJournal();
		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$templateMgr =& TemplateManager::getManager();
		$user =& Request::getUser();

		$templateMgr->assign('itemName', $queuedPayment->getName());
		$templateMgr->assign('itemDescription', $queuedPayment->getDescription());
		if ($queuedPayment->getAmount() > 0) {
			$templateMgr->assign('itemAmount', $queuedPayment->getAmount());
			$templateMgr->assign('itemCurrencyCode', $queuedPayment->getCurrencyCode());
		}
		$templateMgr->assign('manualInstructions', $this->getSetting($journal->getId(), 'manualInstructions'));
		$templateMgr->assign('queuedPaymentId', $queuedPaymentId);

		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
	}

	/**
	 * Handle incoming requests/notifications
	 */
	function handle($args) {
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$user =& Request::getUser();
		$op = isset($args[0])?$args[0]:null;
		$queuedPaymentId = isset($args[1])?((int) $args[1]):0;

		import('classes.payment.ojs.OJSPaymentManager');
		$ojsPaymentManager =& OJSPaymentManager::getManager();
		$queuedPayment =& $ojsPaymentManager->getQueuedPayment($queuedPaymentId);
		// if the queued payment doesn't exist, redirect away from payments
		if ( !$queuedPayment ) Request::redirect(null, 'index');

		switch ( $op ) {
			case 'notify':
				import('classes.mail.MailTemplate');
				Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
				$contactName = $journal->getSetting('contactName');
				$contactEmail = $journal->getSetting('contactEmail');
				$mail = new MailTemplate('MANUAL_PAYMENT_NOTIFICATION');
				$mail->setFrom($contactEmail, $contactName);
				$mail->addRecipient($contactEmail, $contactName);
				$mail->assignParams(array(
					'journalName' => $journal->getLocalizedTitle(),
					'userFullName' => $user?$user->getFullName():('(' . Locale::translate('common.none') . ')'),
					'userName' => $user?$user->getUsername():('(' . Locale::translate('common.none') . ')'),
					'itemName' => $queuedPayment->getName(),
					'itemCost' => $queuedPayment->getAmount(),
					'itemCurrencyCode' => $queuedPayment->getCurrencyCode()
				));
				$mail->send();

				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, 'payment', 'plugin', array('notify', $queuedPaymentId)),
					'pageTitle' => 'plugins.paymethod.manual.paymentNotification',
					'message' => 'plugins.paymethod.manual.notificationSent',
					'backLink' => $queuedPayment->getRequestUrl(),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
		}
		parent::handle($args); // Don't know what to do with it
	}

	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}

?>
