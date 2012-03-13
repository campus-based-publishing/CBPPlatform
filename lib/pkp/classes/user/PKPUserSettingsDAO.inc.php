<?php

/**
 * @file classes/user/PKPUserSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPUserSettingsDAO
 * @ingroup user
 * @see PKPUser
 *
 * @brief Operations for retrieving and modifying user settings.
 */

// $Id$


class PKPUserSettingsDAO extends DAO {
	/**
	 * Retrieve a user setting value.
	 * @param $userId int
	 * @param $name
	 * @param $assocType int
	 * @param $assocId int
	 * @return mixed
	 */
	function &getSetting($userId, $name, $assocType = null, $assocId = null) {
		$result =& $this->retrieve(
			'SELECT	setting_value,
				setting_type
			FROM	user_settings
			WHERE	user_id = ? AND
				setting_name = ? AND
				assoc_type = ? AND
				assoc_id = ?',
			array(
				(int) $userId,
				$name,
				(int) $assocType,
				(int) $assocId
			)
		);

		if ($result->RecordCount() != 0) {
			$row =& $result->getRowAssoc(false);
			$returner = $this->convertFromDB($row['setting_value'], $row['setting_type']);
		} else {
			$returner = null;
		}

		return $returner;
	}

	/**
	 * Retrieve all users by setting name and value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string
	 * @param $assocType int
	 * @param $assocId int
	 * @return DAOResultFactory matching Users
	 */
	function &getUsersBySetting($name, $value, $type = null, $assocType = null, $assocId = null) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$value = $this->convertToDB($value, $type);
		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u,
				user_settings s
			WHERE	u.user_id = s.user_id AND
				s.setting_name = ? AND
				s.setting_value = ? AND
				s.assoc_type = ? AND
				s.assoc_id = ?',
			array($name, $value, (int) $assocType, (int) $assocId)
		);

		$returner = new DAOResultFactory($result, $userDao, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Retrieve all settings for a user by association info.
	 * @param $userId int
	 * @param $assocType int
	 * @param $assocId int
	 * @return array
	 */
	function &getSettingsByAssoc($userId, $assocType = null, $assocId = null) {
		$userSettings = array();

		$result =& $this->retrieve(
			'SELECT	setting_name,
				setting_value,
				setting_type
			FROM	user_settings
			WHERE	user_id = ? AND
				assoc_type = ?
				AND assoc_id = ?',
			array((int) $userId, (int) $assocType, (int) $assocId)
		);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$userSettings[$row['setting_name']] = $value;
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $userSettings;
	}

	/**
	 * Add/update a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $assocType int
	 * @param $assocId int
	 */
	function updateSetting($userId, $name, $value, $type = null, $assocType = null, $assocId = null) {
		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	user_settings
			WHERE	user_id = ? AND
				setting_name = ?
				AND assoc_type = ?
				AND assoc_id = ?',
			array((int) $userId, $name, (int) $assocType, (int) $assocId)
		);

		$value = $this->convertToDB($value, $type);
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO user_settings
					(user_id, setting_name, assoc_type, assoc_id, setting_value, setting_type)
				VALUES
					(?, ?, ?, ?, ?, ?)',
				array(
					(int) $userId,
					$name,
					(int) $assocType,
					(int) $assocId,
					$value,
					$type
				)
			);
		} else {
			$returner = $this->update(
				'UPDATE user_settings
				SET	setting_value = ?,
					setting_type = ?
				WHERE	user_id = ? AND
					setting_name = ? AND
					assoc_type = ?
					AND assoc_id = ?',
				array(
					$value,
					$type,
					(int) $userId,
					$name,
					(int) $assocType,
					(int) $assocId
				)
			);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $assocType int
	 * @param $assocId int
	 */
	function deleteSetting($userId, $name, $assocType = null, $assocId = null) {
		return $this->update(
			'DELETE FROM user_settings WHERE user_id = ? AND setting_name = ? AND assoc_type = ? AND assoc_id = ?',
			array((int) $userId, $name, (int) $assocType, (int) $assocId)
		);
	}

	/**
	 * Delete all settings for a user.
	 * @param $userId int
	 */
	function deleteSettings($userId) {
		return $this->update(
			'DELETE FROM user_settings WHERE user_id = ?', $userId
		);
	}
}

?>
