<?php

/**
 * @file classes/submission/sectionEditor/SectionEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorSubmissionDAO
 * @ingroup submission
 * @see SectionEditorSubmission
 *
 * @brief Operations for retrieving and modifying SectionEditorSubmission objects.
 */

// $Id$


import('classes.submission.sectionEditor.SectionEditorSubmission');

// Bring in editor decision constants
import('classes.submission.author.AuthorSubmission');
import('classes.submission.common.Action');
import('classes.submission.reviewer.ReviewerSubmission');

class SectionEditorSubmissionDAO extends DAO {
	var $articleDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;
	var $reviewAssignmentDao;
	var $copyeditorSubmissionDao;
	var $articleFileDao;
	var $suppFileDao;
	var $signoffDao;
	var $galleyDao;
	var $articleEmailLogDao;
	var $articleCommentDao;

	/**
	 * Constructor.
	 */
	function SectionEditorSubmissionDAO() {
		parent::DAO();
		$this->articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$this->articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$this->galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$this->articleEmailLogDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
		$this->articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
	}

	/**
	 * Retrieve a section editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getSectionEditorSubmission($articleId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				r2.review_revision
			FROM	articles a
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.submission_id AND a.current_round = r2.round)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.article_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$articleId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a SectionEditorSubmission object from a row.
	 * @param $row array
	 * @return SectionEditorSubmission
	 */
	function &_returnSectionEditorSubmissionFromRow(&$row) {
		$sectionEditorSubmission = new SectionEditorSubmission();

		// Article attributes
		$this->articleDao->_articleFromRow($sectionEditorSubmission, $row);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
		$sectionEditorSubmission->setEditAssignments($editAssignments->toArray());

		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$sectionEditorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}

