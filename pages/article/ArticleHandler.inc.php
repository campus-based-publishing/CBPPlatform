<?php

/**
 * @file ArticleHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */


import('classes.rt.ojs.RTDAO');
import('classes.rt.ojs.JournalRT');
import('classes.handler.Handler');
import('classes.rt.ojs.SharingRT');

class ArticleHandler extends Handler {
	/** journal associated with the request **/
	var $journal;

	/** issue associated with the request **/
	var $issue;

	/** article associated with the request **/
	var $article;

	/**
	 * Constructor
	 * @param $request Request
	 */
	function ArticleHandler(&$request) {
		parent::Handler($request);
		$router =& $request->getRouter();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorCustom($this, false, null, null, create_function('$journal', 'return $journal->getSetting(\'publishingMode\') != PUBLISHING_MODE_NONE;'), array($router->getContext($request))));
	}

	/**
	 * View Article.
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, &$request) {
		$router =& $request->getRouter();
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $this->journal;
		$issue =& $this->issue;
		$article =& $this->article;
		$this->setupTemplate();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournal($journal);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($article->getSectionId(), $journal->getId(), true);

		$version = null;
		if ($journalRt->getVersion()!=null && $journalRt->getDefineTerms()) {
			// Determine the "Define Terms" context ID.
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId(), true);
			if ($version) foreach ($version->getContexts() as $context) {
				if ($context->getDefineTerms()) {
					$defineTermsContextId = $context->getContextId();
					break;
				}
			}
		}

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$enableComments = $journal->getSetting('enableComments');

		if (($article->getEnableComments()) && ($enableComments == COMMENTS_AUTHENTICATED || $enableComments == COMMENTS_UNAUTHENTICATED || $enableComments == COMMENTS_ANONYMOUS)) {
			$comments =& $commentDao->getRootCommentsBySubmissionId($article->getId());
		}

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getId());
		}

		if ($galley && !$galley->isHtmlGalley() && !$galley->isPdfGalley()) {
			if ($galley->isInlineable()) {
				$this->viewFile(
					array($galley->getArticleId(), $galley->getId()),
					$request
				);
			} else {
				
				$this->download(
					array($galley->getArticleId(), $galley->getId()),
					$request
				);
			}
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('js/articleView.js');
		$templateMgr->addJavaScript('js/pdfobject.js');

		if (!$galley) {
			// Get the subscription status if displaying the abstract;
			// if access is open, we can display links to the full text.
			import('classes.issue.IssueAction');

			// The issue may not exist, if this is an editorial user
			// and scheduling hasn't been completed yet for the article.
			if ($issue) {
				$templateMgr->assign('subscriptionRequired', IssueAction::subscriptionRequired($issue));
			}

			$templateMgr->assign('subscribedUser', IssueAction::subscribedUser($journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null));
			$templateMgr->assign('subscribedDomain', IssueAction::subscribedDomain($journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null));

			$templateMgr->assign('showGalleyLinks', $journal->getSetting('showGalleyLinks'));

			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			if ( $paymentManager->onlyPdfEnabled() ) {
				$templateMgr->assign('restrictOnlyPdf', true);
			}
			if ( $paymentManager->purchaseArticleEnabled() ) {
				$templateMgr->assign('purchaseArticleEnabled', true);
			}

			// Article cover page.
			$locale = Locale::getLocale();
			if (isset($article) && $article->getLocalizedFileName() && $article->getLocalizedShowCoverPage() && !$article->getLocalizedHideCoverPageAbstract()) {
				import('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$coverPagePath = $request->getBaseUrl() . '/';
				$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
				$templateMgr->assign('coverPagePath', $coverPagePath);
				$templateMgr->assign('coverPageFileName', $article->getLocalizedFileName());
				$templateMgr->assign('width', $article->getLocalizedWidth());
				$templateMgr->assign('height', $article->getLocalizedHeight());
				$templateMgr->assign('coverPageAltText', $article->getLocalizedCoverPageAltText());
			}

			// References list.
			// FIXME: We only display the edited raw citations right now. We also want
			// to allow for generated citations to be displayed here (including a way for
			// the reader to choose any of the installed citation styles for output), see #5938.
			$citationDao =& DAORegistry::getDAO('CitationDAO'); /* @var $citationDao CitationDAO */
			$citationFactory =& $citationDao->getObjectsByAssocId(ASSOC_TYPE_ARTICLE, $article->getId());
			$templateMgr->assign('citationFactory', $citationFactory);

			// Increment the published article's abstract views count
			if (!$request->isBot()) {
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticleDao->incrementViewsByArticleId($article->getId());
			}
		} else {
			if (!$request->isBot() && !$galley->isPdfGalley()) {
				// Increment the galley's views count.
				// PDF galley views are counted in viewFile.
				$galleyDao->incrementViews($galley->getId());
			}

			// Use the article's CSS file, if set.
			if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
				$templateMgr->addStyleSheet($router->url($request, null, 'article', 'viewFile', array(
					$article->getId(),
					$galley->getBestGalleyId($journal),
					$styleFile->getFileId()
				)));
			}
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('version', $version);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('postingAllowed', (
			($article->getEnableComments()) && (
			$enableComments == COMMENTS_UNAUTHENTICATED ||
			(($enableComments == COMMENTS_AUTHENTICATED ||
			$enableComments == COMMENTS_ANONYMOUS) &&
			Validation::isLoggedIn()))
		));
		$templateMgr->assign('enableComments', $enableComments);
		$templateMgr->assign('postingLoginRequired', ($enableComments != COMMENTS_UNAUTHENTICATED && !Validation::isLoggedIn()));
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
		$templateMgr->assign('comments', isset($comments)?$comments:null);

		$templateMgr->assign('sharingEnabled', $journalRt->getSharingEnabled());

		if($journalRt->getSharingEnabled()) {
			$templateMgr->assign('sharingRequestURL', $request->getRequestURL());
			$templateMgr->assign('sharingArticleTitle', $article->getArticleTitle());
			$templateMgr->assign_by_ref('sharingUserName', $journalRt->getSharingUserName());
			$templateMgr->assign_by_ref('sharingButtonStyle', $journalRt->getSharingButtonStyle());
			$templateMgr->assign_by_ref('sharingDropDownMenu', $journalRt->getSharingDropDownMenu());
			$templateMgr->assign_by_ref('sharingBrand', $journalRt->getSharingBrand());
			$templateMgr->assign_by_ref('sharingDropDown', $journalRt->getSharingDropDown());
			$templateMgr->assign_by_ref('sharingLanguage', $journalRt->getSharingLanguage());
			$templateMgr->assign_by_ref('sharingLogo', $journalRt->getSharingLogo());
			$templateMgr->assign_by_ref('sharingLogoBackground', $journalRt->getSharingLogoBackground());
			$templateMgr->assign_by_ref('sharingLogoColor', $journalRt->getSharingLogoColor());
			list($btnUrl, $btnWidth, $btnHeight) = SharingRT::sharingButtonImage($journalRt);
			$templateMgr->assign('sharingButtonUrl', $btnUrl);
			$templateMgr->assign('sharingButtonWidth', $btnWidth);
			$templateMgr->assign('sharingButtonHeight', $btnHeight);
		}

		$templateMgr->assign('articleSearchByOptions', array(
			'' => 'search.allFields',
			ARTICLE_SEARCH_AUTHOR => 'search.author',
			ARTICLE_SEARCH_TITLE => 'article.title',
			ARTICLE_SEARCH_ABSTRACT => 'search.abstract',
			ARTICLE_SEARCH_INDEX_TERMS => 'search.indexTerms',
			ARTICLE_SEARCH_GALLEY_FILE => 'search.fullText'
		));
		
		$templateMgr->display('article/article.tpl');
	}

	/**
	 * Article interstitial page before PDF is shown
	 * @param $args array
	 * @param $request Request
	 * @param $galley ArticleGalley
	 */
	function viewPDFInterstitial($args, &$request, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$this->validate($request, $articleId, $galleyId);
		$journal =& $this->journal;
		$issue =& $this->issue;
		$article =& $this->article;
		$this->setupTemplate();

		if (!$galley) {
			$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
			} else {
				$galley =& $galleyDao->getGalley($galleyId, $article->getId());
			}
		}

		if (!$galley) $request->redirect(null, null, 'view', $articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('article', $article);

		$templateMgr->display('article/pdfInterstitial.tpl');
	}

	/**
	 * Article interstitial page before a non-PDF, non-HTML galley is
	 * downloaded
	 * @param $args array
	 * @param $request Request
	 * @param $galley ArticleGalley
	 */
	function viewDownloadInterstitial($args, &$request, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$this->validate($request, $articleId, $galleyId);
		$journal =& $this->journal;
		$issue =& $this->issue;
		$article =& $this->article;
		$this->setupTemplate();

		if (!$galley) {
			$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
			} else {
				$galley =& $galleyDao->getGalley($galleyId, $article->getId());
			}
		}

		if (!$galley) $request->redirect(null, null, 'view', $articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('article', $article);

		$templateMgr->display('article/interstitial.tpl');
	}

	/**
	 * Article view
	 * @param $args array
	 * @param $request Request
	 */
	function viewArticle($args, &$request) {
		// This function is deprecated since the Reading Tools frameset was removed.
		return $this->view($args, $request);
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $galleyId, $fileId [optional])
	 * @param $request Request
	 */
	function viewFile($args, &$request) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $this->journal;
		$issue =& $this->issue;
		$article =& $this->article;

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getId());
		}

		if (!$galley) $request->redirect(null, null, 'view', $articleId);

		if (!$fileId) {
			$galleyDao->incrementViews($galley->getId());
			$fileId = $galley->getFileId();
		} else {
			if (!$galley->isDependentFile($fileId)) {
				$request->redirect(null, null, 'view', $articleId);
			}
		}
		if (!HookRegistry::call('ArticleHandler::viewFile', array(&$article, &$galley, &$fileId))) {
			import('classes.submission.common.Action');
			Action::viewFile($article->getId(), $fileId);
		}
	}

	/**
	 * Downloads the document
	 * @param $args array
	 * @param $request Request
	 */
	function download($args, &$request) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$issueId = isset($args[2]) ? $args[2] : 0;

		//%LP% is we have passed a PID and namespace, download the file from the repository
		if (intval($articleId) == false && intval($galleyId) == false) {
			import('classes.file.ArticleFileManager'); // FIXME
			return ArticleFileManager::downloadFile($articleId, $galleyId, null, null, $issueId);
		}
						
		$this->validate($request, $articleId, $galleyId);
		$journal =& $this->journal;
		$issue =& $this->issue;
		$article =& $this->article;
	
		if ($article && $galleyId) {
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($article->getId());
			$articleFileManager->downloadFile($galleyId);
		}
	}

	/**
	 * Download a supplementary file
	 * @param $args array
	 * @param $request Request
	 */
	function downloadSuppFile($args, &$request) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$suppId = isset($args[1]) ? $args[1] : 0;
		$this->validate($request, $articleId);
		$journal =& $this->journal;
		$issue =& $this->issue;
		$article =& $this->article;

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		if ($journal->getSetting('enablePublicSuppFileId')) {
			$suppFile =& $suppFileDao->getSuppFileByBestSuppFileId($article->getId(), $suppId);
		} else {
			$suppFile =& $suppFileDao->getSuppFile((int) $suppId, $article->getId());
		}

		if ($article && $suppFile) {
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($article->getId());
			if ($suppFile->isInlineable()) {
				$articleFileManager->viewFile($suppFile->getFileId());
			} else {
				$articleFileManager->downloadFile($suppFile->getFileId());
			}
		}
	}

	/**
	 * Validation
	 * @see lib/pkp/classes/handler/PKPHandler#validate()
	 * @param $request Request
	 * @param $articleId integer
	 * @param $galleyId int or string
	 */
	function validate(&$request, $articleId, $galleyId = null) {
		$router =& $request->getRouter();
		parent::validate(null, $request);

		import('classes.issue.IssueAction');

		$journal =& $router->getContext($request);
		$journalId = $journal->getId();
		$article = $publishedArticle = $issue = null;
		$user =& $request->getUser();
		$userId = $user?$user->getId():0;

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		if ($journal->getSetting('enablePublicArticleId')) {
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByBestArticleId((int) $journalId, $articleId, true);
		} else {
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId((int) $articleId, (int) $journalId, true);
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		if (isset($publishedArticle)) {
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId(), $publishedArticle->getJournalId(), true);
		} else {
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle((int) $articleId, $journalId, true);
		}

		// If this is an editorial user who can view unpublished/unscheduled
		// articles, bypass further validation. Likewise for its author.
		if (($article || $publishedArticle) && (($article && IssueAction::allowedPrePublicationAccess($journal, $article) || ($publishedArticle && IssueAction::allowedPrePublicationAccess($journal, $publishedArticle))))) {
			$this->journal =& $journal;
			$this->issue =& $issue;
			if(isset($publishedArticle)) {
				$this->article =& $publishedArticle;
			} else $this->article =& $article;

			return true;
		}

		// Make sure the reader has rights to view the article/issue.
		if ($issue && $issue->getPublished()) {
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$isSubscribedDomain = IssueAction::subscribedDomain($journal, $issue->getId(), $articleId);

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess') && isset($galleyId) && $galleyId) {
				Validation::redirectLogin();
			}

			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!$isSubscribedDomain && $subscriptionRequired) &&
			     (isset($galleyId) && $galleyId) ) {

				// Subscription Access
				$subscribedUser = IssueAction::subscribedUser($journal, $issue->getId(), $articleId);

				if (!(!$subscriptionRequired || $publishedArticle->getAccessStatus() == ARTICLE_ACCESS_OPEN || $subscribedUser)) {
					// if payment information is enabled,
					import('classes.payment.ojs.OJSPaymentManager');
					$paymentManager =& OJSPaymentManager::getManager();

					if ( $paymentManager->purchaseArticleEnabled() || $paymentManager->membershipEnabled() ) {
						/* if only pdf files are being restricted, then approve all non-pdf galleys
						 * and continue checking if it is a pdf galley */
						if ( $paymentManager->onlyPdfEnabled() ) {
							$galleyDAO =& DAORegistry::getDAO('ArticleGalleyDAO');
							if ($journal->getSetting('enablePublicGalleyId')) {
								$galley =& $galleyDAO->getGalleyByBestGalleyId($galleyId, $articleId);
							} else {
								$galley =& $galleyDAO->getGalley($galleyId, $articleId);
							}
							if ( $galley && !$galley->isPdfGalley() ) {
								$this->journal =& $journal;
								$this->issue =& $issue;
								$this->article =& $publishedArticle;
								return true;
							}
						}

						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("payment.loginRequired.forArticle");
						}

						/* if the article has been paid for then forget about everything else
						 * and just let them access the article */
						$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
						$dateEndMembership = $user->getSetting('dateEndMembership', 0);
						if ( $completedPaymentDAO->hasPaidPerViewArticle($userId, $articleId)
							|| (!is_null($dateEndMembership) && $dateEndMembership > time()) ) {
							$this->journal =& $journal;
							$this->issue =& $issue;
							$this->article =& $publishedArticle;
							return true;
						} else {
							$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getId(), $articleId, $journal->getSetting('purchaseArticleFee'));
							$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

							$templateMgr =& TemplateManager::getManager();
							$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
							exit;
						}
					}

					if (!isset($galleyId) || $galleyId) {
						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("reader.subscriptionRequiredLoginText");
						}
						$request->redirect(null, 'about', 'subscriptions');
					}
				}
			}
		} else {
			$request->redirect(null, 'index');
		}
		$this->journal =& $journal;
		$this->issue =& $issue;
		$this->article =& $publishedArticle;
		return true;
	}

	function setupTemplate() {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION));
	}
}

?>
