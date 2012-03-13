<?php

/**
 * @file classes/controllers/grid/DataObjectGridCellProvider.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectGridCellProvider
 * @ingroup controllers_grid
 *
 * @brief Base class for a cell provider that can retrieve labels from DataObjects
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class DataObjectGridCellProvider extends GridCellProvider {
	/** @var string the locale to be retrieved. */
	var $_locale = null;

	/**
	 * Constructor
	 */
	function DataObjectGridCellProvider() {
		parent::GridCellProvider();
	}

	//
	// Setters and Getters
	//
	/**
	 * Set the locale
	 * @param $locale string
	 */
	function setLocale($locale) {
		$this->_locale = $locale;
	}

	/**
	 * Get the locale
	 * @return string
	 */
	function getLocale() {
		return $this->_locale;
	}


	//
	// Template methods from GridCellProvider
	//
	/**
	 * This implementation assumes an element that is a
	 * DataObject. It will retrieve an element in the
	 * configured locale.
	 * @see GridCellProvider::getTemplateVarsFromRowColumn()
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn(&$row, $column) {
		$element =& $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'DataObject') && !empty($columnId));
		return array('label' => $element->getData($columnId, $this->getLocale()));
	}
}