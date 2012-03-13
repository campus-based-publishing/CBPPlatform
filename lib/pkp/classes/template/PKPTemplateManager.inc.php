<?php

/**
 * @defgroup template
 */

/**
 * @file classes/template/PKPTemplateManager.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 */


/* This definition is required by Smarty */
define('SMARTY_DIR', Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'smarty' . DIRECTORY_SEPARATOR);

require_once('./lib/pkp/lib/smarty/Smarty.class.php');
require_once('./lib/pkp/lib/smarty/plugins/modifier.escape.php'); // Seems to be needed?

define('CACHEABILITY_NO_CACHE',		'no-cache');
define('CACHEABILITY_NO_STORE',		'no-store');
define('CACHEABILITY_PUBLIC',		'public');
define('CACHEABILITY_MUST_REVALIDATE',	'must-revalidate');
define('CACHEABILITY_PROXY_REVALIDATE',	'proxy-revalidate');

define('CDN_JQUERY_VERSION', '1.4.2');
define('CDN_JQUERY_UI_VERSION', '1.8.1');

class PKPTemplateManager extends Smarty {
	/** @var $styleSheets array of URLs to stylesheets */
	var $styleSheets;

	/** @var $javaScripts array of URLs to javascript files */
	var $javaScripts;

	/** @var $initialized Kludge because of reference problems with
	    TemplateManager::getManager() invoked during constructor process */
	var $initialized;

	/** @var $cacheability string Type of cacheability (Cache-Control). */
	var $cacheability;

	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 * @param $request PKPRequest FIXME: is optional for backwards compatibility only - make mandatory
	 */
	function PKPTemplateManager($request = null) {
		// FIXME: for backwards compatibility only - remove
		if (!isset($request)) {
			if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function call.');
			$request =& Registry::get('request');
		}
		assert(is_a($request, 'PKPRequest'));

		// Retrieve the router
		$router =& $request->getRouter();
		assert(is_a($router, 'PKPRouter'));

		parent::Smarty();

		// Set up Smarty configuration
		$baseDir = Core::getBaseDir();
		$cachePath = CacheManager::getFileCachePath();

		// Set the default template dir (app's template dir)
		$this->app_template_dir = $baseDir . DIRECTORY_SEPARATOR . 'templates';
		// Set fallback template dir (core's template dir)
		$this->core_template_dir = $baseDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'templates';

		$this->template_dir = array($this->app_template_dir, $this->core_template_dir);
		$this->compile_dir = $cachePath . DIRECTORY_SEPARATOR . 't_compile';
		$this->config_dir = $cachePath . DIRECTORY_SEPARATOR . 't_config';
		$this->cache_dir = $cachePath . DIRECTORY_SEPARATOR . 't_cache';


		// Assign common variables
		$this->styleSheets = array();
		$this->assign_by_ref('stylesheets', $this->styleSheets);

		$this->javaScripts = array();

		$this->cacheability = CACHEABILITY_NO_STORE; // Safe default

		$this->assign('defaultCharset', Config::getVar('i18n', 'client_charset'));
		$this->assign('basePath', $request->getBasePath());
		$this->assign('baseUrl', $request->getBaseUrl());
		$this->assign('requiresFormRequest', $request->isPost());
		if (is_a($router, 'PKPPageRouter')) $this->assign('requestedPage', $router->getRequestedPage($request));
		$this->assign('currentUrl', $request->getCompleteUrl());
		$this->assign('dateFormatTrunc', Config::getVar('general', 'date_format_trunc'));
		$this->assign('dateFormatShort', Config::getVar('general', 'date_format_short'));
		$this->assign('dateFormatLong', Config::getVar('general', 'date_format_long'));
		$this->assign('datetimeFormatShort', Config::getVar('general', 'datetime_format_short'));
		$this->assign('datetimeFormatLong', Config::getVar('general', 'datetime_format_long'));
		$this->assign('timeFormat', Config::getVar('general', 'time_format'));
		$this->assign('allowCDN', Config::getVar('general', 'enable_cdn'));

		$locale = Locale::getLocale();
		$this->assign('currentLocale', $locale);

		// If there's a locale-specific stylesheet, add it.
		if (($localeStyleSheet = Locale::getLocaleStyleSheet($locale)) != null) $this->addStyleSheet($request->getBaseUrl() . '/' . $localeStyleSheet);

		$application =& PKPApplication::getApplication();
		$this->assign('pageTitle', $application->getNameKey());

		// Register custom functions
		$this->register_modifier('translate', array('Locale', 'translate'));
		$this->register_modifier('get_value', array(&$this, 'smartyGetValue'));
		$this->register_modifier('strip_unsafe_html', array('String', 'stripUnsafeHtml'));
		$this->register_modifier('String_substr', array('String', 'substr'));
		$this->register_modifier('to_array', array(&$this, 'smartyToArray'));
		$this->register_modifier('concat', array(&$this, 'smartyConcat'));
		$this->register_modifier('escape', array(&$this, 'smartyEscape'));
		$this->register_modifier('strtotime', array(&$this, 'smartyStrtotime'));
		$this->register_modifier('explode', array(&$this, 'smartyExplode'));
		$this->register_modifier('assign', array(&$this, 'smartyAssign'));
		$this->register_function('translate', array(&$this, 'smartyTranslate'));
		$this->register_function('flush', array(&$this, 'smartyFlush'));
		$this->register_function('call_hook', array(&$this, 'smartyCallHook'));
		$this->register_function('html_options_translate', array(&$this, 'smartyHtmlOptionsTranslate'));
		$this->register_block('iterate', array(&$this, 'smartyIterate'));
		$this->register_function('call_progress_function', array(&$this, 'smartyCallProgressFunction'));
		$this->register_function('page_links', array(&$this, 'smartyPageLinks'));
		$this->register_function('page_info', array(&$this, 'smartyPageInfo'));
		$this->register_function('get_help_id', array(&$this, 'smartyGetHelpId'));
		$this->register_function('icon', array(&$this, 'smartyIcon'));
		$this->register_function('help_topic', array(&$this, 'smartyHelpTopic'));
		$this->register_function('sort_heading', array(&$this, 'smartySortHeading'));
		$this->register_function('sort_search', array(&$this, 'smartySortSearch'));
		$this->register_function('get_debug_info', array(&$this, 'smartyGetDebugInfo'));
		$this->register_function('assign_mailto', array(&$this, 'smartyAssignMailto'));
		$this->register_function('display_template', array(&$this, 'smartyDisplayTemplate'));
		$this->register_modifier('truncate', array(&$this, 'smartyTruncate'));
		// JS UI components
		$this->register_function('modal', array(&$this, 'smartyModal'));
		$this->register_function('confirm', array(&$this, 'smartyConfirm'));
		$this->register_function('ajax_upload', array(&$this, 'smartyAjaxUpload'));
		$this->register_function('init_tabs', array(&$this, 'smartyInitTabs'));
		$this->register_function('modal_title', array(&$this, 'smartyModalTitle'));

		// register the resource name "core"
		$this->register_resource("core", array(array(&$this, 'smartyResourceCoreGetTemplate'),
											array(&$this, 'smartyResourceCoreGetTimestamp'),
											array(&$this, 'smartyResourceCoreGetSecure'),
											array(&$this, 'smartyResourceCoreGetTrusted')));

		$this->register_function('url', array(&$this, 'smartyUrl'));
		// ajax load into a div
		$this->register_function('load_url_in_div', array(&$this, 'smartyLoadUrlInDiv'));

		if (!defined('SESSION_DISABLE_INIT')) {
			/**
			 * Kludge to make sure no code that tries to connect to
			 * the database is executed (e.g., when loading
			 * installer pages).
			 */
			$this->assign('isUserLoggedIn', Validation::isLoggedIn());

			$application =& PKPApplication::getApplication();
			$currentVersion =& $application->getCurrentVersion();
			$this->assign('currentVersionString', $currentVersion->getVersionString());

			$this->assign('itemsPerPage', Config::getVar('interface', 'items_per_page'));
			$this->assign('numPageLinks', Config::getVar('interface', 'page_links'));

			$user =& $request->getUser();
			if ($user) {
				$notificationDao =& DAORegistry::getDAO('NotificationDAO');
				$notifications =& $notificationDao->getNotificationsByUserId($user->getId(), NOTIFICATION_LEVEL_TRIVIAL);
				$notificationsArray =& $notifications->toArray();
				unset($notifications);
				foreach ($notificationsArray as $notification) {
					$notificationDao->deleteNotificationById($notification->getId());
				}
				$this->assign('systemNotifications', $notificationsArray);
			}
		}

		$this->initialized = false;
	}

