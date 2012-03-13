<?php

/**
 * @file ReadingToolsBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReadingToolsBlockPlugin
 * @ingroup plugins_blocks_reading_tools
 *
 * @brief Class for reading tools block plugin
 */

// $Id$


import('lib.pkp.classes.plugins.BlockPlugin');

class ReadingToolsBlockPlugin extends BlockPlugin {
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.readingTools.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.readingTools.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}
}

?>
