<?php

/**
 * @file classes/security/RoleDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleDAO
 * @ingroup security
 * @see Role
 *
 * @brief Operations for retrieving and modifying Role objects.
 */

// $Id$


import('classes.security.Role');

class RoleDAO extends DAO {
	/**
	 * Constructor.
	 */
	function RoleDAO() {
		parent::DAO();
		$this->userDao =& DAORegistry::getDAO('UserDAO');
	}

	/**
	 * Retrieve a role.
	 * @param $journalId int
	 * @param $userId int
	 * @param $roleId int
	 * @return Role
	 */
	function &getRole($journalId, $userId, $roleId) {
		$result =& $this->retrieve(
			'SELECT * FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ?',
			array(
				(int) $journalId,
				(int) $userId,
				(int) $roleId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnRoleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a Role object from a row.
	 * @param $row array
	 * @return Role
	 */
	function &_returnRoleFromRow(&$row) {
		$role = new Role();
		$role->setJournalId($row['journal_id']);
		$role->setUserId($row['user_id']);
		$role->setRoleId($row['role_id']);

		HookRegistry::call('RoleDAO::_returnRoleFromRow', array(&$role, &$row));

		return $role;
	}

	/**
	 * Insert a new role.
	 * @param $role Role
	 */
	function insertRole(&$role, $approved = 1) {
		//%LP% modified for approval field, required for EIC user approval
		return $this->update(
			'INSERT INTO roles
				(journal_id, user_id, role_id, approved)
				VALUES
				(?, ?, ?, ?)',
			array(
				(int) $role->getJournalId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId(),
				(int) $approved
			)
		);
	}

	/**
	 * Delete a role.
	 * @param $role Role
	 */
	function deleteRole(&$role) {
		return $this->update(
			'DELETE FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ?',
			array(
				(int) $role->getJournalId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId()
			)
		);
	}

	/**
	 * Retrieve a list of all roles for a specified user.
	 * @param $userId int
	 * @param $journalId int optional, include roles only in this journal
	 * @return array matching Roles
	 */
	function &getRolesByUserId($userId, $journalId = null) {
		$roles = array();
		$params = array((int) $userId);
		if ($journalId !== null) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT * FROM roles WHERE user_id = ?
			' . (isset($journalId) ? ' AND journal_id = ?' : '') . '
			AND approved = 1 ORDER BY journal_id',
			$params
		);

		while (!$result->EOF) {
			$roles[] =& $this->_returnRoleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $roles;
	}

	/**
	 * Retrieve a list of users in a specified role.
	 * @param $roleId int optional (can leave as null to get all users in journal)
	 * @param $journalId int optional, include users only in this journal
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains' vs. 'startsWith')
	 * @param $dbResultRange object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByRoleId($roleId = null, $journalId = null, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$users = array();

		$paramArray = array(ASSOC_TYPE_USER, 'interest');
		if (isset($roleId)) $paramArray[] = (int) $roleId;
		if (isset($journalId)) $paramArray[] = (int) $journalId;

		// For security / resource usage reasons, a role or journal ID
		// must be specified. Don't allow calls supplying neither.
		if ($journalId === null && $roleId === null) return null;

		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}

		$searchSql .= ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(
			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id),
				roles AS r WHERE u.user_id = r.user_id ' . (isset($roleId)?'AND r.role_id = ?':'') . (isset($journalId) ? ' AND r.journal_id = ?' : '') . ' ' . $searchSql,
			$paramArray,
			$dbResultRange
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Retrieve a list of all users with some role in the specified journal.
	 * @param $journalId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains' vs. 'startsWith')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByJournalId($journalId, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$users = array();

		$paramArray = array(ASSOC_TYPE_USER, 'interest', (int) $journalId);
		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}

		$searchSql .= ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(

			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id),
				roles AS r WHERE u.user_id = r.user_id AND r.journal_id = ? ' . $searchSql,
			$paramArray,
			$dbResultRange
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Retrieve the number of users associated with the specified journal.
	 * @param $journalId int
	 * @param $roleId int ROLE_ID_... (optional) role to count
	 * @return int
	 */
	function getJournalUsersCount($journalId, $roleId = null) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$params = array((int) $journalId);
		if ($roleId !== null) $params[] = (int) $roleId;

		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT(user_id)) FROM roles WHERE journal_id = ?' . ($roleId === null?'':' AND role_id = ?'),
			$params
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve the number of users with a given role associated with the specified journal.
	 * @param $journalId int
	 * @param $roleId int
	 * @return int
	 */
	function getJournalUsersRoleCount($journalId, $roleId) {
		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT(user_id)) FROM roles WHERE journal_id = ? AND role_id = ?',
			array (
				(int) $journalId,
				(int) $roleId
			)
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Select all roles for a specified journal.
	 * @param $journalId int optional
	 * @param $roleId int optional
	 */
	function &getRolesByJournalId($journalId = null, $roleId = null) {
		$params = array();
		$conditions = array();
		if (isset($journalId)) {
			$params[] = (int) $journalId;
			$conditions[] = 'journal_id = ?';
		}
		if (isset($roleId)) {
			$params[] = (int) $roleId;
			$conditions[] = 'role_id = ?';
		}

		$result =& $this->retrieve(
			'SELECT * FROM roles' . (empty($conditions) ? '' : ' WHERE ' . join(' AND ', $conditions)),
			$params
		);

		$returner = new DAOResultFactory($result, $this, '_returnRoleFromRow');
		return $returner;
	}

	/**
	 * Delete all roles for a specified journal.
	 * @param $journalId int
	 */
	function deleteRoleByJournalId($journalId) {
		return $this->update(
			'DELETE FROM roles WHERE journal_id = ?', (int) $journalId
		);
	}

	/**
	 * Delete all roles for a specified journal.
	 * @param $userId int
	 * @param $journalId int optional, include roles only in this journal
	 * @param $roleId int optional, include only this role
	 */
	function deleteRoleByUserId($userId, $journalId  = null, $roleId = null) {
		return $this->update(
			'DELETE FROM roles WHERE user_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : '') . (isset($roleId) ? ' AND role_id = ?' : ''),
			isset($journalId) && isset($roleId) ? array((int) $userId, (int) $journalId, (int) $roleId)
			: (isset($journalId) ? array((int) $userId, (int) $journalId)
			: (isset($roleId) ? array((int) $userId, (int) $roleId) : (int) $userId))
		);
	}

	/**
	 * Check if a role exists.
	 * @param $journalId int
	 * @param $userId int
	 * @param $roleId int
	 * @return boolean
	 */
	function roleExists($journalId, $userId, $roleId) {
		//%LP% modified to only select user roles that have been approved by an Imprint Manager
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM roles WHERE journal_id = ? AND user_id = ? AND role_id = ? AND approved = 1', array((int) $journalId, (int) $userId, (int) $roleId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the i18n key name associated with the specified role.
	 * @param $roleId int
	 * @param $plural boolean get the plural form of the name
	 * @return string
	 */
	function getRoleName($roleId, $plural = false) {
		switch ($roleId) {
			case ROLE_ID_SITE_ADMIN:
				return 'user.role.siteAdmin' . ($plural ? 's' : '');
			case ROLE_ID_JOURNAL_MANAGER:
				return 'user.role.manager' . ($plural ? 's' : '');
			case ROLE_ID_EDITOR:
				return 'user.role.editor' . ($plural ? 's' : '');
			case ROLE_ID_SECTION_EDITOR:
				return 'user.role.sectionEditor' . ($plural ? 's' : '');
			case ROLE_ID_LAYOUT_EDITOR:
				return 'user.role.layoutEditor' . ($plural ? 's' : '');
			case ROLE_ID_REVIEWER:
				return 'user.role.reviewer' . ($plural ? 's' : '');
			case ROLE_ID_COPYEDITOR:
				return 'user.role.copyeditor' . ($plural ? 's' : '');
			case ROLE_ID_PROOFREADER:
				return 'user.role.proofreader' . ($plural ? 's' : '');
			case ROLE_ID_AUTHOR:
				return 'user.role.author' . ($plural ? 's' : '');
			case ROLE_ID_READER:
				return 'user.role.reader' . ($plural ? 's' : '');
			case ROLE_ID_SUBSCRIPTION_MANAGER:
				return 'user.role.subscriptionManager' . ($plural ? 's' : '');
			default:
				return '';
		}
	}

	/**
	 * Get the URL path associated with the specified role's operations.
	 * @param $roleId int
	 * @return string
	 */
	function getRolePath($roleId) {
		switch ($roleId) {
			case ROLE_ID_SITE_ADMIN:
				return 'admin';
			case ROLE_ID_JOURNAL_MANAGER:
				return 'manager';
			case ROLE_ID_EDITOR:
				return 'editor';
			case ROLE_ID_SECTION_EDITOR:
				return 'sectionEditor';
			case ROLE_ID_LAYOUT_EDITOR:
				return 'layoutEditor';
			case ROLE_ID_REVIEWER:
				return 'reviewer';
			case ROLE_ID_COPYEDITOR:
				return 'copyeditor';
			case ROLE_ID_PROOFREADER:
				return 'proofreader';
			case ROLE_ID_AUTHOR:
				return 'author';
			case ROLE_ID_READER:
				return 'reader';
			case ROLE_ID_SUBSCRIPTION_MANAGER:
				return 'subscriptionManager';
			default:
				return '';
		}
	}

	/**
	 * Get a role's ID based on its path.
	 * @param $rolePath string
	 * @return int
	 */
	function getRoleIdFromPath($rolePath) {
		switch ($rolePath) {
			case 'admin':
				return ROLE_ID_SITE_ADMIN;
			case 'manager':
				return ROLE_ID_JOURNAL_MANAGER;
			case 'editor':
				return ROLE_ID_EDITOR;
			case 'sectionEditor':
				return ROLE_ID_SECTION_EDITOR;
			case 'layoutEditor':
				return ROLE_ID_LAYOUT_EDITOR;
			case 'reviewer':
				return ROLE_ID_REVIEWER;
			case 'copyeditor':
				return ROLE_ID_COPYEDITOR;
			case 'proofreader':
				return ROLE_ID_PROOFREADER;
			case 'author':
				return ROLE_ID_AUTHOR;
			case 'reader':
				return ROLE_ID_READER;
			case 'subscriptionManager':
				return ROLE_ID_SUBSCRIPTION_MANAGER;
			default:
				return null;
		}
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'username': return 'u.username';
			case 'name': return 'u.last_name';
			case 'email': return 'u.email';
			case 'id': return 'u.user_id';
			default: return null;
		}
	}
}

?>
