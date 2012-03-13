<?php

/**
 * @file classes/site/SiteDAO.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteDAO
 * @ingroup site
 * @see Site
 *
 * @brief Operations for retrieving and modifying the Site object.
 */

// $Id$


import('lib.pkp.classes.site.Site');

class SiteDAO extends DAO {
	/**
	 * Retrieve site information.
	 * @return Site
	 */
	function &getSite() {
		$site = null;
		$result =& $this->retrieve(
			'SELECT * FROM site'
		);

		if ($result->RecordCount() != 0) {
			$site =& $this->_returnSiteFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $site;
	}

	/**
	 * Internal function to return a Site object from a row.
	 * @param $row array
	 * @param $callHook boolean
	 * @return Site
	 */
	function &_returnSiteFromRow(&$row, $callHook = true) {
		$site = new Site();
		$site->setRedirect($row['redirect']);
		$site->setMinPasswordLength($row['min_password_length']);
		$site->setPrimaryLocale($row['primary_locale']);
		$site->setOriginalStyleFilename($row['original_style_file_name']);
		$site->setInstalledLocales(isset($row['installed_locales']) && !empty($row['installed_locales']) ? explode(':', $row['installed_locales']) : array());
		$site->setSupportedLocales(isset($row['supported_locales']) && !empty($row['supported_locales']) ? explode(':', $row['supported_locales']) : array());

		if ($callHook) HookRegistry::call('SiteDAO::_returnSiteFromRow', array(&$site, &$row));

		return $site;
	}

	/**
	 * Insert site information.
	 * @param $site Site
	 */
	function insertSite(&$site) {
		$returner = $this->update(
			'INSERT INTO site
				(redirect, min_password_length, primary_locale, installed_locales, supported_locales, original_style_file_name)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$site->getRedirect(),
				(int) $site->getMinPasswordLength(),
				$site->getPrimaryLocale(),
				join(':', $site->getInstalledLocales()),
				join(':', $site->getSupportedLocales()),
				$site->getOriginalStyleFilename()
			)
		);
		return $returner;
	}

	/**
	 * Update existing site information.
	 * @param $site Site
	 */
	function updateObject(&$site) {
		return $this->update(
			'UPDATE site
				SET
					redirect = ?,
					min_password_length = ?,
					primary_locale = ?,
					installed_locales = ?,
					supported_locales = ?,
					original_style_file_name = ?',
			array(
				$site->getRedirect(),
				(int) $site->getMinPasswordLength(),
				$site->getPrimaryLocale(),
				join(':', $site->getInstalledLocales()),
				join(':', $site->getSupportedLocales()),
				$site->getOriginalStyleFilename()
			)
		);
	}

	function updateSite(&$site) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->updateObject($site);
	}
}

?>
