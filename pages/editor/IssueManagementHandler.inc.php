<?php

/**
 * @file IssueManagementHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueManagementHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for issue management in publishing.
 */

// $Id$

import('pages.editor.EditorHandler');

class IssueManagementHandler extends EditorHandler {
	/** issue associated with the request **/
	var $issue;

	/**
	 * Constructor
	 **/
	function IssueManagementHandler() {
		parent::EditorHandler();
	}

	/**
	 * Displays the listings of future (unpublished) issues
	 */
	function futureIssues() {
		$this->validate(null, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$rangeInfo = Handler::getRangeInfo('issues');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getUnpublishedIssues($journal->getId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('editor/issues/futureIssues.tpl');
	}

	/**
	 * Displays the listings of back (published) issues
	 */
	function backIssues() {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$rangeInfo = Handler::getRangeInfo('issues');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->addJavaScript('lib/pkp/js/jquery.tablednd_0_5.js');
		$templateMgr->addJavaScript('lib/pkp/js/tablednd.js');

		$templateMgr->assign_by_ref('issues', $issueDao->getPublishedIssues($journal->getId(), $rangeInfo));

		$allIssuesIterator = $issueDao->getPublishedIssues($journal->getId());
		$issueMap = array();
		while ($issue =& $allIssuesIterator->next()) {
			$issueMap[$issue->getId()] = $issue->getIssueIdentification();
			unset($issue);
		}
		$templateMgr->assign('allIssues', $issueMap);
		$templateMgr->assign('rangeInfo', $rangeInfo);

		$currentIssue =& $issueDao->getCurrentIssue($journal->getId());
		$currentIssueId = $currentIssue?$currentIssue->getId():null;
		$templateMgr->assign('currentIssueId', $currentIssueId);

		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->assign('usesCustomOrdering', $issueDao->customIssueOrderingExists($journal->getId()));
		$templateMgr->display('editor/issues/backIssues.tpl');
	}

	/**
	 * Removes an issue
	 */
	function removeIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue;
		$isBackIssue = $issue->getPublished() > 0 ? true: false;

		// remove all published articles and return original articles to editing queue
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			foreach ($publishedArticles as $article) {
				$articleDao->changeArticleStatus($article->getId(),STATUS_QUEUED);
				$publishedArticleDao->deletePublishedArticleById($article->getPubId());
			}
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteIssue($issue);
		if ($issue->getCurrent()) {
			$journal =& Request::getJournal();
			$issues = $issueDao->getPublishedIssues($journal->getId());
			if (!$issues->eof()) {
				$issue =& $issues->next();
				$issue->setCurrent(1);
				$issueDao->updateIssue($issue);
			}
		}

		if ($isBackIssue) {
			Request::redirect(null, null, 'backIssues');
		} else {
			Request::redirect(null, null, 'futureIssues');
		}
	}

	/**
	 * Displays the create issue form
	 */
	function createIssue() {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('classes.issue.form.IssueForm');

		$templateMgr =& TemplateManager::getManager();
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.createIssue');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/createIssue.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/createIssue.tpl');
		}

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueForm->initData();
		}
		$issueForm->display();
	}

	/**
	 * Saves the new issue form
	 */
	function saveIssue() {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('classes.issue.form.IssueForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/createIssue.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/createIssue.tpl');
		}

		$issueForm->readInputData();

		if ($issueForm->validate()) {
			$issueId = $issueForm->execute();
			$this->futureIssues();
		} else {
			$templateMgr =& TemplateManager::getManager();
			import('classes.issue.IssueAction');
			$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
			$templateMgr->assign('helpTopicId', 'publishing.createIssue');
			$issueForm->display();
		}
	}

	/**
	 * Displays the issue data page
	 */
	function issueData($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('classes.issue.form.IssueForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/issueData.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/issueData.tpl');
		}

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueId = $issueForm->initData($issueId);
		}
		$templateMgr->assign('issueId', $issueId);

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$issueForm->display();
	}

	/**
	 * Edit the current issue form
	 */
	function editIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('classes.issue.form.IssueForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/issueData.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/issueData.tpl');
		}
		$issueForm->readInputData();

		if ($issueForm->validate($issueId)) {
			$issueForm->execute($issueId);
			$issueForm->initData($issueId);
			//%CBP% add Issue ISBN to table
			$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
			$CBPPlatformDao->setIssueISBN($issueId, Request::getUserVar('isbn'));
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());

		$issueForm->display();
	}

	/**
	 * Remove cover page from issue
	 */
	function removeIssueCoverPage($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$this->validate($issueId, true);

		$formLocale = $args[1];
		if (!Locale::isLocaleValid($formLocale)) {
			Request::redirect(null, null, 'issueData', $issueId);
		}

		import('classes.file.PublicFileManager');
		$issue =& $this->issue;
		$journal =& Request::getJournal();
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$issue->getFileName($formLocale));
		$issue->setFileName('', $formLocale);
		$issue->setOriginalFileName('', $formLocale);
		$issue->setWidth('', $formLocale);
		$issue->setHeight('', $formLocale);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Remove style file from issue
	 */
	function removeStyleFile($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue;

		import('classes.file.PublicFileManager');
		$journal =& Request::getJournal();
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$issue->getStyleFileName());
		$issue->setStyleFileName('');
		$issue->setOriginalStyleFileName('');

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Display the table of contents
	 */
	function issueToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$enablePublicArticleId = $journalSettingsDao->getSetting($journalId,'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enablePageNumber = $journalSettingsDao->getSetting($journalId, 'enablePageNumber');
		$templateMgr->assign('enablePageNumber', $enablePageNumber);
		$templateMgr->assign('customSectionOrderingExists', $customSectionOrderingExists = $sectionDao->customSectionOrderingExists($issueId));

		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished', !$issue->getPublished());
		$templateMgr->assign('issueAccess', $issue->getAccessStatus());
		
		//%CBP% get CBP dao for required sections
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$requiredSections = $CBPPlatformDao->getRequiredSections($journalId);
		foreach ($requiredSections as $requiredSection) {
			$requiredSectionsArr[$requiredSection['title']] = 1;
			if ($requiredSection['delegated'] == "editor") {
				$requiredSectionsDisplay[$requiredSection['title']]['title'] = $requiredSection['title'];
				$requiredSectionsDisplay[$requiredSection['title']]['policy'] = $requiredSection['policy'];
			}
		}		
		$templateMgr->assign_by_ref('requiredSections', $requiredSectionsArr);
		$templateMgr->assign_by_ref('requiredSectionsDisplay', $requiredSectionsDisplay);

		// get issue sections and articles
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);

		$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$proofedArticleIds = $layoutEditorSubmissionDao->getProofedArticlesByIssueId($issueId);
		$templateMgr->assign('proofedArticleIds', $proofedArticleIds);

		$currSection = 0;
		$counter = 0;
		$sections = array();
		$sectionCount = 0;
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		foreach ($publishedArticles as $article) {
			$sectionId = $article->getSectionId();
			if ($currSection != $sectionId) {
				$lastSectionId = $currSection;
				$sectionCount++;
				if ($lastSectionId !== 0) $sections[$lastSectionId][5] = $customSectionOrderingExists?$sectionDao->getCustomSectionOrder($issueId, $sectionId):$sectionCount; // Store next custom order
				$currSection = $sectionId;
				$counter++;
				$sections[$sectionId] = array(
					$sectionId,
					$article->getSectionTitle(),
					array($article),
					$counter,
					$customSectionOrderingExists?
						$sectionDao->getCustomSectionOrder($issueId, $lastSectionId): // Last section custom ordering
						($sectionCount-1),
					null // Later populated with next section ordering
				);
			} else {
				$sections[$article->getSectionId()][2][] = $article;
			}
		}

		$templateMgr->assign_by_ref('sections', $sections);

		$templateMgr->assign('accessOptions', array(
			ARTICLE_ACCESS_ISSUE_DEFAULT => Locale::Translate('editor.issues.default'),
			ARTICLE_ACCESS_OPEN => Locale::Translate('editor.issues.open')
		));
		
		//%CBP% get comments for issue by reviewers and EIC	
		$templateMgr->assign('user', DAORegistry::getDAO('UserDAO'));
		//$templateMgr->assign('issueComments', $CBPPlatformDao->getReviewerIssueComments($issueId));
	
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.tableOfContents');

		$templateMgr->addJavaScript('lib/pkp/js/jquery.tablednd_0_5.js');
		$templateMgr->addJavaScript('lib/pkp/js/tablednd.js');

		$templateMgr->display('editor/issues/issueToc.tpl');
	}

	/**
	 * Updates issue table of contents with selected changes and article removals.
	 */
	function updateIssueToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);

		$journal =& Request::getJournal();

		$removedPublishedArticles = array();

		$publishedArticles = Request::getUserVar('publishedArticles');
		$removedArticles = Request::getUserVar('remove');
		$accessStatus = Request::getUserVar('accessStatus');
		$pages = Request::getUserVar('pages');

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$articles = $publishedArticleDao->getPublishedArticles($issueId);

		foreach($articles as $article) {
			$articleId = $article->getId();
			$pubId = $article->getPubId();
			if (!isset($removedArticles[$articleId])) {
				if (isset($pages[$articleId])) {
					$article->setPages($pages[$articleId]);
				}
				if (isset($publishedArticles[$articleId])) {
					$publicArticleId = $publishedArticles[$articleId];
					if (!$publicArticleId || !$publishedArticleDao->publicArticleIdExists($publicArticleId, $articleId, $journal->getId())) {
						$publishedArticleDao->updatePublishedArticleField($pubId, 'public_article_id', $publicArticleId);
					}
				}
				if (isset($accessStatus[$pubId])) {
					$publishedArticleDao->updatePublishedArticleField($pubId, 'access_status', $accessStatus[$pubId]);
				}
			} else {
				$article->setStatus(STATUS_QUEUED);
				$article->stampStatusModified();

				// If the article is the only one in the section, delete the section from custom issue ordering
				$sectionId = $article->getSectionId();
				$publishedArticleArray =& $publishedArticleDao->getPublishedArticlesBySectionId($sectionId, $issueId);
				if (sizeof($publishedArticleArray) == 1) {
					$sectionDao->deleteCustomSection($issueId, $sectionId);
				}

				$publishedArticleDao->deletePublishedArticleById($pubId);
				$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issueId);
			}
			$articleDao->updateArticle($article);
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Change the sequence of an issue.
	 */
	function setCurrentIssue($args) {
		$issueId = Request::getUserVar('issueId');
		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$this->validate($issueId);
			$issue =& $this->issue;
			$issue->setCurrent(1);
			$issueDao->updateCurrentIssue($journal->getId(), $issue);
		} else {
			$this->validate();
			$issueDao->updateCurrentIssue($journal->getId());
		}
		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of an issue.
	 */
	function moveIssue($args) {
		$issueId = Request::getUserVar('id');
		$this->validate($issueId);
		$prevId = Request::getUserVar('prevId');
		$nextId = Request::getUserVar('nextId');
		$issue =& $this->issue;
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		// If custom issue ordering isn't yet in place, bring it in.
		if (!$issueDao->customIssueOrderingExists($journalId)) {
			$issueDao->setDefaultCustomIssueOrders($journalId);
		}

		$direction = Request::getUserVar('d');
		if ($direction) {
			// Moved using up or down arrow
			$newPos = $issueDao->getCustomIssueOrder($journalId, $issueId) + ($direction == 'u' ? -1.5 : +1.5);
		} else {
			// Drag and Drop
			if ($nextId)
				// we are dropping before the next row
				$newPos = $issueDao->getCustomIssueOrder($journalId, $nextId) - 0.5;
			else
				// we are dropping after the previous row
				$newPos = $issueDao->getCustomIssueOrder($journalId, $prevId) + 0.5;
		}
		$issueDao->moveCustomIssueOrder($journal->getId(), $issueId, $newPos);

		if ($direction) {
			// Only redirect the nonajax call
			Request::redirect(null, null, 'backIssues', null, array("issuesPage" => Request::getUserVar('issuesPage')));
		}
	}

	/**
	 * Reset issue ordering to defaults.
	 */
	function resetIssueOrder($args) {
		$this->validate();

		$journal =& Request::getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteCustomIssueOrdering($journal->getId());

		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSectionToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$journal =& Request::getJournal();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection(Request::getUserVar('sectionId'), $journal->getId());

		if ($section != null) {
			// If issue-specific section ordering isn't yet in place, bring it in.
			if (!$sectionDao->customSectionOrderingExists($issueId)) {
				$sectionDao->setDefaultCustomSectionOrders($issueId);
			}

			$sectionDao->moveCustomSectionOrder($issueId, $section->getId(), Request::getUserVar('newPos'), Request::getUserVar('d') == 'u');
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Reset section ordering to section defaults.
	 */
	function resetSectionOrder($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue;

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		Request::redirect(null, null, 'issueToc', $issue->getId());
	}

	/**
	 * Change the sequence of the articles.
	 */
	function moveArticleToc($args, $request) {
		$this->validate(null, true);
		$pubId = (int) $request->getUserVar('id');

		$journal =& $request->getJournal();

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$publishedArticle =& $publishedArticleDao->getPublishedArticleById($pubId);

		if (!$publishedArticle) $request->redirect(null, null, 'index');

		$articleId = $publishedArticle->getId();
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($articleId, $journal->getId());

		$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());

		if (!$article || !$issue || $publishedArticle->getIssueId() != $issue->getId() || $issue->getJournalId() != $journal->getId()) $request->redirect(null, null, 'index');

		if ($d = $request->getUserVar('d')) {
			// Moving by up/down arrows
			$publishedArticle->setSeq(
				$publishedArticle->getSeq() + ($d == 'u' ? -1.5 : 1.5)
			);
		} else {
			// Moving by drag 'n' drop
			$prevId = $request->getUserVar('prevId');
			if ($prevId == null) {
				$nextId = $request->getUserVar('nextId');
				$nextArticle = $publishedArticleDao->getPublishedArticleById($nextId);
				$publishedArticle->setSeq($nextArticle->getSeq() - .5);
			} else {
				$prevArticle = $publishedArticleDao->getPublishedArticleById($prevId);
				$publishedArticle->setSeq($prevArticle->getSeq() + .5);
			}
		}
		$publishedArticleDao->updatePublishedArticle($publishedArticle);
		$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issue->getIssueId());

		// Only redirect if we're not doing drag and drop
		if ($d) {
			$request->redirect(null, null, 'issueToc', $publishedArticle->getIssueId());
		}
	}

	/**
	 * Publish issue, and ingest converted distribution formats (EPUB, PDF, MOBI and front cover artwork) in repository
	 */
	function publishIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue;

		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$requiredSections = $CBPPlatformDao->getRequiredSections($journalId);
		foreach($requiredSections as $requiredSection) {
			$requiredSectionsArr[$requiredSection['title']] = 1;
		}
		$requiredSections = $requiredSectionsArr;

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticles =& $publishedArticleDao->getPublishedArticles($issueId);
		$numberPublishedArticles = count($publishedArticles);
		
		foreach ($publishedArticles as $publishedArticle) {
			$article =& $articleDao->getArticle($publishedArticle->getId());
			if ($article && $article->getStatus() == STATUS_QUEUED) {
				$article->setStatus(STATUS_PUBLISHED);
				$article->stampStatusModified();
				$articleDao->updateArticle($article);
			}
			//TODO: %CBP% get most recent galley, not just a submission, review version etc
			$articleGalleys = $publishedArticle->getGalleys();
			$articleGalley = end($articleGalleys); //get the most recent galley
			if (strpos($articleGalley->_data['fileName'], "RV") !== false) $path = "/submission/review/";
			if (strpos($articleGalley->_data['fileName'], "ED") !== false) $path = "/submission/editor/";
			if (strpos($articleGalley->_data['fileName'], "PB") !== false) $path = "/public/";
			$issueChapters[] = Array("id" => $article->getArticleId(), "name" => $article->getArticleTitle(), "description" => $article->getArticleAbstract(), "author" => $article->getAuthorString(false, ', ', false), "src" => $articleGalley->_data['fileName'], "dir" => Config::getVar('files', 'files_dir') . "/journals/$journalId/articles/" . $articleGalley->_data['submissionId'] . $path);
			$relatedItems[] = Config::getVar('general', 'component_namespace') . ":" . $article->getArticleId();
			
			//%CBP% retreive docx supplementary files for publication in the book
			$suppFiles = $publishedArticle->getSuppFiles();
			foreach ($suppFiles as $suppFile) {
				if ($suppFile->_data['fileType'] == "application/vnd.openxmlformats-officedocument.wordprocessingml.document") {
					$suppFilesArr[] = $suppFile;
				}
			}
			$issueAuthors[] = $article->getAuthorString(false, ', ', false);
			unset($article);
		}

		//%CBP% adding author biographies section to the end of the book
		if (array_key_exists("Author Biographies", $requiredSections)) {
			foreach ($suppFilesArr as $suppFile) {
				$path = "/supp/";
				$title = $suppFile->getTitle();
				if (stristr($title['en_US'], "author biography") != false) { //if the title of the supplementary file doesn't include the words 'author biography', don't include
					$issueChapters[] = Array("id" => $suppFile->getArticleId(), "name" => $title['en_US'], "src" => $suppFile->_data['fileName'], "dir" => Config::getVar('files', 'files_dir') . "/journals/$journalId/articles/" . $suppFile->getArticleId() . $path, "type" => "supp", "desc" => "Author Biography");
				}
			}
		}

		asort($issueAuthors);
		$numberOfIssueAuthors = count($issueAuthors);
		$i = 0;
		$issueAuthorsString = "";
		$issueAuthors = array_unique($issueAuthors);
		foreach ($issueAuthors as $issueAuthor){
			$issueAuthorsString .= $issueAuthor;
			($i+1 == $numberOfIssueAuthors) ? $issueAuthorsString .= "" : $issueAuthorsString .= "; ";
			$i++;
		}

		//%CBP% Upload file to Repository and update database with PID/DsId
		$digitalObject = new CBPPlatformDigitalObject();
		$epubconvert = new CBPPlatformEpubConvert();
		$mobiconvert = new CBPPlatformMobiConvert();
		$pdfconvert = new CBPPlatformPdfConvert();
		
		$coverImage = Config::getVar('general', 'base_url') . "/" . Config::getVar('files', 'public_files_dir') . "/journals/" . $issue->getJournalId() . "/" . $issue->getIssueFileName();
		$epubArgs = array("author" => $issueAuthorsString, "imprint" => $journal->getJournalTitle(), "title" => $issue->getIssueTitle(), "description" => $issue->getIssueDescription(), "isbn" => $CBPPlatformDao->getIssueISBN($issueId), "cover" => $coverImage);
		
		$epubconvert->createEpub(null, null, "cache/$journalId-$issueId.epub", $issueChapters, $journalId, $epubArgs);
		$mobiconvert->createMobi(null, null, "cache/$journalId-$issueId.mobi", $issueChapters, $journalId, $epubArgs);
		$pdfconvert->createPdf(null, null, "cache/$journalId-$issueId.pdf", $issueChapters, $journalId, $epubArgs, "cache/$journalId-$issueId-FC.pdf");
		
		$digitalObject->setPID($journalId . "-" . $issueId); 
		$digitalObject->setLabel($issue->getIssueTitle()); 
		$digitalObject->setNamespace(Config::getVar('general', 'compiled_namespace'));
		
		///
		$ext = "pdf";
		$digitalObject->setDatastream(Array($ds = "content" => Array ("file" => Config::getVar('general', 'docroot') . "/cache/$journalId-$issueId.$ext", "label" => $issue->getIssueTitle() . ".$ext", "mimetype" => "application/pdf", "filetype" => $ext)));
		///
		$ext = "epub";
		$digitalObject->setDatastream(Array("content02" => Array ("file" => Config::getVar('general', 'docroot') . "/cache/$journalId-$issueId.$ext", "label" => $issue->getIssueTitle() . ".$ext", "mimetype" => "application/epub+zip", "filetype" => $ext)));
		///
		$ext = "mobi";
		$digitalObject->setDatastream(Array("content03" => Array ("file" => Config::getVar('general', 'docroot') . "/cache/$journalId-$issueId.$ext", "label" => $issue->getIssueTitle() . ".$ext", "mimetype" => "application/x-mobipocket-ebook", "filetype" => $ext)));
		///
		$ext = "pdf";
		if (file_exists(Config::getVar('general', 'docroot') . "/cache/$journalId-$issueId-FC.$ext")) $digitalObject->setDatastream(Array("content04" => Array ("file" => Config::getVar('general', 'docroot') . "/cache/$journalId-$issueId-FC.$ext", "label" => $issue->getIssueTitle() . " Front Cover Artwork.pdf", "mimetype" => "application/pdf", "filetype" => $ext)));
		
		$obj = new SimpleXMLElement($digitalObject->readObjectProperties());
		foreach ($obj as $result) {
			$noObjects = $result->count();
		}
		if ($noObjects==0) {
			$digitalObject->createObject();
		}
		$digitalObject->createObjectDatastreams();
		$digitalObject->makeHydraCompliant("book", $issue->getIssueTitle(), $issueAuthorsString, $relatedItems);
		$CBPPlatformDao->setFedoraIssueObjectInformation($digitalObject->obj->PID, $digitalObject->obj->ns, $ds, $issueId);

		//%CBP% don't publish just yet, we don't have EIC approval for the publication, but still create the issue...
		$issue->setCurrent(1);
		$CBPPlatformDao->setIssuePending($issueId, 1);

		// If subscriptions with delayed open access are enabled then
		// update open access date according to open access delay policy
		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $journal->getSetting('enableDelayedOpenAccess')) {

			$delayDuration = $journal->getSetting('delayedOpenAccessDuration');
			$delayYears = (int)floor($delayDuration/12);
			$delayMonths = (int)fmod($delayDuration,12);

			$curYear = date('Y');
			$curMonth = date('n');
			$curDay = date('j');

			$delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth+$delayMonths)/12);
 			$delayOpenAccessMonth = (int)fmod($curMonth+$delayMonths,12);

			$issue->setAccessStatus(ISSUE_ACCESS_SUBSCRIPTION);
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$delayOpenAccessMonth,$curDay,$delayOpenAccessYear)));
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrentIssue($journalId,$issue);

		// Send a notification to associated users
		// %CBP% TODO: move this to EIC publishing approval stage
		/*import('lib.pkp.classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByJournalId($journalId);
		while (!$allUsers->eof()) {
			$user =& $allUsers->next();
			$notificationUsers[] = array('id' => $user->getId());
			unset($user);
		}
		$url = Request::url(null, 'issue', 'current');
		foreach ($notificationUsers as $userRole) {
			$notificationManager->createNotification(
				$userRole['id'], 'notification.type.issuePublished',
				null, $url, 1, NOTIFICATION_TYPE_PUBLISHED_ISSUE
			);
		}
		$notificationManager->sendToMailingList(
			$notificationManager->createNotification(
				0, 'notification.type.issuePublished',
				null, $url, 1, NOTIFICATION_TYPE_PUBLISHED_ISSUE
			)
		);*/

		Request::redirect(null, null, 'issueToc', $issue->getId());
	}

	/**
	 * Unpublish a previously-published issue
	 */
	function unpublishIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue;

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$issue->setCurrent(0);
		$issue->setPublished(0);
		$issue->setDatePublished(null);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'futureIssues');
	}

	/**
	 * Allows editors to write emails to users associated with the journal.
	 */
	function notifyUsers($args) {
		$this->validate(Request::getUserVar('issue'));
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

		$journal =& Request::getJournal();
		$user =& Request::getUser();
		$templateMgr =& TemplateManager::getManager();

		import('lib.pkp.classes.mail.MassMail');
		$email = new MassMail('PUBLISH_NOTIFY');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->addRecipient($user->getEmail(), $user->getFullName());

			switch (Request::getUserVar('whichUsers')) {
				case 'allIndividualSubscribers':
					$recipients =& $individualSubscriptionDao->getSubscribedUsers($journal->getId());
					break;
				case 'allInstitutionalSubscribers':
					$recipients =& $institutionalSubscriptionDao->getSubscribedUsers($journal->getId());
					break;
				case 'allAuthors':
					$recipients =& $authorDao->getAuthorsAlphabetizedByJournal($journal->getId(), null, null, true);
					break;
				case 'allUsers':
					$recipients =& $roleDao->getUsersByJournalId($journal->getId());
					break;
				case 'allReaders':
				default:
					$recipients =& $roleDao->getUsersByRoleId(
						ROLE_ID_READER,
						$journal->getId()
					);
					break;
			}

			while (!$recipients->eof()) {
				$recipient =& $recipients->next();
				$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
				unset($recipient);
			}

			if (Request::getUserVar('includeToc')=='1' && isset($issue)) {
				$issue = $issueDao->getIssueById(Request::getUserVar('issue'));

				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId());

				$templateMgr->assign_by_ref('journal', $journal);
				$templateMgr->assign_by_ref('issue', $issue);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);

				$email->setBody($templateMgr->fetch('editor/notifyUsersEmail.tpl'));

				// Stamp the "users notified" date.
				$issue->setDateNotified(Core::getCurrentDate());
				$issueDao->updateIssue($issue);
			}

			$callback = array(&$email, 'send');
			$templateMgr->setProgressFunction($callback);
			unset($callback);

			$email->setFrequency(10); // 10 emails per callback
			$callback = array(&$templateMgr, 'updateProgressBar');
			$email->setCallback($callback);
			unset($callback);

			$templateMgr->assign('message', 'editor.notifyUsers.inProgress');
			$templateMgr->display('common/progress.tpl');
			echo '<script type="text/javascript">window.location = "' . Request::url(null, 'editor') . '";</script>';
		} else {
			if (!Request::getUserVar('continued')) {
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature()
				));
			}

			$issuesIterator =& $issueDao->getIssues($journal->getId());

			$allUsersCount = $roleDao->getJournalUsersCount($journal->getId());

			// FIXME: There should be a better way of doing this.
			$authors =& $authorDao->getAuthorsAlphabetizedByJournal($journal->getId(), null, null, true);
			$authorCount = $authors->getCount();


			$email->displayEditForm(
				Request::url(null, null, 'notifyUsers'),
				array(),
				'editor/notifyUsers.tpl',
				array(
					'issues' => $issuesIterator,
					'allUsersCount' => $allUsersCount,
					'allReadersCount' => $roleDao->getJournalUsersCount($journal->getId(), ROLE_ID_READER),
					'allAuthorsCount' => $authorCount,
					'allIndividualSubscribersCount' => $individualSubscriptionDao->getSubscribedUserCount($journal->getId()),
					'allInstitutionalSubscribersCount' => $institutionalSubscriptionDao->getSubscribedUserCount($journal->getId()),
				)
			);
		}
	}

	/**
	 * Validate that user is an editor in the selected journal and if the issue id is valid
	 * Redirects to issue create issue page if not properly authenticated.
	 * NOTE: As of OJS 2.2, Layout Editors are allowed if specified in args.
	 */
	function validate($issueId = null, $allowLayoutEditor = false) {
		$issue = null;
		$journal =& Request::getJournal();

		if (!isset($journal)) Validation::redirectLogin();

		if ($issueId) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getIssueById($issueId, $journal->getId());

			if (!$issue) {
				Request::redirect(null, null, 'createIssue');
			}
		}

		if (!Validation::isEditor($journal->getId())) {
			if (isset($journal) && $allowLayoutEditor && Validation::isLayoutEditor($journal->getId())) {
				// We're a Layout Editor. If specified, make sure that the issue is not published.
				if ($issue && !$issue->getPublished()) {
					Validation::redirectLogin();
				}
			} else {
				Validation::redirectLogin();
			}
		}

		$this->issue =& $issue;
		return true;
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to one of EDITOR_SECTION_? defined in EditorHandler.
	 */
	function setupTemplate($level) {
		parent::setupTemplate($level);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('isLayoutEditor', Request::getRequestedPage() == 'layoutEditor');
	}
}
