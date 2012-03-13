<?php

/**
 * @file classes/controllers/grid/GridColumn.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridColumn
 * @ingroup controllers_grid
 *
 * @brief Represents a column within a grid. It is used to configure the way
 *  cells within a column are displayed (cell provider) and can also be used
 *  to configure a editing strategy (not yet implemented). Contains all column-
 *  specific configuration (e.g. column title).
 */

// $Id$

class GridColumn {
	/** @var string the column id */
	var $_id;

	/** @var string the column title i18n key */
	var $_title;

	/** @var string the column title (translated) */
	var $_titleTranslated;

	/**
	 * @var array flags that can be set by the handler to trigger layout
	 *  options in the template.
	 */
	var $_flags;

	/** @var string the controller template for the cells in this column */
	var $_template;

	/** @var GridCellProvider a cell provider for cells in this column */
	var $_cellProvider;

	/**
	 * Constructor
	 */
	function GridColumn($id = '', $title = null, $titleTranslated = null,
		$template = 'controllers/grid/gridCell.tpl', $cellProvider = null, $flags = array()) {
		$this->_id = $id;
		$this->_title = $title;
		$this->_titleTranslated = $titleTranslated;
		$this->_template = $template;
		$this->_cellProvider =& $cellProvider;
		$this->_flags = $flags;
	}

	//
	// Setters/Getters
	//
	/**
	 * Get the column id
	 * @return string
	 */
	function getId() {
		return $this->_id;
	}

	/**
	 * Set the column id
	 * @param $id string
	 */
	function setId($id) {
		$this->_id = $id;
	}


	/**
	 * Get the column title
	 * @return string
	 */
	function getTitle() {
		return $this->_title;
	}

	/**
	 * Set the column title (already translated)
	 * @param $title string
	 */
	function setTitle($title) {
		$this->_title = $title;
	}

	/**
	 * Set the column title (already translated)
	 * @param $title string
	 */
	function setTitleTranslated($titleTranslated) {
		$this->_titleTranslated = $titleTranslated;
	}

	/**
	 * Get the translated column title
	 * @return string
	 */
	function getLocalizedTitle() {
		if ( $this->_titleTranslated ) return $this->_titleTranslated;
		return Locale::translate($this->_title);;
	}

	/**
	 * Get all layout flags
	 * @return array
	 */
	function getFlags() {
		return $this->_flags;
	}

	/**
	 * Get a single layout flags
	 * @param $flag string
	 * @return mixed
	 */
	function getFlag($flag) {
		assert(isset($this->flags[$flag]));
		return $this->_flags[$flag];
	}

	/**
	 * Check whether a flag is set to true
	 * @param $flag string
	 * @return boolean
	 */
	function hasFlag($flag) {
		if (!isset($this->_flags[$flag])) return false;
		return (boolean)$this->_flags[$flag];
	}

	/**
	 * Add a flag
	 * @param $flag string
	 * @param $value mixed
	 */
	function addFlag($flag, $value) {
		$this->_flags[$flag] = $value;
	}

	/**
	 * get the column's cell template
	 * @return string
	 */
	function getTemplate() {
		return $this->_template;
	}

	/**
	 * set the column's cell template
	 * @param $template string
	 */
	function setTemplate($template) {
		$this->_template = $template;
	}

	/**
	 * Get the cell provider
	 * @return GridCellProvider
	 */
	function &getCellProvider() {
		if (is_null($this->_cellProvider)) {
			// provide a sensible default cell provider
			import('lib.pkp.classes.controllers.grid.ArrayGridCellProvider');
			$cellProvider =& new ArrayGridCellProvider();
			$this->setCellProvider($cellProvider);
		}
		return $this->_cellProvider;
	}

	/**
	 * Set the cell provider
	 * @param $cellProvider GridCellProvider
	 */
	function setCellProvider(&$cellProvider) {
		$this->_cellProvider =& $cellProvider;
	}
}