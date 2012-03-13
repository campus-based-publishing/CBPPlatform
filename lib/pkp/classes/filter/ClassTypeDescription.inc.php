<?php
/**
 * @file classes/filter/ClassTypeDescription.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ClassTypeDescription
 * @ingroup filter
 *
 * @brief A type description that validates objects by class type.
 *
 * Example type identifier: 'class::lib.pkp.classes.submission.Submission'
 */

import('lib.pkp.classes.filter.TypeDescription');
import('lib.pkp.classes.filter.TypeDescriptionFactory');

class ClassTypeDescription extends TypeDescription {
	/** @var string a valid class name */
	var $_className;

	/** @var string a valid package name */
	var $_packageName;

	/**
	 * Constructor
	 *
	 * @param $typeName string a fully qualified class name.
	 */
	function ClassTypeDescription($typeName) {
		parent::TypeDescription($typeName);
	}

	//
	// Setters and Getters
	//
	/**
	 * @see TypeDescription::getNamespace()
	 */
	function getNamespace() {
		return TYPE_DESCRIPTION_NAMESPACE_CLASS;
	}


	//
	// Implement abstract template methods from TypeDescription
	//
	/**
	 * @see TypeDescription::parseTypeName()
	 */
	function parseTypeName($typeName) {
		$splitName = $this->splitClassName($typeName);
		if ($splitName === false) return false;
		list($this->_packageName, $this->_className) = $splitName;

		// FIXME: Validate package and class to reduce the risk of
		// code injection, e.g. check that the package is within given limits/folders,
		// don't allow empty package parts, etc.

		return true;
	}

	/**
	 * @see TypeDescription::checkType()
	 */
	function checkType(&$object) {
		// We expect an object
		if (!is_object($object)) return false;

		// Check the object's class
		if (!is_a($object, $this->_className)) return false;

		return true;
	}


	//
	// Protected helper methods
	//
	/**
	 * Splits a fully qualified class name into
	 * a package and a class name string.
	 * @param $typeName the type name to be split up.
	 * @return array an array with the package name
	 *  as its first entry and the class name as its
	 *  second entry.
	 */
	function splitClassName($typeName) {
		// This should be a class - identify package and class name
		$typeNameParts = explode('.', $typeName);
		if (count($typeNameParts) == 1) {
			// No package given - invalid type description
			return false;
		}

		$className = array_pop($typeNameParts);
		$packageName = implode('.', $typeNameParts);
		return array($packageName, $className);
	}

	/**
	 * Instantiates the given class from the given
	 * package
	 * @param $packageName string
	 * @param $className string
	 * @return object
	 */
	function &instantiateClass($packageName, $className) {
		assert(!empty($packageName) && !empty($className));
		$object =& instantiate($packageName.'.'.$className, $className);
		return $object;
	}
}
?>