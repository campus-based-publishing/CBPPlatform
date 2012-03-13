<?php

/**
 * @file classes/payment/PaymentManager.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentManager
 * @ingroup payment
 * @see Payment
 *
 * @brief Provides payment management functions.
 *
 */

class PaymentManager {
	/**
	 * Get the payment manager.
	 */
	function &getManager() {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Queue a payment for receipt.
	 * @param $queuedPayment object
	 * @param $expiryDate date optional
	 * @return int Queued payment ID for new payment
	 */
	function queuePayment(&$queuedPayment, $expiryDate = null) {
		if (!$this->isConfigured()) return false;

		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPaymentId = $queuedPaymentDao->insertQueuedPayment($queuedPayment, $expiryDate);

		// Perform periodic cleanup
		if (time() % 100 == 0) $queuedPaymentDao->deleteExpiredQueuedPayments();

		return $queuedPaymentId;
	}

	/**
	 * Abstract method for fetching the payment plugin
	 * @return null
	 */
	function &getPaymentPlugin() {
		$returnValue = null;
		return $returnValue; // Abstract method; subclasses should impl
	}

	/**
	 * Check if there is a payment plugin and if is configured
	 * @return bool
	 */
	function isConfigured() {
		$paymentPlugin =& $this->getPaymentPlugin();
		if ($paymentPlugin !== null) return $paymentPlugin->isConfigured();
		return false;
	}

	/**
	 * Call the payment plugin's display method
	 */
	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		$paymentPlugin =& $this->getPaymentPlugin();
		if ($paymentPlugin !== null && $paymentPlugin->isConfigured()) return $paymentPlugin->displayPaymentForm($queuedPaymentId, $queuedPayment);
		return false;
	}

	/**
	 * Call the payment plugin's settings display method
	 */
	function displayConfigurationForm() {
		$paymentPlugin =& $this->getPaymentPlugin();
		if ($paymentPlugin !== null && $paymentPlugin->isConfigured()) return $paymentPlugin->displayConfigurationForm();
		return false;
	}

	/**
	 * Fetch a queued payment
	 * @return QueuedPayment
	 */
	function &getQueuedPayment($queuedPaymentId) {
		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPayment =& $queuedPaymentDao->getQueuedPayment($queuedPaymentId);
		return $queuedPayment;
	}

	/**
	 * Abstract method for fulfilling a queued payment
	 */
	function fulfillQueuedPayment(&$queuedPayment) {
		// must be implemented by sub-classes
		assert(false);
	}
}

?>