		// Comments
		$sectionEditorSubmission->setMostRecentEditorDecisionComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_EDITOR_DECISION, $row['article_id']));
		$sectionEditorSubmission->setMostRecentCopyeditComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_COPYEDIT, $row['article_id']));
		$sectionEditorSubmission->setMostRecentLayoutComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_LAYOUT, $row['article_id']));
		$sectionEditorSubmission->setMostRecentProofreadComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PROOFREAD, $row['article_id']));

		// Files
		$sectionEditorSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$sectionEditorSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$sectionEditorSubmission->setReviewFile($this->articleFileDao->getArticleFile($row['review_file_id']));
		$sectionEditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		$sectionEditorSubmission->setEditorFile($this->articleFileDao->getArticleFile($row['editor_file_id']));


		for ($i = 1; $i <= $row['current_round']; $i++) {
			$sectionEditorSubmission->setEditorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['editor_file_id'], $i), $i);
			$sectionEditorSubmission->setAuthorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['revised_file_id'], $i), $i);
		}

		// Review Rounds
		$sectionEditorSubmission->setReviewRevision($row['review_revision']);

		// Review Assignments
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$sectionEditorSubmission->setReviewAssignments($this->reviewAssignmentDao->getBySubmissionId($row['article_id'], $i), $i);
		}

		// Layout Editing
		$sectionEditorSubmission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		// Proof Assignment
		HookRegistry::call('SectionEditorSubmissionDAO::_returnSectionEditorSubmissionFromRow', array(&$sectionEditorSubmission, &$row));

		return $sectionEditorSubmission;
	}

	/**
	 * Update an existing section editor submission.
	 * @param $sectionEditorSubmission SectionEditorSubmission
	 */
	function updateSectionEditorSubmission(&$sectionEditorSubmission) {
		// update edit assignment
		$editAssignments =& $sectionEditorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment($editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment($editAssignment);
			}
		}

		// Update editor decisions
		for ($i = 1; $i <= $sectionEditorSubmission->getCurrentRound(); $i++) {
			$editorDecisions = $sectionEditorSubmission->getDecisions($i);
			if (is_array($editorDecisions)) {
				foreach ($editorDecisions as $editorDecision) {
					if ($editorDecision['editDecisionId'] == null) {
						$this->update(
							sprintf('INSERT INTO edit_decisions
								(article_id, round, editor_id, decision, date_decided)
								VALUES (?, ?, ?, ?, %s)',
								$this->datetimeToDB($editorDecision['dateDecided'])),
							array($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound(), $editorDecision['editorId'], $editorDecision['decision'])
						);
					}
				}
			}
		}
		if ($this->reviewRoundExists($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound())) {
			$this->update(
				'UPDATE review_rounds
					SET
						review_revision = ?
					WHERE submission_id = ? AND round = ?',
				array(
					$sectionEditorSubmission->getReviewRevision(),
					$sectionEditorSubmission->getArticleId(),
					$sectionEditorSubmission->getCurrentRound()
				)
			);
		} elseif ($sectionEditorSubmission->getReviewRevision()!=null) {
			$this->createReviewRound(
				$sectionEditorSubmission->getArticleId(),
				$sectionEditorSubmission->getCurrentRound() === null ? 1 : $sectionEditorSubmission->getCurrentRound(),
				$sectionEditorSubmission->getReviewRevision()
			);
		}

		// Update copyeditor assignment
		$copyeditSignoff = $this->signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getArticleId());
		if ($copyeditSignoff) {
			$copyeditorSubmission =& $this->copyeditorSubmissionDao->getCopyeditorSubmission($sectionEditorSubmission->getArticleId());
		} else {
			$copyeditorSubmission = new CopyeditorSubmission();
		}

		// Only update the fields that an editor can modify.
		$copyeditorSubmission->setArticleId($sectionEditorSubmission->getArticleId());
		$copyeditorSubmission->setDateStatusModified($sectionEditorSubmission->getDateStatusModified());
		$copyeditorSubmission->setLastModified($sectionEditorSubmission->getLastModified());

		// update review assignments
		foreach ($sectionEditorSubmission->getReviewAssignments() as $roundReviewAssignments) {
			foreach ($roundReviewAssignments as $reviewAssignment) {
				if ($reviewAssignment->getId() > 0) {
					$this->reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				} else {
					$this->reviewAssignmentDao->insertReviewAssignment($reviewAssignment);
				}
			}
		}

		// Remove deleted review assignments
		$removedReviewAssignments = $sectionEditorSubmission->getRemovedReviewAssignments();
		for ($i=0, $count=count($removedReviewAssignments); $i < $count; $i++) {
			$this->reviewAssignmentDao->deleteReviewAssignmentById($removedReviewAssignments[$i]);
		}

		// Update article
		if ($sectionEditorSubmission->getArticleId()) {

			$article =& $this->articleDao->getArticle($sectionEditorSubmission->getArticleId());

			// Only update fields that can actually be edited.
			$article->setSectionId($sectionEditorSubmission->getSectionId());
			$article->setCurrentRound($sectionEditorSubmission->getCurrentRound());
			$article->setReviewFileId($sectionEditorSubmission->getReviewFileId());
			$article->setEditorFileId($sectionEditorSubmission->getEditorFileId());
			$article->setStatus($sectionEditorSubmission->getStatus());
			$article->setDateStatusModified($sectionEditorSubmission->getDateStatusModified());
			$article->setLastModified($sectionEditorSubmission->getLastModified());
			$article->setCommentsStatus($sectionEditorSubmission->getCommentsStatus());

			$this->articleDao->updateArticle($article);
		}

	}

	function createReviewRound($articleId, $round, $reviewRevision) {
		$this->update(
			'INSERT INTO review_rounds
				(submission_id, round, review_revision)
				VALUES
				(?, ?, ?)',
			array($articleId, $round, $reviewRevision)
		);
	}

	/**
	 * Get all section editor submissions for a section editor.
	 * @param $sectionEditorId int
	 * @param $status boolean true if active, false if completed.
	 * @return array SectionEditorSubmission
	 */
	function &getSectionEditorSubmissions($sectionEditorId, $journalId, $status = true) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$sectionEditorSubmissions = array();

		$result =& $this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				r2.review_revision
			FROM	articles a
				LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.submission_id AND a.current_round = r2.round)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.journal_id = ?
				AND e.editor_id = ?
				AND a.status = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primarylocale,
				'abbrev',
				$locale,
				$journalId,
				$sectionEditorId,
				$status
			)
		);

		while (!$result->EOF) {
			$sectionEditorSubmissions[] =& $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $sectionEditorSubmissions;
	}

	/**
	 * Retrieve unfiltered section editor submissions
	 */
	function &_getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $additionalWhereSql = '', $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$params = array(
			ASSOC_TYPE_ARTICLE,
			'SIGNOFF_COPYEDITING_FINAL',
			ASSOC_TYPE_ARTICLE,
			'SIGNOFF_PROOFREADING_PROOFREADER',
			ASSOC_TYPE_ARTICLE,
			'SIGNOFF_LAYOUT',
			'title', // Section title
			$primaryLocale,
			'title',
			$locale,
			'abbrev', // Section abbrev
			$primaryLocale,
			'abbrev',
			$locale,
			'cleanTitle', // Article title
			'cleanTitle',
			$locale,
			$journalId,
			$sectionEditorId
		);

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(atl.setting_value) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(atl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = ' AND LOWER(atl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$first_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.last_name');
				$first_middle_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.middle_name', '\' \'', 'aa.last_name');
				$last_comma_first = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name', '\' \'', 'aa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(aa.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} elseif ($searchMatch === 'contains') {
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_EDITOR:
				$first_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.last_name');
				$first_middle_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.middle_name', '\' \'', 'ed.last_name');
				$last_comma_first = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name', '\' \'', 'ed.middle_name');
				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(ed.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} elseif ($searchMatch === 'contains') {
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) switch($dateField) {
			case SUBMISSION_FIELD_DATE_SUBMITTED:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND a.date_submitted >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND a.date_submitted <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND c.date_final_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND c.date_final_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND l.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND l.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND p.date_proofreader_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND p.date_proofreader_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				scf.date_completed as copyedit_completed,
				spr.date_completed as proofread_completed,
				sle.date_completed as layout_completed,
				COALESCE(atl.setting_value, atpl.setting_value) AS submission_title,
				aap.last_name AS author_name,
				e.can_review AS can_review,
				e.can_edit AS can_edit,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				r2.review_revision
			FROM	articles a
				LEFT JOIN authors aa ON (aa.submission_id = a.article_id)
				LEFT JOIN authors aap ON (aap.submission_id = a.article_id AND aap.primary_contact = 1)
				LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
				LEFT JOIN users ed ON (e.editor_id = ed.user_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN signoffs scf ON (a.article_id = scf.assoc_id AND scf.assoc_type = ? AND scf.symbolic = ?)
				LEFT JOIN users ce ON (scf.user_id = ce.user_id)
				LEFT JOIN signoffs spr ON (a.article_id = spr.assoc_id AND spr.assoc_type = ? AND spr.symbolic = ?)
				LEFT JOIN users pe ON (pe.user_id = spr.user_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.submission_id and a.current_round = r2.round)
				LEFT JOIN signoffs sle ON (a.article_id = sle.assoc_id AND sle.assoc_type = ? AND sle.symbolic = ?) LEFT JOIN users le ON (le.user_id = sle.user_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
				LEFT JOIN article_settings atpl ON (atpl.article_id = a.article_id AND atpl.setting_name = ? AND atpl.locale = a.locale)
				LEFT JOIN article_settings atl ON (a.article_id = atl.article_id AND atl.setting_name = ? AND atl.locale = ?)
				LEFT JOIN edit_decisions edec ON (a.article_id = edec.article_id)
				LEFT JOIN edit_decisions edec2 ON (a.article_id = edec2.article_id AND edec.edit_decision_id < edec2.edit_decision_id)
			WHERE	a.journal_id = ?
				AND e.editor_id = ?
				AND a.submission_progress = 0' . (!empty($additionalWhereSql)?" AND ($additionalWhereSql)":'') . '
				AND edec2.edit_decision_id IS NULL';

		if ($sectionId) {
			$params[] = $sectionId;
			$searchSql .= ' AND a.section_id = ?';
		}

		$result =& $this->retrieveRange($sql . ' ' . $searchSql . ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : ''),
			$params,
			$rangeInfo
		);

		return $result;
	}

	/**
	 * Get all submissions in review for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsInReview($sectionEditorId, $journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredSectionEditorSubmissions(
			$sectionEditorId, $journalId, $sectionId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status = ' . STATUS_QUEUED . ' AND e.can_review = 1 AND (edec.decision IS NULL OR edec.decision <> ' . SUBMISSION_EDITOR_DECISION_ACCEPT . ')',
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnSectionEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions in editing for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsInEditing($sectionEditorId, $journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredSectionEditorSubmissions(
			$sectionEditorId, $journalId, $sectionId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status = ' . STATUS_QUEUED . ' AND e.can_edit = 1 AND edec.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT,
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnSectionEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions in archives for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsArchives($sectionEditorId, $journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result = $this->_getUnfilteredSectionEditorSubmissions(
			$sectionEditorId, $journalId, $sectionId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status <> ' . STATUS_QUEUED,
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnSectionEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getSectionEditorSubmissionsCount($sectionEditorId, $journalId) {
		$submissionsCount = array();
		for($i = 0; $i < 2; $i++) {
			$submissionsCount[$i] = 0;
		}

		// Fetch a count of submissions in review.
		// "d2" and "d" are used to fetch the single most recent
		// editor decision.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS review_count
			FROM	articles a
				LEFT JOIN edit_assignments e ON (a.article_id = e.article_id)
				LEFT JOIN edit_decisions d ON (a.article_id = d.article_id)
				LEFT JOIN edit_decisions d2 ON (a.article_id = d2.article_id AND d.edit_decision_id < d2.edit_decision_id)
			WHERE	a.journal_id = ?
				AND e.editor_id = ?
				AND a.submission_progress = 0
				AND a.status = ' . STATUS_QUEUED . '
				AND d2.edit_decision_id IS NULL
				AND (d.decision IS NULL OR d.decision <> ' . SUBMISSION_EDITOR_DECISION_ACCEPT . ')',
			array((int) $journalId, (int) $sectionEditorId)
		);
		$submissionsCount[0] = $result->Fields('review_count');
		$result->Close();

		// Fetch a count of submissions in editing.
		// "d2" and "d" are used to fetch the single most recent
		// editor decision.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS editing_count
			FROM	articles a
				LEFT JOIN edit_assignments e ON (a.article_id = e.article_id)
				LEFT JOIN edit_decisions d ON (a.article_id = d.article_id)
				LEFT JOIN edit_decisions d2 ON (a.article_id = d2.article_id AND d.edit_decision_id < d2.edit_decision_id)
			WHERE	a.journal_id = ?
				AND e.editor_id = ?
				AND a.submission_progress = 0
				AND a.status = ' . STATUS_QUEUED . '
				AND d2.edit_decision_id IS NULL
				AND d.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT,
			array((int) $journalId, (int) $sectionEditorId)
		);
		$submissionsCount[1] = $result->Fields('editing_count');
		$result->Close();
		return $submissionsCount;
	}

	//
	// Miscellaneous
	//

	/**
	 * Delete copyediting assignments by article.
	 * @param $articleId int
	 */
	function deleteDecisionsByArticle($articleId) {
		return $this->update(
			'DELETE FROM edit_decisions WHERE article_id = ?',
			$articleId
		);
	}

	/**
	 * Delete review rounds article.
	 * @param $articleId int
	 */
	function deleteReviewRoundsByArticle($articleId) {
		return $this->update(
			'DELETE FROM review_rounds WHERE submission_id = ?',
			$articleId
		);
	}

	/**
	 * Get the editor decisions for a review round of an article.
	 * @param $articleId int
	 * @param $round int
	 */
	function getEditorDecisions($articleId, $round = null) {
		$decisions = array();

		if ($round == null) {
			$result =& $this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? ORDER BY edit_decision_id ASC', $articleId
			);
		} else {
			$result =& $this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? AND round = ? ORDER BY edit_decision_id ASC',
				array($articleId, $round)
			);
		}

		while (!$result->EOF) {
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'editorId' => $result->fields['editor_id'],
				'decision' => $result->fields['decision'],
				'dateDecided' => $this->datetimeFromDB($result->fields['date_decided'])
			);
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $decisions;
	}

	/**
	 * Get the highest review round.
	 * @param $articleId int
	 * @return int
	 */
	function getMaxReviewRound($articleId) {
		$result =& $this->retrieve(
			'SELECT MAX(round) FROM review_rounds WHERE submission_id = ?', $articleId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a review round exists for a specified article.
	 * @param $articleId int
	 * @param $round int
	 * @return boolean
	 */
	function reviewRoundExists($articleId, $round) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_rounds WHERE submission_id = ? AND round = ?', array($articleId, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a reviewer is assigned to a specified article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($articleId, $reviewerId, $round) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_assignments WHERE submission_id = ? AND reviewer_id = ? AND round = ? AND cancelled = 0', array((int) $articleId, (int) $reviewerId, (int) $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers with respect to an article's current round.
	 * @param $journalId int
	 * @param $articleId int
	 * @param $round int
	 * @param $searchType int USER_FIELD_...
	 * @param $search string
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $rangeInfo RangeInfo optional
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForArticle($journalId, $articleId, $round, $searchType = null, $search = null, $searchMatch = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		// Convert the field being searched for to a DB element to select on
		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		// Generate the SQL used to filter the results based on what the user is searching for
		$paramArray = array((int) $articleId, (int) $round);
		$joinInterests = false;
		if($searchType == USER_FIELD_INTERESTS) {
			$paramArray[] = ASSOC_TYPE_USER;
			$paramArray[] = 'interest';
			$joinInterests = true;
		}

		// Push some extra default parameters to the SQL parameter array
		$paramArray[] = (int) $journalId;
		$paramArray[] = ROLE_ID_REVIEWER;

		$searchSql = '';
		if (isset($search) && isset($searchTypeMap[$searchType])) {
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
		} elseif (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}


		// If we are sorting a column, we'll need to configure the additional join conditions
		$sortSelect = '';
		$joinAll = $joinComplete = $joinIncomplete = false;
		$selectQuality = $selectLatest = $selectComplete = $selectAverage = $selectIncomplete = false;
		if($sortBy) switch($sortBy) {
			case 'quality':
				$selectQuality = $joinAll = true;
				break;
			case 'latest':
				$selectLatest = $joinAll = true;
				break;
			case 'done':
				$selectComplete = $joinComplete = true;
				break;
			case 'average':
				$selectAverage = $joinComplete = true;
				break;
			case 'active':
				$selectIncomplete = $joinIncomplete = true;
				break;
		}

		$sql = 'SELECT DISTINCT
				u.user_id,
				u.last_name,
				ar.review_id ' .
				($selectQuality ? ', AVG(ac.quality) AS average_quality ' : '') .
				($selectLatest ? ', MAX(ac.date_notified) AS latest ' : '') .
				($selectComplete ? ', COUNT(ra.review_id) AS completed ' : '') .
				($selectAverage ? ', AVG(ra.date_completed-ra.date_notified) AS average ' : '') .
				($selectIncomplete ? ', COUNT(ai.review_id) AS incomplete ' : '') .
			'FROM roles r, users u
				LEFT JOIN review_assignments ar ON (ar.reviewer_id = u.user_id AND ar.cancelled = 0 AND ar.submission_id = ? AND ar.round = ?) ' .
				($joinInterests ? 'LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id) ':'') .
		 		($joinAll ? 'LEFT JOIN review_assignments ac ON (ac.reviewer_id = u.user_id) ':'') .
				($joinComplete ? 'LEFT JOIN review_assignments ra ON (ra.reviewer_id = u.user_id AND ra.date_completed IS NOT NULL) ':'') .
				($joinIncomplete ? 'LEFT JOIN review_assignments ai ON (ai.reviewer_id = u.user_id AND ai.date_notified IS NOT NULL AND ai.cancelled = 0 AND ai.date_completed IS NULL) ':'') .
			'WHERE u.user_id = r.user_id AND
				r.journal_id = ? AND
				r.role_id = ? ' . $searchSql . ' GROUP BY u.user_id, u.last_name, ar.review_id' .
			($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(
			$sql, $paramArray, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;
	}

	function &_returnReviewerUserFromRow(&$row) { // FIXME
		$user =& $this->userDao->getUser($row['user_id']);
		$user->review_id = $row['review_id'];

		HookRegistry::call('SectionEditorSubmissionDAO::_returnReviewerUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToArticle($journalId, $articleId) {
		$users = array();

		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.article_id = ?)
			WHERE	r.journal_id = ? AND
				r.role_id = ? AND
				a.article_id IS NULL
			ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('reviewer'))
		);

		while (!$result->EOF) {
			$users[] =& $this->userDao->_returnUserFromRowWithData($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $users;
	}

	/**
	 * Check if a copyeditor is assigned to a specified article.
	 * @param $articleId int
	 * @param $copyeditorId int
	 * @return boolean
	 */
	function copyeditorExists($articleId, $copyeditorId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM signoffs WHERE assoc_id = ? AND assoc_type = ? AND user_id = ? AND symbolic = ?', array($articleId, ASSOC_TYPE_ARTICLE, $copyeditorId, 'SIGNOFF_COPYEDITING_INITIAL')
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}

	/**
	 * Retrieve a list of all copyeditors not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getCopyeditorsNotAssignedToArticle($journalId, $articleId, $searchType = null, $search = null, $searchMatch = null) {
		$users = array();

		$paramArray = array(ASSOC_TYPE_USER, 'interest', $articleId, ASSOC_TYPE_ARTICLE, 'SIGNOFF_COPYEDITING_INITIAL', $journalId, RoleDAO::getRoleIdFromPath('copyeditor'));
		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		if (isset($search) && isset($searchTypeMap[$searchType])) {
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
		} elseif (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN signoffs sci ON (sci.user_id = u.user_id AND sci.assoc_id = ? AND sci.assoc_type = ? AND sci.symbolic = ?)
			WHERE	r.journal_id = ? AND
				r.role_id = ? AND
				sci.assoc_id IS NULL
				' . $searchSql . '
			ORDER BY last_name, first_name',
			$paramArray
		);

		while (!$result->EOF) {
			$users[] =& $this->userDao->_returnUserFromRowWithData($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $users;
	}

	/**
	 * Get the assignment counts and last assigned date for all layout editors of the given journal.
	 * @return array
	 */
	function getLayoutEditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result =& $this->retrieve('SELECT sl.user_id AS editor_id, COUNT(sl.assoc_id) AS complete FROM signoffs sl, articles a INNER JOIN signoffs sp ON (sp.assoc_id = a.article_id) WHERE sl.assoc_id = a.article_id AND (sl.date_completed IS NOT NULL AND sp.date_completed IS NOT NULL) AND sl.date_notified IS NOT NULL AND a.journal_id = ? AND sl.symbolic = ? AND sp.symbolic = ? AND sl.assoc_type = ? AND sp.assoc_type = ? GROUP BY sl.user_id', array($journalId, 'SIGNOFF_LAYOUT', 'SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result =& $this->retrieve('SELECT sl.user_id AS editor_id, COUNT(sl.assoc_id) AS complete FROM signoffs sl, articles a INNER JOIN signoffs sp ON (sp.assoc_id = a.article_id) WHERE sl.assoc_id = a.article_id AND (sl.date_completed IS NULL AND sp.date_completed IS NULL) AND sl.date_notified IS NOT NULL AND a.journal_id = ? AND sl.symbolic = ? AND sp.symbolic = ? AND sl.assoc_type = ? AND sp.assoc_type = ? GROUP BY sl.user_id', array($journalId, 'SIGNOFF_LAYOUT', 'SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get last assignment date
		$result =& $this->retrieve('SELECT sl.user_id AS editor_id, MAX(sl.date_notified) AS last_assigned FROM signoffs sl, articles a WHERE sl.assoc_id=a.article_id AND a.journal_id=? AND sl.symbolic = ? AND sl.assoc_type = ? GROUP BY sl.user_id', array($journalId, 'SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $this->datetimeFromDB($row['last_assigned']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given journal.
	 * @return array
	 */
	function getReviewerStatistics($journalId) {
		$statistics = Array();

		// Get latest review request date
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, MAX(r.date_notified) AS last_notified
			FROM	review_assignments r,
				articles a
			WHERE	r.submission_id = a.article_id AND
				a.journal_id = ?
			GROUP BY r.reviewer_id',
			(int) $journalId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get completion status
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, COUNT(*) AS incomplete
			FROM	review_assignments r,
				articles a
			WHERE	r.submission_id = a.article_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.cancelled = 0 AND
				a.journal_id = ?
			GROUP BY r.reviewer_id',
			(int) $journalId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate time taken for completed reviews
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, r.date_notified, r.date_completed
			FROM	review_assignments r,
				articles a
			WHERE	r.submission_id = a.article_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NOT NULL AND
				r.declined = 0 AND
				a.journal_id = ?',
			(int) $journalId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();

			$completed = strtotime($this->datetimeFromDB($row['date_completed']));
			$notified = strtotime($this->datetimeFromDB($row['date_notified']));
			if (isset($statistics[$row['reviewer_id']]['total_span'])) {
				$statistics[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$statistics[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in weeks.
			$statistics[$row['reviewer_id']]['average_span'] = (($statistics[$row['reviewer_id']]['total_span'] / $statistics[$row['reviewer_id']]['completed_review_count']) / 60 / 60 / 24 / 7);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Get the assignment counts and last assigned date for all copyeditors of the given journal.
	 * @return array
	 */
	function getCopyeditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result =& $this->retrieve('SELECT sc.user_id AS editor_id, COUNT(sc.assoc_id) AS complete FROM signoffs sc, articles a WHERE sc.assoc_id = a.article_id AND sc.date_completed IS NOT NULL AND a.journal_id = ? AND sc.symbolic = ? AND sc.assoc_type = ? GROUP BY sc.user_id', array($journalId, 'SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result =& $this->retrieve('SELECT sc.user_id AS editor_id, COUNT(sc.assoc_id) AS incomplete FROM signoffs sc, articles a WHERE sc.assoc_id = a.article_id AND sc.date_completed IS NULL AND a.journal_id = ? AND sc.symbolic = ? AND sc.assoc_type = ? GROUP BY sc.user_id', array($journalId, 'SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get last assignment date
		$result =& $this->retrieve('SELECT sc.user_id AS editor_id, MAX(sc.date_notified) AS last_assigned FROM signoffs sc, articles a WHERE sc.assoc_id = a.article_id AND a.journal_id = ? AND sc.symbolic = ? AND sc.assoc_type = ? GROUP BY sc.user_id', array($journalId, 'SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $this->datetimeFromDB($row['last_assigned']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Get the assignment counts and last assigned date for all proofreaders of the given journal.
	 * @return array
	 */
	function getProofreaderStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result =& $this->retrieve('SELECT sp.user_id AS editor_id, COUNT(sp.assoc_id) AS complete FROM signoffs sp, articles a WHERE sp.assoc_id = a.article_id AND sp.date_completed IS NOT NULL AND a.journal_id = ? AND sp.symbolic = ? AND sp.assoc_type = ? GROUP BY sp.user_id', array($journalId, 'SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result =& $this->retrieve('SELECT sp.user_id AS editor_id, COUNT(sp.assoc_id) AS incomplete FROM signoffs sp, articles a WHERE sp.assoc_id = a.article_id AND sp.date_completed IS NULL AND a.journal_id = ? AND sp.symbolic = ? AND sp.assoc_type = ? GROUP BY sp.user_id', array($journalId, 'SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get last assignment date
		$result =& $this->retrieve('SELECT sp.user_id AS editor_id, MAX(sp.date_notified) AS last_assigned FROM signoffs sp, articles a WHERE sp.assoc_id = a.article_id AND a.journal_id = ? AND sp.symbolic = ? AND sp.assoc_type = ? GROUP BY sp.user_id', array($journalId, 'SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $this->datetimeFromDB($row['last_assigned']);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'id': return 'a.article_id';
			case 'submitDate': return 'a.date_submitted';
			case 'section': return 'section_abbrev';
			case 'authors': return 'author_name';
			case 'title': return 'submission_title';
			case 'active': return 'incomplete';
			case 'subCopyedit': return 'copyedit_completed';
			case 'subLayout': return 'layout_completed';
			case 'subProof': return 'proofread_completed';
			case 'reviewerName': return 'u.last_name';
			case 'quality': return 'average_quality';
			case 'done': return 'completed';
			case 'latest': return 'latest';
			case 'active': return 'active';
			case 'average': return 'average';
			case 'name': return 'u.last_name';
			default: return null;
		}
	}
}

?>
