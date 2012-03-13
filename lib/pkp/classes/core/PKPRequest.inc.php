<?php

/**
 * @file classes/core/PKPRequest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPRequest
 * @ingroup core
 *
 * @brief Class providing operations associated with HTTP requests.
 */


class PKPRequest {
	//
	// Internal state - please do not reference directly
	//
	/** @var PKPRouter router instance used to route this request */
	var $_router = null;
	/** @var Dispatcher dispatcher instance used to dispatch this request */
	var $_dispatcher = null;
	/** @var array the request variables cache (GET/POST) */
	var $_requestVars = null;
	/** @var string request base path */
	var $_basePath;
	/** @var string request path */
	var $_requestPath;
	/** @var boolean true if restful URLs are enabled in the config */
	var $_isRestfulUrlsEnabled;
	/** @var boolean true if path info is enabled for this server */
	var $_isPathInfoEnabled;
	/** @var string server host */
	var $_serverHost;
	/** @var string base url */
	var $_baseUrl;
	/** @var string request protocol */
	var $_protocol;


	/**
	 * get the router instance
	 * @return PKPRouter
	 */
	function &getRouter() {
		return $this->_router;
	}

	/**
	 * set the router instance
	 * @param $router instance PKPRouter
	 */
	function setRouter(&$router) {
		$this->_router =& $router;
	}

	/**
	 * Set the dispatcher
	 * @param $dispatcher Dispatcher
	 */
	function setDispatcher(&$dispatcher) {
		$this->_dispatcher =& $dispatcher;
	}

	/**
	 * Get the dispatcher
	 * @return Dispatcher
	 */
	function &getDispatcher() {
		return $this->_dispatcher;
	}


	/**
	 * Perform an HTTP redirect to an absolute or relative (to base system URL) URL.
	 * @param $url string (exclude protocol for local redirects)
	 */
	function redirectUrl($url) {
		PKPRequest::_checkThis();

		if (HookRegistry::call('Request::redirect', array(&$url))) {
			return;
		}

		header("Refresh: 0; url=$url");
		exit();
	}

	/**
	 * Redirect to the current URL, forcing the HTTPS protocol to be used.
	 */
	function redirectSSL() {
		$_this =& PKPRequest::_checkThis();

		$url = 'https://' . $_this->getServerHost() . $_this->getRequestPath();
		$queryString = $_this->getQueryString();
		if (!empty($queryString)) $url .= "?$queryString";
		$_this->redirectUrl($url);
	}

	/**
	 * Redirect to the current URL, forcing the HTTP protocol to be used.
	 */
	function redirectNonSSL() {
		$_this =& PKPRequest::_checkThis();

		$url = 'http://' . $_this->getServerHost() . $_this->getRequestPath();
		$queryString = $_this->getQueryString();
		if (!empty($queryString)) $url .= "?$queryString";
		$_this->redirectUrl($url);
	}

