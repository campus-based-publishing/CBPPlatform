<?php

/**
 * @file classes/metadata/CrosswalkFilter.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrosswalkFilter
 * @ingroup metadata
 * @see MetadataDescription
 *
 * @brief Class that provides methods to convert one type of
 *  meta-data description into another. This is an abstract
 *  class that must be sub-classed by specific cross-walk
 *  implementations.
 */

import('lib.pkp.classes.filter.Filter');

class CrosswalkFilter extends Filter {
	/**
	 * Constructor
	 * @param $fromSchema string fully qualified class name of supported input meta-data schema
	 * @param $toSchema string fully qualified class name of supported output meta-data schema
	 */
	function CrosswalkFilter() {
		parent::Filter();
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getSupportedTransformation()
	 */
	function getSupportedTransformation($fromSchema, $toSchema) {
		// We allow any type of described subject. See MetadataTypeDescription
		// class doc for meta-data schema validation syntax used below.
		return array('metadata::'.$fromSchema.'(*)', 'metadata::'.$toSchema.'(*)');
	}
}
?>