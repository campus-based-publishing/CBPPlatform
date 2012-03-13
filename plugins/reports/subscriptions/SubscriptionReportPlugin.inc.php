<?php

/**
 * @file plugins/reports/subscriptions/SubscriptionReportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionReportPlugin
 * @ingroup plugins_reports_subscription
 *
 * @brief Subscription report plugin
 */

import('classes.plugins.ReportPlugin');

class SubscriptionReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'SubscriptionReportPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String display name of plugin
	 */
	function getDisplayName() {
		return Locale::translate('plugins.reports.subscriptions.displayName');
	}

	/**
	 * Get the description text for this plugin.
	 * @return String description text for this plugin
	 */
	function getDescription() {
		return Locale::translate('plugins.reports.subscriptions.description');
	}

	/**
	 * Generate the subscription report and write CSV contents to file
	 * @param $args array Request arguments 
	 */
	function display(&$args) {
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$userDao =& DAORegistry::getDAO('UserDAO');
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=subscriptions-' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'wt');

		// Columns for individual subscriptions
		$columns = array(Locale::translate('subscriptionManager.individualSubscriptions'));
		String::fputcsv($fp, array_values($columns));

		$columnsCommon = array(
			'subscription_id' => Locale::translate('common.id'),
			'status' => Locale::translate('subscriptions.status'),
			'type' => Locale::translate('common.type'),
			'format' => Locale::translate('subscriptionTypes.format'),
			'date_start' => Locale::translate('manager.subscriptions.dateStart'),
			'date_end' => Locale::translate('manager.subscriptions.dateEnd'),
			'membership' => Locale::translate('manager.subscriptions.membership'),
			'reference_number' => Locale::translate('manager.subscriptions.referenceNumber'),
			'notes' => Locale::translate('common.notes')
		);

		$columnsIndividual = array(
			'name' => Locale::translate('user.name'),
			'mailing_address' => Locale::translate('common.mailingAddress'),
			'country' => Locale::translate('common.country'),
			'email' => Locale::translate('user.email'),
			'phone' => Locale::translate('user.phone'),
			'fax' => Locale::translate('user.fax')
		);

		$columns = array_merge($columnsCommon, $columnsIndividual);

		// Write out individual subscription column headings to file
		String::fputcsv($fp, array_values($columns));

		// Iterate over individual subscriptions and write out each to file
		$individualSubscriptions =& $individualSubscriptionDao->getSubscriptionsByJournalId($journalId);
		while ($subscription =& $individualSubscriptions->next()) {
			$user =& $userDao->getUser($subscription->getUserId());
			$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

			foreach ($columns as $index => $junk) {
				switch ($index) {
					case 'subscription_id':
						$columns[$index] = $subscription->getId();
						break;
					case 'status':
						$columns[$index] = $subscription->getStatusString();
						break;
					case 'type':
						$columns[$index] = $subscription->getSubscriptionTypeSummaryString();
						break;
					case 'format':
						$columns[$index] = Locale::translate($subscriptionType->getFormatString());
						break;
					case 'date_start':
						$columns[$index] = $subscription->getDateStart();
						break;
					case 'date_end':
						$columns[$index] = $subscription->getDateEnd();
						break;
					case 'membership':
						$columns[$index] = $subscription->getMembership();
						break;
					case 'reference_number':
						$columns[$index] = $subscription->getReferenceNumber();
						break;
					case 'notes':
						$columns[$index] = $this->_html2text($subscription->getNotes());
						break;
					case 'name':
						$columns[$index] = $user->getFullName();
						break;
					case 'mailing_address':
						$columns[$index] = $this->_html2text($user->getMailingAddress());
						break;
					case 'country':
						$columns[$index] = $countryDao->getCountry($user->getCountry());
						break;
					case 'email':
						$columns[$index] = $user->getEmail();
						break;
					case 'phone':
						$columns[$index] = $user->getPhone();
						break;
					case 'fax':
						$columns[$index] = $user->getFax();
						break;
					default:
						$columns[$index] = '';
				}
			}

			String::fputcsv($fp, $columns);
		}

		// Columns for institutional subscriptions
		$columns = array('');
		String::fputcsv($fp, array_values($columns));

		$columns = array(Locale::translate('subscriptionManager.institutionalSubscriptions'));
		String::fputcsv($fp, array_values($columns));

		$columnsInstitution = array(
			'institution_name' => Locale::translate('manager.subscriptions.institutionName'),
			'institution_mailing_address' => Locale::translate('plugins.reports.subscriptions.institutionMailingAddress'),
			'domain' => Locale::translate('manager.subscriptions.domain'),
			'ip_ranges' => Locale::translate('plugins.reports.subscriptions.ipRanges'),
			'contact' => Locale::translate('manager.subscriptions.contact'),
			'mailing_address' => Locale::translate('common.mailingAddress'),
			'country' => Locale::translate('common.country'),
			'email' => Locale::translate('user.email'),
			'phone' => Locale::translate('user.phone'),
			'fax' => Locale::translate('user.fax')
		);

		$columns = array_merge($columnsCommon, $columnsInstitution);

		// Write out institutional subscription column headings to file
		String::fputcsv($fp, array_values($columns));

		// Iterate over institutional subscriptions and write out each to file
		$institutionalSubscriptions =& $institutionalSubscriptionDao->getSubscriptionsByJournalId($journalId);
		while ($subscription =& $institutionalSubscriptions->next()) {
			$user =& $userDao->getUser($subscription->getUserId());
			$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

			foreach ($columns as $index => $junk) {
				switch ($index) {
					case 'subscription_id':
						$columns[$index] = $subscription->getId();
						break;
					case 'status':
						$columns[$index] = $subscription->getStatusString();
						break;
					case 'type':
						$columns[$index] = $subscription->getSubscriptionTypeSummaryString();
						break;
					case 'format':
						$columns[$index] = Locale::translate($subscriptionType->getFormatString());
						break;
					case 'date_start':
						$columns[$index] = $subscription->getDateStart();
						break;
					case 'date_end':
						$columns[$index] = $subscription->getDateEnd();
						break;
					case 'membership':
						$columns[$index] = $subscription->getMembership();
						break;
					case 'reference_number':
						$columns[$index] = $subscription->getReferenceNumber();
						break;
					case 'notes':
						$columns[$index] = $this->_html2text($subscription->getNotes());
						break;
					case 'institution_name':
						$columns[$index] = $subscription->getInstitutionName();
						break;
					case 'institution_mailing_address':
						$columns[$index] = $subscription->getInstitutionMailingAddress();
						break;
					case 'domain':
						$columns[$index] = $subscription->getDomain();
						break;
					case 'ip_ranges':
						$columns[$index] = $this->_formatIPRanges($subscription->getIPRanges());
						break;
					case 'contact':
						$columns[$index] = $user->getFullName();
						break;
					case 'mailing_address':
						$columns[$index] = $this->_html2text($user->getMailingAddress());
						break;
					case 'country':
						$columns[$index] = $countryDao->getCountry($user->getCountry());
						break;
					case 'email':
						$columns[$index] = $user->getEmail();
						break;
					case 'phone':
						$columns[$index] = $user->getPhone();
						break;
					case 'fax':
						$columns[$index] = $user->getFax();
						break;
					default:
						$columns[$index] = '';
				}
			}

			String::fputcsv($fp, $columns);
		}

		fclose($fp);
	}

	/**
	 * Replace HTML "newline" tags (p, li, br) with line feeds. Strip all other tags.
	 * @param $html String Input HTML string
	 * @return String Text with replaced and stripped HTML tags
	 */
	function _html2text($html) {
		$html = String::regexp_replace('/<[\/]?p>/', chr(10), $html);
		$html = String::regexp_replace('/<li>/', '&bull; ', $html);
		$html = String::regexp_replace('/<\/li>/', chr(10), $html);
		$html = String::regexp_replace('/<br[ ]?[\/]?>/', chr(10), $html);
		$html = String::html2utf(strip_tags($html));
		return $html;
	}

	/**
	 * Pretty format IP ranges, one per line via line feeds.
	 * @param $ipRanges array IP ranges
	 * @return String Text of IP ranges formatted with newlines
	 */
	function _formatIPRanges($ipRanges) {
		$numRanges = count($ipRanges);
		$ipRangesString = '';

		for($i=0; $i<$numRanges; $i++) {
			$ipRangesString .= $ipRanges[$i];
			if ( $i+1 < $numRanges) $ipRangesString .= chr(10);
		}

		return $ipRangesString;
	}
}

?>
