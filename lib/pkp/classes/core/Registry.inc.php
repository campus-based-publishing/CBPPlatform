<?php

/**
 * @file classes/core/Registry.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Registry
 * @ingroup core
 *
 * @brief Maintains a static table of keyed references.
 * Used for storing/accessing single instance objects and values.
 *
 */

// $Id$


class Registry {
	/**
	 * Get a static reference to the registry data structure.
	 * @return array
	 */
	function &getRegistry() {
		static $registry = array();
		return $registry;
	}

	/**
	 * Get the value of an item in the registry.
	 * @param $key string
	 * @param $createIfEmpty boolean Whether or not to create the entry if none exists
	 * @param $createWithDefault mixed If $createIfEmpty, this value will be used as a default
	 * @return mixed
	 */
	function &get($key, $createIfEmpty = false, $createWithDefault = null) {
		$registry =& Registry::getRegistry();

		$result = null;
		if (isset($registry[$key])) $result =& $registry[$key];
		elseif ($createIfEmpty) {
			$result = $createWithDefault;
			Registry::set($key, $result);
		}
		return $result;
	}

	/**
	 * Set the value of an item in the registry.
	 * The item will be added if it does not already exist.
	 * @param $key string
	 * @param $value mixed
	 */
	function set($key, &$value) {
		$registry =& Registry::getRegistry();
		$registry[$key] =& $value;
	}

	/**
	 * Remove an item from the registry.
	 * @param $key string
	 */
	function delete($key) {
		$registry =& Registry::getRegistry();
		if (isset($registry[$key])) {
			unset($registry[$key]);
		}
	}

	function clear() {
		$registry =& Registry::getRegistry();
		foreach (array_keys($registry) as $key) {
			unset($registry[$key]);
		}
	}
}

?>
