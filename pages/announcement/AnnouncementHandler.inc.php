<?php

/**
 * @file pages/announcement/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_announcement
 *
 * @brief Handle requests for public announcement functions.
 */


import('lib.pkp.pages.announcement.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 **/
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
		$this->addCheck(new HandlerValidatorJournal($this));
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementsEnabled()
	 */
	function _getAnnouncementsEnabled() {
		$journal =& Request::getJournal();
		return $journal->getSetting('enableAnnouncements');
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncements()
	 */
	function &_getAnnouncements($rangeInfo = null) {
		$journal =& Request::getJournal();

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $rangeInfo);

		return $announcements;
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementsIntroduction()
	 */
	function _getAnnouncementsIntroduction() {
		$journal =& Request::getJournal();
		return $journal->getLocalizedSetting('announcementsIntroduction');
	}

	/**
	 * @see PKPAnnouncementHandler::_announcementIsValid()
	 */
	function _announcementIsValid($announcementId) {
		$journal =& Request::getJournal();
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		return ($announcementId != null && $announcementDao->getAnnouncementAssocId($announcementId) == $journal->getId());
	}
}

?>