	/**
	 * Override the Smarty {include ...} function to allow hooks to be
	 * called.
	 */
	function _smarty_include($params) {
		if (!HookRegistry::call('TemplateManager::include', array(&$this, &$params))) {
			return parent::_smarty_include($params);
		}
		return false;
	}

	/**
	 * Flag the page as cacheable (or not).
	 * @param $cacheability boolean optional
	 */
	function setCacheability($cacheability = CACHEABILITY_PUBLIC) {
		$this->cacheability = $cacheability;
	}

	/**
	 * Initialize the template.
	 */
	function initialize() {
		// This code cannot be called in the constructor because of
		// reference problems, i.e. callers that need getManager fail.

		// Load enabled block plugins.
		$plugins =& PluginRegistry::loadCategory('blocks', true);

		$this->initialized = true;
	}

	/**
	 * Add a page-specific style sheet.
	 * @param $url string the URL to the style sheet
	 */
	function addStyleSheet($url) {
		array_push($this->styleSheets, $url);
	}

	/**
	 * Add a page-specific script.
	 * @param $url string the URL to be included
	 */
	function addJavaScript($url) {
		array_push($this->javaScripts, $url);
	}

	/**
	 * @see Smarty::fetch()
	 */
    function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false) {
    	if (!$this->initialized) {
			$this->initialize();
		}

    	// Add additional java script URLs
		if (!empty($this->javaScripts)) {
			$baseUrl = $this->get_template_vars('baseUrl');
			$scriptOpen = '	<script language="javascript" type="text/javascript" src="';
			$scriptClose = '"></script>';
			$javaScript = '';
			foreach ($this->javaScripts as $script) {
				$javaScript .= $scriptOpen . $baseUrl . '/' . $script . $scriptClose . "\n";
			}

			$additionalHeadData = $this->get_template_vars('additionalHeadData');
			$this->assign('additionalHeadData', $additionalHeadData."\n".$javaScript);

			// Empty the java scripts array so that we don't include
			// the same scripts twice in case the template manager is called again.
			$this->javaScripts = array();
		}
		return parent::fetch($resource_name, $cache_id, $compile_id, $display);
	}

	/**
	 * Display the template.
	 */
	function display($template, $sendContentType = null, $hookName = null, $display = true) {
		// Set the defaults
		// N.B: This was moved from method signature to allow calls such as: ->display($template, null, null, false)
		if ( is_null($sendContentType) ) {
			$sendContentType = 'text/html';
		}
		if ( is_null($hookName) ) {
			$hookName = 'TemplateManager::display';
		}

		$charset = Config::getVar('i18n', 'client_charset');

		// Give any hooks registered against the TemplateManager
		// the opportunity to modify behavior; otherwise, display
		// the template as usual.

		$output = null;
		if (!HookRegistry::call($hookName, array(&$this, &$template, &$sendContentType, &$charset, &$output))) {
			// If this is the main display call, send headers.
			if ($hookName == 'TemplateManager::display') {
				// Explicitly set the character encoding
				// Required in case server is using Apache's
				// AddDefaultCharset directive (which can
				// prevent browser auto-detection of the proper
				// character set)
				header('Content-Type: ' . $sendContentType . '; charset=' . $charset);

				// Send caching info
				header('Cache-Control: ' . $this->cacheability);
			}

			// Actually display the template.
			return $this->fetch($template, null, null, $display);
		} else {
			// Display the results of the plugin.
			echo $output;
		}
	}

	/**
	 * Display templates from Smarty and allow hook overrides
	 *
	 * Smarty usage: {display_template template="name.tpl" hookname="My::Hook::Name"}
	 */
	function smartyDisplayTemplate($params, &$smarty) {
		$templateMgr =& TemplateManager::getManager();
		// This is basically a wrapper for display()
		if (isset($params['template'])) {
			$templateMgr->display($params['template'], "", $params['hookname']);
		}
	}


	/**
	 * Clear template compile and cache directories.
	 */
	function clearTemplateCache() {
		$this->clear_compiled_tpl();
		$this->clear_all_cache();
	}

	/**
	 * Return an instance of the template manager.
	 * @param $request PKPRequest FIXME: is optional for backwards compatibility only - make mandatory
	 * @return TemplateManager the template manager object
	 */
	function &getManager($request = null) {
		$instance =& Registry::get('templateManager', true, null);

		if ($instance === null) {
			$instance = new TemplateManager($request);
		}
		return $instance;
	}

	//
	// Custom Template Resource "Core"
	// The Core Template Resource is points to the fallback template_dir in the core
	//

	function smartyResourceCoreGetTemplate($template, &$templateSource, &$smarty) {
		$templateSource = file_get_contents($this->core_template_dir . DIRECTORY_SEPARATOR . $template);
		return true;
	}

	function smartyResourceCoreGetTimestamp($template, &$templateTimestamp, &$smarty) {
		$templateSource = $this->core_template_dir . DIRECTORY_SEPARATOR . $template;
		if (!file_exists($templateSource)) return false;
		$templateTimestamp = filemtime($templateSource);
		return true;
	}

	function smartyResourceCoreGetTecure($template, &$smarty) {
		return true;
	}

	function smartyResourceCoreGetTrusted($template, &$smarty) {}


	//
	// Custom template functions, modifiers, etc.
	//

	/**
	 * Smarty usage: {translate key="localization.key.name" [paramName="paramValue" ...]}
	 *
	 * Custom Smarty function for translating localization keys.
	 * Substitution works by replacing tokens like "{$foo}" with the value of the parameter named "foo" (if supplied).
	 * @params $params array associative array, must contain "key" parameter for string to translate plus zero or more named parameters for substitution.
	 * 	Translation variables can be specified also as an optional
	 * 	associative array named "params".
	 * @params $smarty Smarty
	 * @return string the localized string, including any parameter substitutions
	 */
	function smartyTranslate($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (!isset($params['key'])) return Locale::translate('');

			$key = $params['key'];
			unset($params['key']);
			if (isset($params['params']) && is_array($params['params'])) {
				$paramsArray = $params['params'];
				unset($params['params']);
				$params = array_merge($params, $paramsArray);
			}
			return Locale::translate($key, $params);
		}
	}

	/**
	 * Smarty usage: {assign_mailto var="varName" address="email@address.com" ...]}
	 *
	 * Generates a hex-encoded mailto address and assigns it to the variable name specified..
	 */
	function smartyAssignMailto($params, &$smarty) {
		if (isset($params['var']) && isset($params['address'])) {
			// Password encoding code taken from Smarty's mailto
			// function.
			$address = $params['address'];
			$address_encode = '';
			for ($x=0; $x < strlen($address); $x++) {
				if(preg_match('!\w!',$address[$x])) {
					$address_encode .= '%' . bin2hex($address[$x]);
				} else {
					$address_encode .= $address[$x];
				}
			}
			$text_encode = '';
			for ($x=0; $x < strlen($text); $x++) {
				$text_encode .= '&#x' . bin2hex($text[$x]).';';
			}

			$mailto = "&#109;&#97;&#105;&#108;&#116;&#111;&#58;";
			$smarty->assign($params['var'], $mailto . $address_encode);
		}
	}

	/**
	 * Smarty usage: {html_options_translate ...}
	 * For parameter usage, see http://smarty.php.net/manual/en/language.function.html.options.php
	 *
	 * Identical to Smarty's "html_options" function except option values are translated from i18n keys.
	 * @params $params array
	 * @params $smarty Smarty
	 */
	function smartyHtmlOptionsTranslate($params, &$smarty) {
		if (isset($params['options'])) {
			if (isset($params['translateValues'])) {
				// Translate values AND output
				$newOptions = array();
				foreach ($params['options'] as $k => $v) {
					$newOptions[Locale::translate($k)] = Locale::translate($v);
				}
				$params['options'] = $newOptions;
			} else {
				// Just translate output
				$params['options'] = array_map(array('Locale', 'translate'), $params['options']);
			}
		}

		if (isset($params['output'])) {
			$params['output'] = array_map(array('Locale', 'translate'), $params['output']);
		}

		if (isset($params['values']) && isset($params['translateValues'])) {
			$params['values'] = array_map(array('Locale', 'translate'), $params['values']);
		}

		require_once($this->_get_plugin_filepath('function','html_options'));
		return smarty_function_html_options($params, $smarty);
	}

	/**
	 * Iterator function for looping through objects extending the
	 * ItemIterator class.
	 * Parameters:
	 *  - from: Name of template variable containing iterator
	 *  - item: Name of template variable to receive each item
	 *  - key: (optional) Name of variable to receive index of current item
	 */
	function smartyIterate($params, $content, &$smarty, &$repeat) {
		$iterator =& $smarty->get_template_vars($params['from']);

		if (isset($params['key'])) {
			if (empty($content)) $smarty->assign($params['key'], 1);
			else $smarty->assign($params['key'], $smarty->get_template_vars($params['key'])+1);
		}

		// If the iterator is empty, we're finished.
		if (!$iterator || $iterator->eof()) {
			if (!$repeat) return $content;
			$repeat = false;
			return '';
		}

		$repeat = true;

		if (isset($params['key'])) {
			list($key, $value) = $iterator->nextWithKey();
			$smarty->assign_by_ref($params['item'], $value);
			$smarty->assign_by_ref($params['key'], $key);
		} else {
			$smarty->assign_by_ref($params['item'], $iterator->next());
		}
		return $content;
	}

	/**
	 * Smarty usage: {icon name="image name" alt="alternative name" url="url path"}
	 *
	 * Custom Smarty function for generating anchor tag with optional url
	 * @params $params array associative array, must contain "name" paramater to create image anchor tag
	 * @return string <a href="url"><img src="path to image/image name" ... /></a>
	 */
	function smartyIcon($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			$iconHtml = '';
			if (isset($params['name'])) {
				// build image tag with standarized size of 16x16
				$disabled = (isset($params['disabled']) && !empty($params['disabled']));
				if (!isset($params['path'])) $params['path'] = 'lib/pkp/templates/images/icons/';
				$iconHtml = '<img src="' . $smarty->get_template_vars('baseUrl') . '/' . $params['path'];
				$iconHtml .= $params['name'] . ($disabled ? '_disabled' : '') . '.gif" width="16" height="14" alt="';

				// if alt parameter specified use it, otherwise use localization version
				if (isset($params['alt'])) {
					$iconHtml .= $params['alt'];
				} else {
					$iconHtml .= Locale::translate('icon.'.$params['name'].'.alt');
				}
				$iconHtml .= '" ';

				// if onclick parameter specified use it
				if (isset($params['onclick'])) {
					$iconHtml .= 'onclick="' . $params['onclick'] . '" ';
				}


				$iconHtml .= '/>';

				// build anchor with url if specified as a parameter
				if (!$disabled && isset($params['url'])) {
					$iconHtml = '<a href="' . $params['url'] . '" class="icon">' . $iconHtml . '</a>';
				}
			}
			return $iconHtml;
		}
	}

	/**
	 * Display page information for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_info from=$myIterator}
	 */
	function smartyPageInfo($params, &$smarty) {
		$iterator = $params['iterator'];

		$itemsPerPage = $smarty->get_template_vars('itemsPerPage');
		if (!is_numeric($itemsPerPage)) $itemsPerPage=25;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		if ($pageCount<1) return '';

		$from = (($page - 1) * $itemsPerPage) + 1;
		$to = min($itemTotal, $page * $itemsPerPage);

		return Locale::translate('navigation.items', array(
			'from' => ($to===0?0:$from),
			'to' => $to,
			'total' => $itemTotal
		));
	}

	/**
	 * Flush the output buffer. This is useful in cases where Smarty templates
	 * are calling functions that take a while to execute so that they can display
	 * a progress indicator or a message stating that the operation may take a while.
	 */
	function smartyFlush($params, &$smarty) {
		$smarty->flush();
	}

	function flush() {
		while (ob_get_level()) {
			ob_end_flush();
		}
		flush();
	}

	/**
	 * Call hooks from a template.
	 */
	function smartyCallHook($params, &$smarty) {
		$output = null;
		HookRegistry::call($params['name'], array(&$params, &$smarty, &$output));
		return $output;
	}

	/**
	 * Get debugging information and assign it to the template.
	 */
	function smartyGetDebugInfo($params, &$smarty) {
		if (Config::getVar('debug', 'show_stats')) {
			$smarty->assign('enableDebugStats', true);

			// provide information from the PKPProfiler class
			$pkpProfiler =& Registry::get('system.debug.profiler');
			foreach ($pkpProfiler->getData() as $output => $value) {
				$smarty->assign($output, $value);
			}
			$smarty->assign('pqpCss', Request::getBaseUrl() . '/lib/pkp/lib/pqp/css/pQp.css');
			$smarty->assign('pqpTemplate', BASE_SYS_DIR . '/lib/pkp/lib/pqp/pqp.tpl');
		}
	}

	/**
	 * Generate a URL into a PKPApp.
	 * @param $params array
	 * @param $smarty object
	 * Available parameters:
	 * - router: which router to use
	 * - context
	 * - page
	 * - component
	 * - op
	 * - path (array)
	 * - anchor
	 * - escape (default to true unless otherwise specified)
	 */
	function smartyUrl($params, &$smarty) {
		if ( !isset($params['context']) ) {
			// Extract the variables named in $paramList, and remove them
			// from the params array. Variables remaining in params will be
			// passed along to Request::url as extra parameters.
			$context = array();
			$contextList = Application::getContextList();
			foreach ($contextList as $contextName) {
				if (isset($params[$contextName])) {
					$context[$contextName] = $params[$contextName];
					unset($params[$contextName]);
				} else {
					$context[$contextName] = null;
				}
			}
			$params['context'] = $context;
		}

		// Extract the variables named in $paramList, and remove them
		// from the params array. Variables remaining in params will be
		// passed along to Request::url as extra parameters.
		$paramList = array('router', 'context', 'page', 'component', 'op', 'path', 'anchor', 'escape');
		foreach ($paramList as $param) {
			if (isset($params[$param])) {
				$$param = $params[$param];
				unset($params[$param]);
			} else {
				$$param = null;
			}
		}

		// Set the default router
		$request =& PKPApplication::getRequest();
		if (is_null($router)) {
			if (is_a($request->getRouter(), 'PKPComponentRouter')) {
				$router = ROUTE_COMPONENT;
			} else {
				$router = ROUTE_PAGE;
			}
		}

		// Check the router
		$dispatcher =& PKPApplication::getDispatcher();
		$routerShortcuts = array_keys($dispatcher->getRouterNames());
		assert(in_array($router, $routerShortcuts));

		// Identify the handler
		switch($router) {
			case ROUTE_PAGE:
				$handler = $page;
				break;

			case ROUTE_COMPONENT:
				$handler = $component;
				break;

			default:
				// Unknown router type
				assert(false);
		}

		// Let the dispatcher create the url
		return $dispatcher->url($request, $router, $context, $handler, $op, $path, $params, $anchor, !isset($escape) || $escape);
	}

	function setProgressFunction($progressFunction) {
		Registry::set('progressFunctionCallback', $progressFunction);
	}

	function smartyCallProgressFunction($params, &$smarty) {
		$progressFunctionCallback =& Registry::get('progressFunctionCallback');
		if ($progressFunctionCallback) {
			call_user_func($progressFunctionCallback);
		}
	}

	function updateProgressBar($progress, $total) {
		static $lastPercent;
		$percent = round($progress * 100 / $total);
		if (!isset($lastPercent) || $lastPercent != $percent) {
			for($i=1; $i <= $percent-$lastPercent; $i++) {
				echo '<img src="' . Request::getBaseUrl() . '/templates/images/progbar.gif" width="5" height="15">';
			}
		}
		$lastPercent = $percent;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->flush();
	}

	/**
	 * Display page links for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_links
	 * 	name="nameMustMatchGetRangeInfoCall"
	 * 	iterator=$myIterator
	 *	additional_param=myAdditionalParameterValue
	 * }
	 */
	function smartyPageLinks($params, &$smarty) {
		$iterator = $params['iterator'];
		$name = $params['name'];
		if (isset($params['params']) && is_array($params['params'])) {
			$extraParams = $params['params'];
			unset($params['params']);
			$params = array_merge($params, $extraParams);
		}
		if (isset($params['anchor'])) {
			$anchor = $params['anchor'];
			unset($params['anchor']);
		} else {
			$anchor = null;
		}
		if (isset($params['all_extra'])) {
			$allExtra = ' ' . $params['all_extra'];
			unset($params['all_extra']);
		} else {
			$allExtra = '';
		}

		unset($params['iterator']);
		unset($params['name']);

		$numPageLinks = $smarty->get_template_vars('numPageLinks');
		if (!is_numeric($numPageLinks)) $numPageLinks=10;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		$pageBase = max($page - floor($numPageLinks / 2), 1);
		$paramName = $name . 'Page';

		if ($pageCount<=1) return '';

		$value = '';

		if ($page>1) {
			$params[$paramName] = 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&lt;&lt;</a>&nbsp;';
			$params[$paramName] = $page - 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<strong>$i</strong>&nbsp;";
			} else {
				$params[$paramName] = $i;
				$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$params[$paramName] = $page + 1;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&gt;</a>&nbsp;';
			$params[$paramName] = $pageCount;
			$value .= '<a href="' . Request::url(null, null, null, Request::getRequestedArgs(), $params, $anchor) . '"' . $allExtra . '>&gt;&gt;</a>&nbsp;';
		}

		return $value;
	}

	/**
	 * Convert the parameters of a function to an array.
	 */
	function smartyToArray() {
		return func_get_args();
	}

	/**
	 * Concatenate the parameters and return the result.
	 */
	function smartyConcat() {
		$args = func_get_args();
		return implode('', $args);
	}

	/**
	 * Convert a string to a numeric time.
	 */
	function smartyStrtotime($string) {
		return strtotime($string);
	}

	/**
	 * Get the value of a template variable.
	 */
	function smartyGetValue($name) {
		$templateMgr =& TemplateManager::getManager();
		return $templateMgr->get_template_vars($name);
	}

	/**
	 * Override the built-in smarty escape modifier to set the charset
	 * properly; also add the jsparam escaping method.
	 */
	function smartyEscape($string, $esc_type = 'html', $char_set = null) {
		if ($char_set === null) $char_set = LOCALE_ENCODING;
		switch ($esc_type) {
			case 'jsparam':
				// When including a value in a Javascript parameter,
				// quotes need to be specially handled on top of
				// the usual escaping, as Firefox (and probably others)
				// decodes &#039; as a quote before interpereting
				// the javascript.
				$value = smarty_modifier_escape($string, 'html', $char_set);
				return str_replace('&#039;', '\\\'', $value);
			default:
				return smarty_modifier_escape($string, $esc_type, $char_set);
		}
	}

	/**
	 * Override the built-in smarty truncate modifier to support mbstring and HTML tags
	 * text properly, if possible.
	 */
	function smartyTruncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false, $skip_tags = true) {
		if ($length == 0) return '';

		if (String::strlen($string) > $length) {
			$originalLength = String::strlen($string);
			if ($skip_tags) {
				if ($middle) {
					$tagsReverse = array();
					$this->_removeTags($string, $tagsReverse, true, $length);
				}
				$tags = array();
				$string = $this->_removeTags($string, $tags, false, $length);
			}
			$length -= min($length, String::strlen($etc));
			if (!$middle) {
				if(!$break_words) {
					$string = String::regexp_replace('/\s+?(\S+)?$/', '', String::substr($string, 0, $length+1));
				} else $string = String::substr($string, 0, $length+1);
				if ($skip_tags) $string = $this->_reinsertTags($string, $tags);
				return $this->_closeTags($string) . $etc;
			} else {
				$firstHalf = String::substr($string, 0, $length/2);
				$secondHalf = String::substr($string, -$length/2);

				if($break_words) {
					if($skip_tags) {
						$firstHalf = $this->_reinsertTags($firstHalf, $tags);
						$secondHalf = $this->reinsertTags($secondHalf, $tagsReverse, true);
						return $this->_closeTags($firstHalf) . $etc . $this->_closeTags($secondHalf, true);
					} else {
						return $firstHalf . $etc . $secondHalf;
					}
				} else {
					for($i=$length/2; $string[$i] != ' '; $i++) {
						$firstHalf = String::substr($string, 0, $i+1);
					}
					for($i=$length/2; String::substr($string, -$i, 1) != ' '; $i++) {
						$secondHalf = String::substr($string, -$i-1);
					}

					if ($skip_tags) {
						$firstHalf = $this->_reinsertTags($firstHalf, $tags);
						$secondHalf = $this->reinsertTags($secondHalf, $tagsReverse, strlen($string));
						return $this->_closeTags($firstHalf) . $etc . $this->_closeTags($secondHalf, true);
					} else {
						return $firstHalf . $etc . $secondHalf;
					}
				}
			}
		} else {
			return $string;
		}
	}

	/**
	 * Helper function: Remove XHTML tags and insert them into a global array along with their position
	 * @author Matt Crider
	 * @param string
	 * @param array
	 * @param boolean
	 * @param int
	 * @return string
	 */
	function _removeTags($string, &$tags, $reverse = false, $length) {
		if($reverse) {
			return $this->_removeTagsAuxReverse($string, 0, $tags, $length);
		} else {
			return $this->_removeTagsAux($string, 0, $tags, $length);
		}
	}

	/**
	 * Helper function: Recursive function called by _removeTags
	 * @author Matt Crider
	 * @param string
	 * @param int
	 * @param array
	 * @param int
	 * @return string
	 */
	function _removeTagsAux($string, $loc, &$tags, $length) {
		if(strlen($string) > 0 && $length > 0) {
			$length--;
			if(String::substr($string, 0, 1) == '<') {
				$closeBrack = String::strpos($string, '>')+1;
				if($closeBrack) {
					$tags[] = array(String::substr($string, 0, $closeBrack), $loc);
					return $this->_removeTagsAux(String::substr($string, $closeBrack), $loc+$closeBrack, $tags, $length);
				}
			}
			return String::substr($string, 0, 1) . $this->_removeTagsAux(String::substr($string, 1), $loc+1, $tags, $length);
		}
	}

	/**
	 * Helper function: Recursive function called by _removeTags
	 * Removes tags from the back of the string and keeps a record of their position from the back
	 * @author Matt Crider
	 * @param string
	 * @param int loc Keeps track of position from the back of original string
	 * @param array
	 * @param int
	 * @return string
	 */
	function _removeTagsAuxReverse($string, $loc, &$tags, $length) {
		$backLoc = String::strlen($string)-1;
		if($backLoc >= 0 && $length > 0) {
			$length--;
			if(String::substr($string, $backLoc, 1) == '>') {
				$tag = '>';
				$openBrack = 1;
				while (String::substr($string, $backLoc-$openBrack, 1) != '<') {
					$tag = String::substr($string, $backLoc-$openBrack, 1) . $tag;
					$openBrack++;
				}
				$tag = '<' . $tag;
				$openBrack++;

				$tags[] = array($tag, $loc);
				return $this->_removeTagsAuxReverse(String::substr($string, 0, -$openBrack), $loc+$openBrack, $tags, $length);
			}
			return $this->_removeTagsAuxReverse(String::substr($string, 0, -1), $loc+1, $tags, $length) . String::substr($string, $backLoc, 1);
		}
	}


	/**
	 * Helper function: Reinsert tags from the tag array into their original position in the string
	 * @author Matt Crider
	 * @param string
	 * @param array
	 * @param boolean Set to true to reinsert tags starting at the back of the string
	 * @return string
	 */
	function _reinsertTags($string, &$tags, $reverse = false) {
		if(empty($tags)) return $string;

		for($i = 0; $i < count($tags); $i++) {
			$length = String::strlen($string);
			if ($tags[$i][1] < String::strlen($string)) {
				if ($reverse) {
					if ($tags[$i][1] == 0) { // Cannot use -0 as the start index (its same as +0)
						$string = String::substr_replace($string, $tags[$i][0], $length, 0);
					} else {
						$string = String::substr_replace($string, $tags[$i][0], -$tags[$i][1], 0);
					}
				} else {
					$string = String::substr_replace($string, $tags[$i][0], $tags[$i][1], 0);
				}
			}
		}

		return $string;
	}

	/**
	 * Helper function: Closes all dangling XHTML tags in a string
	 * Modified from http://milianw.de/code-snippets/close-html-tags
	 *  by Milian Wolff <mail@milianw.de>
	 * @param string
	 * @return string
	 */
	function _closeTags($string, $open = false){
		// Put all opened tags into an array
		String::regexp_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $string, $result);
		$openedtags = $result[1];

		// Put all closed tags into an array
		String::regexp_match_all("#</([a-z]+)>#iU", $string, $result);
		$closedtags = $result[1];
		$len_opened = count($openedtags);
		$len_closed = count($closedtags);
		// All tags are closed
		if(count($closedtags) == $len_opened){
			return $string;
		}

		$openedtags = array_reverse($openedtags);
		$closedtags = array_reverse($closedtags);

		if ($open) {
			// Open tags
			for($i=0; $i < $len_closed; $i++) {
				if (!in_array($closedtags[$i],$openedtags)){
					$string = '<'.$closedtags[$i].'>' . $string;
				} else {
					unset($openedtags[array_search($closedtags[$i],$openedtags)]);
				}
			}
			return $string;
		} else {
			// Close tags
			for($i=0; $i < $len_opened; $i++) {
				if (!in_array($openedtags[$i],$closedtags)){
					$string .= '</'.$openedtags[$i].'>';
				} else {
					unset($closedtags[array_search($openedtags[$i],$closedtags)]);
				}
			}
			return $string;
		}
	}

	/**
	 * Split the supplied string by the supplied separator.
	 */
	function smartyExplode($string, $separator) {
		return explode($separator, $string);
	}

	/**
	 * Assign a value to a template variable.
	 */
	function smartyAssign($value, $varName, $passThru = false) {
		if (isset($varName)) {
			// NOTE: CANNOT use $this, as it's actually
			// a COPY of the real template manager for some PHPs!
			// FIXME: Track this bug down. (Smarty?)
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign($varName, $value);
		}
		if ($passThru) return $value;
	}

	/**
	 * Smarty usage: {sort_heading key="localization.key.name" sort="foo"}
	 *
	 * Custom Smarty function for creating heading links to sort tables by
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return string heading link to sort table by
	 */
	function smartySortHeading($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			$sortParams = Request::getQueryArray();
			isset($params['sort'])? ($sortParams['sort'] = $params['sort']) : null;
			$sortDirection = $smarty->get_template_vars('sortDirection');
			$sort = $smarty->get_template_vars('sort');

			// Invert sort direction
			if($params['sort'] == $sort) {
				if ($sortDirection == SORT_DIRECTION_ASC) {
					$sortParams['sortDirection'] = SORT_DIRECTION_DESC;
				} else {
					$sortParams['sortDirection'] = SORT_DIRECTION_ASC;
				}
			} else {
				$sortParams['sortDirection'] = SORT_DIRECTION_ASC;
			}

			$link = PKPRequest::url(null, null, null, Request::getRequestedArgs(), $sortParams, null, true);
			$text = isset($params['key']) ? Locale::translate($params['key']) : '';
			$style = (isset($sort) && isset($params['sort']) && ($sort == $params['sort'])) ? ' style="font-weight:bold"' : '';

			return "<a href=\"$link\"$style>$text</a>";
		}
	}

	/**
	 * Smarty usage: {sort_search key="localization.key.name" sort="foo"}
	 *
	 * Custom Smarty function for creating heading links to sort search-generated tables
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return string heading link to sort table by
	 */
	function smartySortSearch($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			$sort = $smarty->get_template_vars('sort');
			$sortDirection = $smarty->get_template_vars('sortDirection');

			// Invert sort direction
			if($params['sort'] == $sort) {
				if ($sortDirection == SORT_DIRECTION_ASC) {
					$direction = SORT_DIRECTION_DESC;
				} else {
					$direction = SORT_DIRECTION_ASC;
				}
			} else {
				$direction = SORT_DIRECTION_ASC;
			}

			$heading = isset($params['sort']) ? $params['sort'] : $sort;
			$text = isset($params['key']) ? Locale::translate($params['key']) : '';
			$style = (isset($sort) && isset($params['sort']) && ($sort == $params['sort'])) ? ' style="font-weight:bold"' : '';
			return "<a href=\"javascript:sortSearch('$heading','$direction')\"$style>$text</a>";
		}
	}

	/**
	 * Smarty usage: {load_url_in_div id="someHtmlId" url="http://the.url.to.be.loaded.into.the.grid"}
	 *
	 * Custom Smarty function for loading a URL via AJAX into a DIV
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return string of HTML/Javascript
	 */
	function smartyLoadUrlInDiv($params, &$smarty) {
		// Required Params
		if (!isset($params['url'])) {
			$smarty->trigger_error("URL parameter is missing from load_url_in_div");
		}
		if (!isset($params['id'])) {
			$smarty->trigger_error("id parameter is missing from load_url_in_div");
		}

		$this->assign('inDivUrl', $params['url']);
		$this->assign('inDivDivId', $params['id']);
		if (isset($params['class'])) $this->assign('inDivClass', $params['class']);

		if (isset($params['loadMessageId'])) {
			$loadMessageId = $params['loadMessageId'];
			unset($params['url'], $params['id'], $params['loadMessageId'], $params['class']);
			$this->assign('inDivLoadMessage', Locale::translate($loadMessageId, $params));
		} else {
			$this->assign('inDivLoadMessage', Locale::translate('common.loading'));
		}

		return $this->fetch('common/urlInDiv.tpl');
	}

	/**
	 * Smarty usage: {modal url=$dialogUrl actOnId="#gridName" button="#dialogButton"}
	 *
	 * Custom Smarty function for creating jQuery-based modals
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return string Call to modal function with specified parameters
	 */
	function smartyModal($params, &$smarty) {
		// Required Params
		if (!isset($params['url'])) {
			$smarty->trigger_error("URL parameter is missing from modal");
		} elseif (!isset($params['actOnId'])) {
			$smarty->trigger_error("actOnId parameter is missing from modal");
		} elseif (!isset($params['button'])) {
			$smarty->trigger_error("Button parameter is missing from modal");
		} else {
			$url = $params['url'];
			$actOnType = isset($params['actOnType'])?$params['actOnType']:'';
			$actOnId = $params['actOnId'];
			$button = $params['button'];
			$dialogTitle = isset($params['dialogTitle'])?$params['dialogTitle']: false;
		}

		// Translate modal submit/cancel buttons
		$submitButton = Locale::translate('common.ok');
		$cancelButton = Locale::translate('common.cancel');

		// Add the modal javascript to the header
		$dialogTitle = isset($dialogTitle) ? ", '$dialogTitle'" : "";
		$modalCode = "<script type='text/javascript'>
			var localizedButtons = ['$submitButton', '$cancelButton'];
			modal('$url', '$actOnType', '$actOnId', localizedButtons, '$button'$dialogTitle);
		</script>\n";

		return $modalCode;
	}


	/**
	 * Smarty usage: {confirm url=$dialogUrl dialogText="example.locale.key" button="#dialogButton"}
	 * Custom Smarty function for creating simple yes/no dialogs (or to just send an AJAX post)
	 * NB:  -Leave out 'url' parameter to just display a message
	 *		-Leave out 'dialogText' parameter to immediately submit an AJAX request
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return string Call to modal function with specified parameters
	 */
	function smartyConfirm($params, &$smarty) {
		// Required params
		if (!isset($params['button'])) {
			$smarty->trigger_error("Button parameter is missing from confirm");
		} else {
			$button = $params['button'];
		}

		// Non-required params
		$url = isset($params['url']) ? $params['url'] : null;
		$actOnType = isset($params['actOnType']) ? $params['actOnType'] : '';
		$actOnId = isset($params['actOnId'])?$params['actOnId']:'';

		if (isset($params['dialogText']))  {
			$showDialog = true;
			if(isset($params['translate']) && $params['translate'] == false) {
				$dialogText = $params['dialogText'];
			} else {
				$dialogText = Locale::translate($params['dialogText']);
			}
		} else {
			$showDialog = false;
		}

		if (!$showDialog && !$url) {
			$smarty->trigger_error("Both URL and dialogText parameters are missing from confirm");
		}

		// Translate modal submit/cancel buttons
		$submitButton = Locale::translate('common.ok');
		$cancelButton = Locale::translate('common.cancel');

		if ($showDialog) {
			$confirmCode = "<script type='text/javascript'>
			var localizedButtons = ['$submitButton', '$cancelButton'];
			modalConfirm('$url', '$actOnType', '$actOnId', '$dialogText', localizedButtons, '$button');
			</script>\n";
		} else {
			$confirmCode = "<script type='text/javascript'>
			buttonPost('$url', '$button');
			</script>";
		}

		return $confirmCode;
	}

	function smartyAjaxUpload($params, &$smarty) {
		// Required params
		if (!isset($params['form'])) {
			$smarty->trigger_error("Form parameter is missing from ajax upload");
		} else {
			$form = $params['form'];
		}

		// Required params
		if (!isset($params['url'])) {
			$smarty->trigger_error("URL parameter is missing from ajax upload");
		} else {
			$url = $params['url'];
		}
		return "<script type='text/javascript'>ajaxUpload('$url', '$form');</script>";
	}

	function smartyInitTabs($params, &$smarty) {
		// Required params
		if (!isset($params['id'])) {
			$smarty->trigger_error("Selector missing for tab initialization");
		} else {
			$id = $params['id'];
		}

		return "<script type='text/javascript'>$(function() {
		$('$id').tabs({
		    ajaxOptions: {
		        dataFilter: function(jsonData){
		        	var data = $.parseJSON(jsonData);
		        	if(data.status === true) {
			            return data.content;
		        	} else {
		        		alert(data.content);
		        	}
		        }
		    }});
	    });
	    </script>";
	}

	function smartyModalTitle($params, &$smarty) {
		// Required params
		// Id must be child of div that is next-sibling of title div
		if (!isset($params['id'])) {
			$smarty->trigger_error("Selector missing for title bar initialization");
		} else {
			$id = $params['id'];
		}

		// Non-required params
		$iconClass = isset($params['iconClass']) ? $params['iconClass'] : '';
		if(isset($params['iconClass'])) {
			$iconClass = $params['iconClass'];
			$iconHtml = "<span class='icon $iconClass' />";

		} else $iconHtml = "";

		if(isset($params['key'])) {
			$keyHtml = "<span class='text'>" . Locale::translate($params['key']) . "</span>";
		} elseif(isset($params['keyTranslated'])) {
			$keyHtml = "<span class='text'>" . $params['keyTranslated'] . "</span>";
		} else $keyHtml = "";


		if(isset($params['canClose'])) {
			$canClose = $params['canClose'];
			$canCloseHtml = "<a class='close ui-corner-all' href='#'><span class='ui-icon ui-icon-closethick'>close</span></a>";

		} else $canCloseHtml = "";

		return "<script type='text/javascript'>$(function() {
		$('$id').last().parent().prev('.ui-dialog-titlebar').remove();
		$('a.close').live('click', function() { $(this).parent().parent().dialog('close'); return false; });
		return false;
		});</script>
		<div class='modalTitleBar'>" .
			$iconHtml .
			$keyHtml .
			$canCloseHtml .
		"<span style='clear:both' /></div>";
	}

}

?>
