<?php

/**
 * @defgroup CBPPlatform
 */

/**
 * @file classes/CBPPlatform/CBPPlatformDAO.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CBPPlatformDAO
 * @ingroup CBPPlatform
 *
 * @brief Operations for CBPPlatform functionality.
 */

	import('classes.CBPPlatform.rest.DigitalObject');
	import('classes.CBPPlatform.conv.EpubConvert');
	import('classes.CBPPlatform.conv.MobiConvert');
	import('classes.CBPPlatform.conv.PdfConvert');

	class CBPPlatformDAO extends DAO {
		
		/**
		 * Set imprint/journal's settings to be type collection or atomistic
		 * @param $journalId id of the journal/imprint
		 * @param $type str type to set
		 */
		public function setImprintType($journalId, $type) {
			$result =& $this->update("DELETE FROM journal_settings WHERE (journal_id = $journalId AND setting_name='atomistic') OR (journal_id = $journalId AND setting_name='collection')"); //first, remove any previous settings
			$result =& $this->update(
				"INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES ($journalId, '$type', 1, 'bool')"
			);
			$type == collection ? $other = "atomistic" : $other = "collection";
			$result =& $this->update(
				"INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES ($journalId, '$other', 0, 'bool')"
			);
		}
		
		/**
		 * Get imprint/journal's type (collection or atomistic)
		 * @param $journalId id of the journal/imprint
		 * @param $type str type
		 * @return str
		 */
		public function getImprintType($journalId) {
			$result =& $this->retrieve("SELECT setting_name FROM journal_settings WHERE (setting_value = 1 AND journal_id = $journalId AND setting_name='atomistic') OR (setting_value = 1 AND journal_id = $journalId AND setting_name='collection')"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);
		}
		
		/**
		 * Get reviewer comments on an article
		 * @param $articleId int Id of the article
		 * @param $userId int Id of the user the article belongs to
		 * @param $reviewerId int Id of reviewer
		 * @param $viewableOnly int Retrieve comments for public only
		 * @return array
		 */
		public function getReviewerComments($articleId, $userId, $reviewerId = 0, $viewableOnly = 1) {
			$q = 	"SELECT DISTINCT article_comments.comments, article_comments.date_posted, article_comments.viewable, review_assignments.round, review_assignments.reviewer_file_id, users.first_name, users.middle_name, users.last_name, article_files.fedora_namespace, article_files.fedora_pid, article_files.fedora_dsid
					FROM article_comments 
					LEFT JOIN roles ON article_comments.role_id = roles.role_id 
					LEFT JOIN review_assignments ON article_comments.assoc_id = review_assignments.review_id
					LEFT JOIN users ON review_assignments.reviewer_id = users.user_id
					LEFT JOIN article_files ON review_assignments.reviewer_file_id = article_files.file_id
					WHERE article_comments.article_id = $articleId";
			if ($viewableOnly == 1) $q .= " AND article_comments.viewable = 1";
			$q .=	" AND article_comments.comment_type = 1 
					ORDER BY date_posted DESC";
			$result =& $this->retrieve($q);
			$row = $result->GetArray();
			$result->Close();
			unset($result);
			foreach ($row as $key=>$value) {
				$rowSorted[$value['round']][] = $value;
			}
			return $rowSorted;
		}
		
		/**
		 * Get review comments on an article
		 * @param $reviewId int Id of the review
		 * @return array
		 */
		public function getReviewComments($reviewId) {
			$result =& $this->retrieve(
				"SELECT comments, viewable FROM article_comments WHERE assoc_id = $reviewId"
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Get required sections for a journal/imprint
		 * @param $journalId int Id of the journal
		 * @param $role str filter by user role
		 * @param $filter bool true or false to filter by user role
		 * @return array
		 */
		public function getRequiredSections($journalId, $role = 0, $filter = 1) {
			$result =& $this->retrieve(
				'SELECT s.section_id, ss.setting_name, ss.setting_value FROM sections AS s LEFT JOIN section_settings AS ss ON s.section_id = ss.section_id WHERE s.journal_id = ?',
				array($journalId)
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			foreach($row as $key=>$value){
				$rowArr[$value['section_id']][$value['setting_name']] = $value['setting_value']; 
			}
			if ($filter == 1) {
				foreach($rowArr as $key=>$value){
					if (!isset($value['compulsary']) || !isset($value['delegated']) || $value['compulsary'] == 0 || $value['delegated'] != $role) {
						unset($rowArr[$key]);
					}
				}
			}
			return $rowArr;
		}
		
		/**
		 * Set required sections for a journal/imprint
		 * @param $requiredSections arr Required sections
		 * @param $journalId int Id of the journal
		 * @return true
		 */
		public function setRequiredSections($requiredSections, $journalId) {
			$row = $this->getRequiredSections($journalId, 0, 0);
			$i=1;
			if (count($row) <= 1) { 
				foreach($requiredSections as $requiredSection) {
					$result =& $this->update(
						"INSERT INTO sections (journal_id, seq) VALUES ($journalId, $i)"
					);
					$i++;
					$sectionId = $this->getInsertId('settings', 'section_id');
					foreach($requiredSection as $key=>$value) {
						$this->update(
							"INSERT INTO section_settings (section_id, setting_name, setting_value, locale) VALUES ('$sectionId', '$key', '$value', 'en_US')"
						); 
					}
				}
			} else {
				foreach ($requiredSections as &$requiredSection) {
					foreach ($row as $key => $section) {
						if ($requiredSection['title'] == $section['title'] && $requiredSection['compuslary'] != $section['compulsary']) {
							$this->update(
								"UPDATE section_settings SET setting_value = ? WHERE section_id = ? AND setting_name = 'compulsary'"
							, array ($requiredSection['compulsary'], $key));
						}
					}
				}
			}
			return true;
		}
		
		/**
		 * Set CSS stylesheet for distribution formats for journal/imprint
		 * @param $stylesheet str stylesheet to set
		 * @param $journalId int Id of the journal
		 * @return true
		 */
		public function setImprintStylesheet($stylesheet, $journalId) {
			$result =& $this->update("DELETE FROM journal_settings WHERE setting_name = 'imprintStylesheet' AND journal_id = $journalId"); //first, remove any previous settings
			$result =& $this->update("INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES ($journalId, 'imprintStylesheet', '$stylesheet', 'string')");
			return true;
		}
		
		/**
		 * Get CSS stylesheet for distribution formats for journal/imprint
		 * @param $journalId int Id of the journal
		 * @return str
		 */
		public function getImprintStylesheet($journalId) {
			$result =& $this->retrieve("SELECT setting_value FROM journal_settings WHERE setting_name = 'imprintStylesheet' AND journal_id = $journalId");
			$row = $result->GetRowAssoc(false);
			$result->Close();
			return $row['setting_value'];
		}
		
		/**
		 * Get and sort articles by criteria
		 * @param $number int number of articles to select
		 * @param $orderBy str criteria to order by
		 * @param $sort str criteria to sort by
		 * @return array
		 */
		public function getArticlesByCriteria($number = 50, $orderBy = "views", $sort = "desc") {
			$result =& $this->retrieve(
				"SELECT pa.article_id, pa.date_published, j.path, a.setting_value, pa.views, aut.first_name, aut.middle_name, aut.last_name FROM published_articles AS pa LEFT JOIN article_settings AS a ON pa.article_id = a.article_id LEFT JOIN articles AS art ON pa.article_id = art.article_id LEFT JOIN authors AS aut ON aut.submission_id = pa.article_id LEFT JOIN journals AS j ON art.journal_id = j.journal_id WHERE a.setting_name = 'title' ORDER BY $orderBy $sort LIMIT $number"
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Get latest issues
		 * @param $limit int number of issues to select
		 * @return array
		 */
		public function getLatestIssues($limit = 3) {
			$result =& $this->retrieve(
				"SELECT j.path, j.journal_id, i.date_published, i.issue_id, ise.setting_value FROM journals AS j LEFT JOIN issues AS i ON i.journal_id = j.journal_id LEFT JOIN issue_settings AS ise ON ise.issue_id = i.issue_id WHERE setting_name='title' AND i.published = 1 ORDER BY i.date_published DESC LIMIT $limit"
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Get article's creatively complete status
		 * @param $articleId int Id of the article
		 * @return true
		 */
		public function getArticleCreativelyComplete($articleId) {
			$result =& $this->retrieve(
				"SELECT a.creatively_complete, a.status FROM articles AS a WHERE article_id = $articleId"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			if ($row['creatively_complete'] == 1) { 
				return true; 
			} elseif ($row['status'] == 3) {
				return "public";
			} else {
				return false; 
			}
		}
		
		/**
		 * Set article's creatively complete status to true
		 * @param $articleId int Id of the article
		 * @return true
		 */
		public function setArticleAsCreativelyComplete($articleId) {
			if ($result =& $this->update(
				"UPDATE articles a SET a.creatively_complete = 1 WHERE article_id = $articleId"
			)) return true;
		}
		
		/**
		 * Get most recent review file for an article
		 * @param $articleId int Id of the article
		 * @return array
		 */
		public function getMostRecentReviewFile($articleId) {
			$result =& $this->retrieve(
				"SELECT af.file_id FROM article_files as af WHERE article_id = $articleId AND type='submission/review' ORDER BY date_modified DESC LIMIT 1"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Set article galley
		 * @param $articleId int Id of the article
		 * @param $fileId int Id of the file to set as galley
		 * @param $label str label for the galley
		 * @param $locale str locale
		 * @return true
		 */
		public function setArticleGalley($articleId, $fileId, $label = "document", $locale = "en_US") {
			if ($result =& $this->update(
				"INSERT INTO article_galleys (article_id, file_id, label, locale) VALUES ($articleId, $fileId, '$label', '$locale')"
			)) return true;
		}
		
		/**
		 * Get all articles within a journal/imprint
		 * @param $journalId int Id of the journal/imprint
		 * @return array
		 */
		public function getAllJournalArticles($journalId) {
			$result =& $this->retrieve(
				"SELECT * FROM published_articles AS pa LEFT JOIN articles AS a ON pa.article_id = a.article_id LEFT JOIN article_settings AS ase ON pa.article_id = ase.article_id LEFT JOIN article_files AS af ON pa.article_id = af.article_id LEFT JOIN authors AS aut ON pa.article_id = aut.submission_id WHERE ase.setting_name = 'title' AND a.journal_id = $journalId AND af.type = 'public' ORDER BY ase.setting_name ASC"
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Set Fedora object information for a given file revision
		 * @param $PID str fedora PID
		 * @param $ns str fedora namespace
		 * @param $ds str fedora datastream Id
		 * @param $fileId int file Id to set the information for
		 * @param $revision int revision Id to set the information for 
		 * @return true
		 */
		public function setFedoraObjectInformation($PID, $ns = "hull-lp", $ds, $fileId, $revision) {
			if ($result =& $this->update(
				"UPDATE article_files af SET af.fedora_namespace = '$ns', af.fedora_pid = '$PID', af.fedora_dsid = '$ds' WHERE file_id = $fileId AND revision = $revision"
			)) return true;
		}
		
		/**
		 * Get Fedora object information for a given file
		 * @param $fileId int file Id to get the information for
		 * @return array
		 */
		public function getFedoraObjectInformation($fileId) {
			$result =& $this->retrieve(
				"SELECT af.fedora_namespace, af.fedora_pid, af.fedora_dsid FROM article_files AS af WHERE file_id = $fileId"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Get Fedora object information by a given datastream Id
		 * @param $dsid int datastream Id to get the information by
		 * @return array
		 */
		public function getFedoraObjectInformationByDsid($dsid) {
			$result =& $this->retrieve(
				"SELECT * FROM article_files AS af WHERE fedora_dsid = '$dsid'"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Set Fedora object information for a given issue/book
		 * @param $PID str fedora PID
		 * @param $ns str fedora namespace
		 * @param $ds str fedora datastream Id
		 * @param $issueId int issue Id to set the information for
		 * @return true
		 */
		public function setFedoraIssueObjectInformation($PID, $ns = "hull-lp", $ds, $issueId) {
			if ($result =& $this->update(
				"UPDATE issues SET fedora_pid = '$PID', fedora_namespace = '$ns', fedora_dsid = '$ds' WHERE issue_id = $issueId"
			)) return true;
		}
		
		/**
		 * Get Fedora object information for a given issue/book
		 * @param $issueId int issue Id to get the information for
		 * @return array
		 */
		public function getFedoraIssueObjectInformation($issueId) {
			$result =& $this->retrieve(
				"SELECT i.fedora_namespace, i.fedora_pid, i.fedora_dsid FROM issues AS i WHERE issue_id = $issueId"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Set ISBN for a given issue/book
		 * @param $issueId int issue Id to set the ISBN for
		 * @param $isbn str isbn
		 * @return true
		 */
		public function setIssueISBN($issueId, $isbn) {
			if ($result =& $this->update(
				"UPDATE issues i SET i.ISBN = '$isbn' WHERE issue_id = $issueId"
			)) return true;
		}
		
		/**
		 * Get ISBN for a given issue/book
		 * @param $issueId int issue Id to get the ISBN for
		 * @return array
		 */
		public function getIssueISBN($issueId) {
			$result =& $this->retrieve(
				"SELECT i.ISBN FROM issues AS i WHERE issue_id = $issueId"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);
		}
		
		/**
		 * Set registration criteria for a given journal/imprint
		 * @param $journalId int journal/imprint to set the criteria for
		 * @param $criteria str registration criteria
		 * @return true
		 */
		public function setRegistrationCriteria($journalId, $criteria) {
			$result =& $this->update("DELETE FROM journal_settings WHERE (journal_id = $journalId AND setting_name='registrationCriteria')"); //first, remove any previous settings
			$result =& $this->update(
				"INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES ($journalId, 'registrationCriteria', '$criteria', 'string')"
			);
			return true;
		}
		
		/**
		 * Get registration criteria for a given journal/imprint
		 * @param $journalId int journal/imprint to get the criteria for
		 * @return array
		 */
		public function getRegistrationCriteria($journalId) {
			$result =& $this->retrieve("SELECT setting_value FROM journal_settings WHERE (journal_id = $journalId AND setting_name='registrationCriteria')"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);
		}
		
		/**
		 * Set workshop type for a given journal/imprint
		 * @param $journalId int journal/imprint to set
		 * @param $type str type
		 * @return true
		 */
		public function setWorkshop($journalId, $type) {
			$result =& $this->update("DELETE FROM journal_settings WHERE (journal_id = $journalId AND setting_name='workshop')"); //first, remove any previous settings
			$result =& $this->update(
				"INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES ($journalId, 'workshop', '$type', 'string')"
			);
			return true;
		}
		
		/**
		 * Get workshop type for a given journal/imprint
		 * @param $journalId int journal/imprint Id
		 * @return str
		 */
		public function getWorkshop($journalId) {
			$result =& $this->retrieve("SELECT setting_value FROM journal_settings WHERE (journal_id = $journalId AND setting_name='workshop')"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);
		}
		
		/**
		 * Get articles that belong to a workshop-type journal/imprint
		 * @param $journalId int journal/imprint Id
		 * @param $userId int user Id
		 * @return array
		 */
		public function getWorkshopArticles($journalId, $userId) {
			$result =& $this->retrieve(
				"SELECT pa.article_id, pa.current_round, DATEDIFF(curdate(), pa.date_submitted) AS date_difference, pa.date_submitted, a.setting_value, aut.first_name, aut.middle_name, aut.last_name, ra.review_id FROM articles AS pa LEFT JOIN article_settings AS a ON pa.article_id = a.article_id LEFT JOIN articles AS art ON pa.article_id = art.article_id LEFT JOIN authors AS aut ON aut.submission_id = pa.article_id LEFT JOIN review_assignments AS ra ON ra.submission_id = pa.article_id AND ra.reviewer_id = $userId WHERE art.journal_id = $journalId AND a.setting_name = 'title' AND pa.status = 1 GROUP BY pa.article_id ORDER BY a.setting_value ASC"
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			for ($i = 0; $i < count($row); $i++) {
				if ($row[$i]['date_difference'] < 8) $row[$i]['highlight'] = 1; 
				$row[$i]['title'] = $row[$i]['setting_value'];
				unset($row[$i]['setting_value']);
			}
			$result =& $this->retrieve(
				"SELECT review_id, submission_id FROM review_assignments WHERE date_completed IS NOT NULL AND reviewer_id = $userId"
			);
			$row2 = $result->GetAll();
			$result->Close();
			unset($result);
			foreach ($row2 as $value) {
				if(!$reviewCount[$value['submission_id']])  {
					$reviewCount[$value['submission_id']] = 1;
				} else {
					$reviewCount[$value['submission_id']]++;
				}
			}
			foreach($reviewCount as $key=>$value) {
				foreach($row as &$rowValue) {
					if ($key == $rowValue['article_id']) {
						$rowValue['review_count'] = $value;
					}
				}
			}
			return $row;
		}
		
		/**
		 * Set workshop reviewer (Create a review assignment e.g. voluntary review by reviewer)
		 * @param $articleId int article Id
		 * @param $userId int Id of the reviewing user
		 * @return int
		 */
		public function setWorkshopReviewer($articleId, $userId) {
			$result =& $this->update(
				"INSERT INTO review_assignments (submission_id, reviewer_id, round) VALUES ($articleId, $userId, 1)"
			);
			return $this->getInsertId();
		}
		
		/**
		 * Get the completed date of a review assignment (e.g. checking if a review assignment is complete)
		 * @param $reviewId int review Id
		 * @return str
		 */
		public function getWorkshopReviewCompleted($reviewId) {
			$result =& $this->retrieve(
				"SELECT date_completed FROM review_assignments WHERE review_id = $reviewId LIMIT 1"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);	
		}
		
		/**
		 * Get the review assignment Id of an article
		 * @param $articleId int article Id
		 * @param $userId int user (reviewer) Id
		 * @return str
		 */
		public function getWorkshopReviewId($articleId, $userId) {
			$result =& $this->retrieve(
				"SELECT review_id FROM review_assignments WHERE reviewer_id = $userId AND submission_id = $articleId ORDER BY review_id DESC LIMIT 1"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);
		}
		
		/**
		 * Set an author requesting an editor be brought into proceedings
		 * @param $articleId int article Id
		 * @return true
		 */
		public function setAuthorRequestEditorAttention($articleId) {
			if ($result =& $this->update(
				"UPDATE articles SET editor_attention = 1 WHERE article_id = $articleId"
			)) return true;
		}
		
		/**
		 * Set editor attention as complete
		 * @param $articleId int article Id
		 * @return true
		 */
		public function setEditorAttentionComplete($articleId) {
			if ($result =& $this->update(
				"UPDATE articles SET editor_attention = 0 WHERE article_id = $articleId"
			)) return true;
		}
		
		/**
		 * Get an author's request for editor attention
		 * @param $articleId int article Id
		 * @return str
		 */
		public function getAuthorRequestEditorAttention($articleId) {
			$result =& $this->retrieve(
				"SELECT editor_attention FROM articles WHERE article_id = $articleId"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return reset($row);
		}
		
		/**
		 * Get issues that are pending EIC publishing approval
		 * @param $journalId int journal Id
		 * @param $issueId int issue Id
		 * @return array
		 */
		public function getPendingIssues($journalId = null, $issueId = null) {
			$q = "SELECT * FROM issues WHERE pending = 1";
			if ($journalId != null) $q .= " AND journal_id = $journalId";
			if ($issueId != null) $q .= " AND issue_id = $issueId";
			$result =& $this->retrieve($q);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Set an issue's/book's pending status
		 * @param $issueId int issue Id
		 * @param $value int value (1 or 0)
		 * @return true
		 */
		public function setIssuePending($issueId, $value) {
			if ($result =& $this->update(
				"UPDATE issues SET pending = $value WHERE issue_id = $issueId"
			)) return true;
		}
		
		/**
		 * Get user registrations that are pending authorisation/confirmation by an imprint/journal manager
		 * @param $journalId int Id of the journal/imprint
		 * @return array
		 */
		public function getPendingUserRegistrations($journalId) {
			$result =& $this->retrieve(
				"SELECT * FROM roles AS r LEFT JOIN users AS u ON r.user_id = u.user_id WHERE r.journal_id = $journalId AND r.approved = 0 "
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Get user registration status for a user
		 * @param $journalId int Id of the journal/imprint
		 * @param $userId int user Id
		 * @return array
		 */
		public function getUserRegistrationPending($journalId = null, $userId) {
			$q = "SELECT r.approved FROM roles AS r WHERE r.user_id = $userId";
			if ($journalId != null) $q .= " AND r.journal_id = $journalId";
			$result =& $this->retrieve(
				$q
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;	
		}
		
		/**
		 * Set a user's registration status
		 * @param $userId int user Id
		 * @param $roleId int role Id to set status for
		 * @param $journalId int journal Id
		 * @return true
		 */
		public function setUserRegistration($userId, $roleId, $journalId) {
			if ($result =& $this->update(
				"UPDATE roles SET approved = 1 WHERE user_id = $userId AND role_id = $roleId AND journal_id = $journalId"
			)) return true;
		}
		
		/**
		 * Set a comment for an issue/book
		 * @param $issueId int issue Id
		 * @param $reviewerId int reviewer Id
		 * @param $comment str comment
		 * @return int comment Id
		 */
		public function setReviewerIssueComment($issueId, $reviewerId, $comment) {
			$result =& $this->update(
				"INSERT INTO issue_comments (issue_id, reviewer_id, comment) VALUES ($issueId, $reviewerId, '$comment')"
			);
			return $this->getInsertId();
		}
		
		/**
		 * Get comments for an issue/book
		 * @param $issueId int issue Id
		 * @return array
		 */
		public function getReviewerIssueComments($issueId) {
			$result =& $this->retrieve(
				"SELECT * FROM issue_comments WHERE issue_id = $issueId ORDER BY date_commented DESC"
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Get copyright statement for a journal/imprint
		 * @param $journalId journal Id
		 * @return array
		 */
		public function getJournalCopyrightStatement($journalId) {
			$result =& $this->retrieve(
				"SELECT setting_value FROM journal_settings WHERE journal_id = $journalId AND setting_name = 'copyrightNotice'"
			);
			$row = $result->GetRowAssoc(false);
			$result->Close();
			unset($result);
			return $row;
		}
		
		/**
		 * Set file id for an article galley
		 * @param  $articleId int article Id
		 * @param  $fileId int file Id
		 * @return true
		 */
		public function setGalleyFileId($articleId, $fileId) {
			if ($result =& $this->update(
				"UPDATE article_galleys SET file_id = $fileId WHERE article_id = $articleId"
			)) return true;	
		}
		
		/**
		 * Get most recent uploaded supplementary files for an article
		 * @param $articleId int article Id
		 * @param $fileId int file Id (to retrieve a specific supplementary file)
		 * @return array
		 */
		public function getMostRecentSupplementaryFiles($articleId, $fileId = null) {
			$q = "SELECT * FROM article_files WHERE type = 'supp' AND article_id = $articleId";
			if ($fileId != null) $q .= " AND file_id = $fileId";
			$q .= " ORDER BY file_id DESC LIMIT 2";
			$result =& $this->retrieve(
				$q
			);
			$row = $result->GetAll();
			$result->Close();
			unset($result);
			return $row;	
		}
		
		/**
		 * Set file version to use for a review round
		 * @param $articleId int article Id
		 * @param $revision int file revision
		 * @param $round int review round
		 * @param $reviewFileId int review file Id
		 * @return true
		 */
		public function setReviewRoundFileRevision($articleId, $revision, $round, $reviewFileId) {
			if ($result =& $this->update(
				"UPDATE review_rounds SET review_revision = $revision WHERE submission_id = $articleId AND round = $round"
			) && $result =& $this->update(
				"UPDATE articles SET review_file_id = $reviewFileId WHERE article_id = $articleId"
			)) return true;	
		}
		
	}