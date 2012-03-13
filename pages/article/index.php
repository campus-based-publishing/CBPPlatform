<?php

/**
 * @defgroup pages_article
 */
 
/**
 * @file pages/article/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_article
 * @brief Handle requests for article functions. 
 *
 */

// $Id$

switch ($op) {
	case 'view':
	case 'viewPDFInterstitial':
	case 'viewDownloadInterstitial':
	case 'viewArticle':
	case 'viewRST':
	case 'viewFile':
	case 'download':
	case 'downloadSuppFile':
		define('HANDLER_CLASS', 'ArticleHandler');
		import('pages.article.ArticleHandler');
		break;
}

?>
