<?php

/**
 * @defgroup pages_subscriptionManager
 */
 
/**
 * @file pages/subscriptionManager/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_subscriptionManager
 * @brief Handle requests for journal management functions. 
 *
 */

// $Id$


switch ($op) {
	case 'index':
	case 'subscriptionsSummary':
	case 'subscriptions':
	case 'deleteSubscription':
	case 'renewSubscription':
	case 'editSubscription':
	case 'createSubscription':
	case 'selectSubscriber':
	case 'updateSubscription':
	case 'subscriptionTypes':
	case 'moveSubscriptionType':
	case 'deleteSubscriptionType':
	case 'editSubscriptionType':
	case 'createSubscriptionType':
	case 'updateSubscriptionType':
	case 'subscriptionPolicies':
	case 'saveSubscriptionPolicies':
	case 'createUser':
	case 'updateUser':
	case 'payments':
	case 'savePaymentSettings':
	case 'viewPayments':
	case 'viewPayment':
	case 'payMethodSettings':
	case 'savePayMethodSettings':
	case 'suggestUsername':
		define('HANDLER_CLASS', 'SubscriptionManagerHandler');
		import('pages.subscriptionManager.SubscriptionManagerHandler');
		break;
}

?>
