<?php

/**
 * @file PolishedThemePlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PolishedThemePlugin
 * @ingroup plugins_themes_polished
 *
 * @brief "Polished" theme plugin
 */

// $Id: PolishedThemePlugin.inc.php,v 1.6 2008/07/01 01:16:14 asmecher Exp $


import('classes.plugins.ThemePlugin');

class PolishedThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'PolishedThemePlugin';
	}

	function getDisplayName() {
		return 'Polished Theme';
	}

	function getDescription() {
		return 'Dark layout';
	}

	function getStylesheetFilename() {
		return 'polished.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