	/**
	 * Get the IF_MODIFIED_SINCE date (as a numerical timestamp) if available
	 * @return int
	 */
	function getIfModifiedSince() {
		if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) return null;
		return strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	}

	/**
	 * Get the base URL of the request (excluding script).
	 * @return string
	 */
	function getBaseUrl() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_baseUrl)) {
			$serverHost = $_this->getServerHost(null);
			if ($serverHost !== null) {
				// Auto-detection worked.
				$_this->_baseUrl = $_this->getProtocol() . '://' . $_this->getServerHost() . $_this->getBasePath();
			} else {
				// Auto-detection didn't work (e.g. this is a command-line call); use configuration param
				$_this->_baseUrl = Config::getVar('general', 'base_url');
			}
			HookRegistry::call('Request::getBaseUrl', array(&$_this->_baseUrl));
		}

		return $_this->_baseUrl;
	}

	/**
	 * Get the base path of the request (excluding trailing slash).
	 * @return string
	 */
	function getBasePath() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_basePath)) {
			$_this->_basePath = dirname($_SERVER['SCRIPT_NAME']);
			if ($_this->_basePath == '/' || $_this->_basePath == '\\') {
				$_this->_basePath = '';
			}
			HookRegistry::call('Request::getBasePath', array(&$_this->_basePath));
		}

		return $_this->_basePath;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getIndexUrl()
	 */
	function getIndexUrl() {
		static $indexUrl;

		$_this =& PKPRequest::_checkThis();
		if (!isset($indexUrl)) {
			$indexUrl = $_this->_delegateToRouter('getIndexUrl');

			// Call legacy hook
			HookRegistry::call('Request::getIndexUrl', array(&$indexUrl));
		}

		return $indexUrl;
	}

	/**
	 * Get the complete URL to this page, including parameters.
	 * @return string
	 */
	function getCompleteUrl() {
		$_this =& PKPRequest::_checkThis();

		static $completeUrl;

		if (!isset($completeUrl)) {
			$completeUrl = $_this->getRequestUrl();
			$queryString = $_this->getQueryString();
			if (!empty($queryString)) $completeUrl .= "?$queryString";
			HookRegistry::call('Request::getCompleteUrl', array(&$completeUrl));
		}

		return $completeUrl;
	}

	/**
	 * Get the complete URL of the request.
	 * @return string
	 */
	function getRequestUrl() {
		$_this =& PKPRequest::_checkThis();

		static $requestUrl;

		if (!isset($requestUrl)) {
			$requestUrl = $_this->getProtocol() . '://' . $_this->getServerHost() . $_this->getRequestPath();
			HookRegistry::call('Request::getRequestUrl', array(&$requestUrl));
		}

		return $requestUrl;
	}

	/**
	 * Get the complete set of URL parameters to the current request.
	 * @return string
	 */
	function getQueryString() {
		PKPRequest::_checkThis();

		static $queryString;

		if (!isset($queryString)) {
			$queryString = isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:'';
			HookRegistry::call('Request::getQueryString', array(&$queryString));
		}

		return $queryString;
	}

	/**
	 * Get the complete set of URL parameters to the current request as an associative array.
	 * @return array
	 */
	function getQueryArray() {
		$_this =& PKPRequest::_checkThis();

		$queryString = $_this->getQueryString();
		$queryArray = array();

		if (isset($queryString)) {
			parse_str($queryString, $queryArray);
		}

		return $queryArray;
	}

	/**
	 * Get the completed path of the request.
	 * @return string
	 */
	function getRequestPath() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_requestPath)) {
			if ($_this->isRestfulUrlsEnabled()) {
				$_this->_requestPath = $_this->getBasePath();
			} else {
				$_this->_requestPath = $_SERVER['SCRIPT_NAME'];
			}

			if ($_this->isPathInfoEnabled()) {
				$_this->_requestPath .= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
			}
			HookRegistry::call('Request::getRequestPath', array(&$_this->_requestPath));
		}
		return $_this->_requestPath;
	}

	/**
	 * Get the server hostname in the request. May optionally include the
	 * port number if non-standard.
	 * @return string
	 */
	function getServerHost($default = 'localhost') {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_serverHost)) {
			$_this->_serverHost = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST']
				: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
				: (isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME']
				: $default));
			HookRegistry::call('Request::getServerHost', array(&$_this->_serverHost));
		}
		return $_this->_serverHost;
	}

	/**
	 * Get the protocol used for the request (HTTP or HTTPS).
	 * @return string
	 */
	function getProtocol() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_protocol)) {
			$_this->_protocol = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') ? 'http' : 'https';
			HookRegistry::call('Request::getProtocol', array(&$_this->_protocol));
		}
		return $_this->_protocol;
	}

	/**
	 * Get the request method
	 * @return string
	 */
	function getRequestMethod() {
		PKPRequest::_checkThis();

		$requestMethod = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '');
		return $requestMethod;
	}

	/**
	 * Determine whether the request is a POST request
	 * @return boolean
	 */
	function isPost() {
		$_this =& PKPRequest::_checkThis();

		return ($_this->getRequestMethod() == 'POST');
	}

	/**
	 * Determine whether the request is a GET request
	 * @return boolean
	 */
	function isGet() {
		$_this =& PKPRequest::_checkThis();

		return ($_this->getRequestMethod() == 'GET');
	}

	/**
	 * Get the remote IP address of the current request.
	 * @return string
	 */
	function getRemoteAddr() {
		PKPRequest::_checkThis();

		static $ipaddr;
		if (!isset($ipaddr)) {
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
				preg_match_all('/([0-9.a-fA-F:]+)/', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
			} else if (isset($_SERVER['REMOTE_ADDR']) &&
				preg_match_all('/([0-9.a-fA-F:]+)/', $_SERVER['REMOTE_ADDR'], $matches)) {
			} else if (preg_match_all('/([0-9.a-fA-F:]+)/', getenv('REMOTE_ADDR'), $matches)) {
			} else {
				$ipaddr = '';
			}

			if (!isset($ipaddr)) {
				// If multiple addresses are listed, take the last. (Supports ipv6.)
				$ipaddr = $matches[0][count($matches[0])-1];
			}
			HookRegistry::call('Request::getRemoteAddr', array(&$ipaddr));
		}
		return $ipaddr;
	}

	/**
	 * Get the remote domain of the current request
	 * @return string
	 */
	function getRemoteDomain() {
		$_this =& PKPRequest::_checkThis();

		static $remoteDomain;
		if (!isset($remoteDomain)) {
			$remoteDomain = null;
			$remoteDomain = @getHostByAddr($_this->getRemoteAddr());
			HookRegistry::call('Request::getRemoteDomain', array(&$remoteDomain));
		}
		return $remoteDomain;
	}

	/**
	 * Get the user agent of the current request.
	 * @return string
	 */
	function getUserAgent() {
		PKPRequest::_checkThis();

		static $userAgent;
		if (!isset($userAgent)) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
			}
			if (!isset($userAgent) || empty($userAgent)) {
				$userAgent = getenv('HTTP_USER_AGENT');
			}
			if (!isset($userAgent) || $userAgent == false) {
				$userAgent = '';
			}
			HookRegistry::call('Request::getUserAgent', array(&$userAgent));
		}
		return $userAgent;
	}

	/**
	 * Determine whether a user agent is a bot or not using an external
	 * list of regular expressions.
	 */
	function isBot() {
		$_this =& PKPRequest::_checkThis();

		static $isBot;
		if (!isset($isBot)) {
			$userAgent = $_this->getUserAgent();
			$isBot = false;
			$userAgentsFile = Config::getVar('general', 'registry_dir') . DIRECTORY_SEPARATOR . 'botAgents.txt';
			$regexps = array_filter(file($userAgentsFile), create_function('&$a', 'return ($a = trim($a)) && !empty($a) && $a[0] != \'#\';'));
			foreach ($regexps as $regexp) {
				if (String::regexp_match($regexp, $userAgent)) {
					$isBot = true;
					return $isBot;
				}
			}
		}
		return $isBot;
	}

	/**
	 * Return true if PATH_INFO is enabled.
	 */
	function isPathInfoEnabled() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_isPathInfoEnabled)) {
			$_this->_isPathInfoEnabled = Config::getVar('general', 'disable_path_info')?false:true;
		}
		return $_this->_isPathInfoEnabled;
	}

	/**
	 * Return true if RESTFUL_URLS is enabled.
	 */
	function isRestfulUrlsEnabled() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_isRestfulUrlsEnabled)) {
			$_this->_isRestfulUrlsEnabled = Config::getVar('general', 'restful_urls')?true:false;
		}
		return $_this->_isRestfulUrlsEnabled;
	}

	/**
	 * Get site data.
	 * @return Site
	 */
	function &getSite() {
		PKPRequest::_checkThis();

		$site =& Registry::get('site', true, null);
		if ($site === null) {
			$siteDao =& DAORegistry::getDAO('SiteDAO');
			$site =& $siteDao->getSite();
			// PHP bug? This is needed for some reason or extra queries results.
			Registry::set('site', $site);
		}

		return $site;
	}

	/**
	 * Get the user session associated with the current request.
	 * @return Session
	 */
	function &getSession() {
		PKPRequest::_checkThis();

		$session =& Registry::get('session', true, null);

		if ($session === null) {
			$sessionManager =& SessionManager::getManager();
			$session = $sessionManager->getUserSession();
		}

		return $session;
	}

	/**
	 * Get the user associated with the current request.
	 * @return User
	 */
	function &getUser() {
		PKPRequest::_checkThis();

		$user =& Registry::get('user', true, null);
		if ($user === null) {
			$sessionManager =& SessionManager::getManager();
			$session =& $sessionManager->getUserSession();
			$user =& $session->getUser();
		}

		return $user;
	}

	/**
	 * Get the value of a GET/POST variable.
	 * @return mixed
	 */
	function getUserVar($key) {
		$_this =& PKPRequest::_checkThis();

		// Get all vars (already cleaned)
		$vars =& $_this->getUserVars();

		if (isset($vars[$key])) {
			return $vars[$key];
		} else {
			return null;
		}
	}

	/**
	 * Get all GET/POST variables as an array
	 * @return array
	 */
	function &getUserVars() {
		$_this =& PKPRequest::_checkThis();

		if (!isset($_this->_requestVars)) {
			$_this->_requestVars = array_merge($_GET, $_POST);
			$_this->cleanUserVar($_this->_requestVars);
		}

		return $_this->_requestVars;
	}

	/**
	 * Get the value of a GET/POST variable generated using the Smarty
	 * html_select_date and/or html_select_time function.
	 * @param $prefix string
	 * @param $defaultDay int
	 * @param $defaultMonth int
	 * @param $defaultYear int
	 * @param $defaultHour int
	 * @param $defaultMinute int
	 * @param $defaultSecond int
	 * @return Date
	 */
	function getUserDateVar($prefix, $defaultDay = null, $defaultMonth = null, $defaultYear = null, $defaultHour = 0, $defaultMinute = 0, $defaultSecond = 0) {
		$_this =& PKPRequest::_checkThis();

		$monthPart = $_this->getUserVar($prefix . 'Month');
		$dayPart = $_this->getUserVar($prefix . 'Day');
		$yearPart = $_this->getUserVar($prefix . 'Year');
		$hourPart = $_this->getUserVar($prefix . 'Hour');
		$minutePart = $_this->getUserVar($prefix . 'Minute');
		$secondPart = $_this->getUserVar($prefix . 'Second');

		switch ($_this->getUserVar($prefix . 'Meridian')) {
			case 'pm':
				if (is_numeric($hourPart) && $hourPart != 12) $hourPart += 12;
				break;
			case 'am':
			default:
				// Do nothing.
				break;
		}

		if (empty($dayPart)) $dayPart = $defaultDay;
		if (empty($monthPart)) $monthPart = $defaultMonth;
		if (empty($yearPart)) $yearPart = $defaultYear;
		if (empty($hourPart)) $hourPart = $defaultHour;
		if (empty($minutePart)) $minutePart = $defaultMinute;
		if (empty($secondPart)) $secondPart = $defaultSecond;

		if (empty($monthPart) || empty($dayPart) || empty($yearPart)) return null;
		return mktime($hourPart, $minutePart, $secondPart, $monthPart, $dayPart, $yearPart);
	}

	/**
	 * Sanitize a user-submitted variable (i.e., GET/POST/Cookie variable).
	 * Strips slashes if necessary, then sanitizes variable as per Core::cleanVar().
	 * @param $var mixed
	 */
	function cleanUserVar(&$var) {
		$_this =& PKPRequest::_checkThis();

		if (isset($var) && is_array($var)) {
			foreach ($var as $key => $value) {
				$_this->cleanUserVar($var[$key]);
			}
		} else if (isset($var)) {
			$var = Core::cleanVar(get_magic_quotes_gpc() ? stripslashes($var) : $var);

		} else {
			return null;
		}
	}

	/**
	 * Get the value of a cookie variable.
	 * @return mixed
	 */
	function getCookieVar($key) {
		$_this =& PKPRequest::_checkThis();

		if (isset($_COOKIE[$key])) {
			$value = $_COOKIE[$key];
			$_this->cleanUserVar($value);
			return $value;
		} else {
			return null;
		}
	}

	/**
	 * Set a cookie variable.
	 * @param $key string
	 * @param $value mixed
	 */
	function setCookieVar($key, $value) {
		$_this =& PKPRequest::_checkThis();

		setcookie($key, $value, 0, $_this->getBasePath());
		$_COOKIE[$key] = $value;
	}

	/**
	 * Redirect to the specified page within a PKP Application.
	 * Shorthand for a common call to $request->redirect($dispatcher->url($request, ROUTE_PAGE, ...)).
	 * @param $context Array The optional contextual paths
	 * @param $page string The name of the op to redirect to.
	 * @param $op string optional The name of the op to redirect to.
	 * @param $path mixed string or array containing path info for redirect.
	 * @param $params array Map of name => value pairs for additional parameters
	 * @param $anchor string Name of desired anchor on the target page
	 */
	function redirect($context = null, $page = null, $op = null, $path = null, $params = null, $anchor = null) {
		$_this =& PKPRequest::_checkThis();
		$dispatcher =& $_this->getDispatcher();
		$_this->redirectUrl($dispatcher->url($_this, ROUTE_PAGE, $context, $page, $op, $path, $params, $anchor));
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContext()
	 */
	function &getContext() {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('getContext');
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedContextPath()
	 */
	function getRequestedContextPath($contextLevel = null) {
		$_this =& PKPRequest::_checkThis();

		// Emulate the old behavior of getRequestedContextPath for
		// backwards compatibility.
		if (is_null($contextLevel)) {
			return $_this->_delegateToRouter('getRequestedContextPaths');
		} else {
			return array($_this->_delegateToRouter('getRequestedContextPath', $contextLevel));
		}
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedPage()
	 */
	function getRequestedPage() {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('getRequestedPage');
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedOp()
	 */
	function getRequestedOp() {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('getRequestedOp');
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedArgs()
	 */
	function getRequestedArgs() {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('getRequestedArgs');
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::url()
	 */
	function url($context = null, $page = null, $op = null, $path = null,
				$params = null, $anchor = null, $escape = false) {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('url', $context, $page, $op, $path,
				$params, $anchor, $escape);
	}

	/**
	 * This method exists to maintain backwards compatibility
	 * with static calls to PKPRequest.
	 *
	 * If it is called non-statically then it will simply
	 * return $this. Otherwise a global singleton instance
	 * from the registry will be returned instead.
	 *
	 * NB: This method is protected and may not be used by
	 * external classes. It should also only be used in legacy
	 * methods.
	 *
	 * @return PKPRequest
	 */
	function &_checkThis() {
		if (isset($this) && is_a($this, 'PKPRequest')) {
			return $this;
		} else {
			// This call is deprecated. We don't trigger a
			// deprecation error, though, as there are so
			// many instances of this error that it has a
			// performance impact and renders the error
			// log virtually useless when deprecation
			// warnings are switched on.
			// FIXME: Fix enough instances of this error so that
			// we can put a deprecation warning in here.
			$instance =& Registry::get('request');
			assert(!is_null($instance));
			return $instance;
		}
	}

	/**
	 * This method exists to maintain backwards compatibility
	 * with calls to methods that have been factored into the
	 * Router implementations.
	 *
	 * It delegates the call to the router and returns the result.
	 *
	 * NB: This method is protected and may not be used by
	 * external classes. It should also only be used in legacy
	 * methods.
	 *
	 * @return mixed depends on the called method
	 */
	function &_delegateToRouter($method) {
		// This call is deprecated. We don't trigger a
		// deprecation error, though, as there are so
		// many instances of this error that it has a
		// performance impact and renders the error
		// log virtually useless when deprecation
		// warnings are switched on.
		// FIXME: Fix enough instances of this error so that
		// we can put a deprecation warning in here.
		$_this =& PKPRequest::_checkThis();
		$router =& $_this->getRouter();

		// Construct the method call
		$callable = array($router, $method);

		// Get additional parameters but replace
		// the first parameter (currently the
		// method to be called) with the request
		// as all router methods required the request
		// as their first parameter.
		$parameters = func_get_args();
		$parameters[0] =& $_this;

		$returner = call_user_func_array($callable, $parameters);
		return $returner;
	}
}

?>
