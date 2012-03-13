<?php

/**
 * @file classes/metadata/MetadataDataObjectAdapter.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataDataObjectAdapter
 * @ingroup metadata
 * @see DataObject
 * @see MetadataSchema
 * @see MetadataDescription
 *
 * @brief Class that injects/extracts a meta-data description
 *  into/from an application entity object (DataObject).
 */

import('lib.pkp.classes.filter.Filter');
import('lib.pkp.classes.metadata.MetadataDescription');

class MetadataDataObjectAdapter extends Filter {
	/** @var string fully qualified name of a meta-data schema class */
	var $_metadataSchemaName;

	/** @var MetadataSchema */
	var $_metadataSchema;

	/** @var string */
	var $_dataObjectName;

	/** @var string */
	var $_dataObjectClass;

	/** @var integer */
	var $_assocType;

	/** @var array */
	var $_metadataFieldNames;

	/** @var array */
	var $_supportedTransformations;

	/**
	 * Constructor
	 * @param $metadataSchemaName string a fully qualified class name
	 * @param $dataObjectName string a fully qualified class name
	 * @param $assocType integer
	 */
	function MetadataDataObjectAdapter($metadataSchemaName, $dataObjectName, $assocType) {
		assert(is_string($metadataSchemaName) && is_string($dataObjectName)
				&& is_integer($assocType));

		// Initialize the adapter
		$this->setDisplayName('Inject/Extract Metadata into/from a '.$dataObjectName);
		$this->_metadataSchemaName = $metadataSchemaName;
		$this->_dataObjectName = $dataObjectName;
		$this->_assocType = $assocType;
		parent::Filter();
	}

	//
	// Getters and setters
	//
	/**
	 * Get the fully qualified class name of
	 * the supported meta-data schema.
	 */
	function getMetadataSchemaName() {
		return $this->_metadataSchemaName;
	}

	/**
	 * Get the supported meta-data schema (lazy load)
	 * @return MetadataSchema
	 */
	function &getMetadataSchema() {
		// Lazy-load the meta-data schema if this has
		// not been done before.
		if (is_null($this->_metadataSchema)) {
			$this->_metadataSchema =& instantiate($this->getMetadataSchemaName(), 'MetadataSchema');
			assert(is_object($this->_metadataSchema));
		}
		return $this->_metadataSchema;
	}

	/**
	 * Convenience method that returns the
	 * meta-data name space.
	 * @return string
	 */
	function getMetadataNamespace() {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getNamespace();
	}

	/**
	 * Get the supported application entity (class) name
	 * @return string
	 */
	function getDataObjectName() {
		return $this->_dataObjectName;
	}

	/**
	 * Return the data object class name
	 * (without the package prefix)
	 *
	 * @return string
	 */
	function getDataObjectClass() {
		if (is_null($this->_dataObjectClass)) {
			$dataObjectNameParts = explode('.', $this->getDataObjectName());
			$this->_dataObjectClass = array_pop($dataObjectNameParts);
		}
		return $this->_dataObjectClass;
	}

	/**
	 * Get the association type corresponding to the data
	 * object type.
	 * @return integer
	 */
	function getAssocType() {
		return $this->_assocType;
	}

	//
	// Abstract template methods
	//
	/**
	 * Inject a MetadataDescription into a DataObject
	 * @param $metadataDescription MetadataDescription
	 * @param $dataObject DataObject
	 * @param $replace boolean whether to delete existing meta-data
	 * @return DataObject
	 */
	function &injectMetadataIntoDataObject(&$metadataDescription, &$dataObject, $replace) {
		// Must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Extract a MetadataDescription from a DataObject.
	 * @param $dataObject DataObject
	 * @return MetadataDescription
	 */
	function &extractMetadataFromDataObject(&$dataObject) {
		// Must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Return the additional field names introduced by the
	 * meta-data schema that need to be persisted in the
	 * ..._settings table corresponding to the DataObject
	 * which is supported by this adapter.
	 * NB: The field names must be prefixed with the meta-data
	 * schema namespace identifier.
	 * @param $translated boolean if true, return localized field
	 *  names, otherwise return additional field names.
	 * @return array an array of field names to be persisted.
	 */
	function getDataObjectMetadataFieldNames($translated = true) {
		// By default return all field names
		return $this->getMetadataFieldNames($translated);
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see getSupportedTransformations()
	 */
	function getSupportedTransformations() {
		if (is_null($this->_supportedTransformations)) {
			// Find the ASSOC_TYPE_* constant with the correct value.
			$definedConstants = array_keys(get_defined_constants());
			$assocTypeConstants = array_filter($definedConstants,
					create_function('$o', 'return (strpos($o, "ASSOC_TYPE_") === 0) && '
					.'(constant($o) === '.(string)$this->getAssocType().');'));
			assert(count($assocTypeConstants) == 1);

			// Extract the assoc type name.
			$assocTypeName = str_replace('ASSOC_TYPE_', '', array_pop($assocTypeConstants));

			// Construct the supported type definitions
			$metadataType = 'metadata::'.$this->getMetadataSchemaName().'('.$assocTypeName.')';
			$dataObjectType = 'class::'.$this->getDataObjectName();

			// Construct the supported transformations
			$this->_supportedTransformations = array(
				array($metadataType, $dataObjectType),
				array($dataObjectType, $metadataType)
			);
		}
		return $this->_supportedTransformations;
	}

	/**
	 * @see Filter::supports()
	 * @param $input mixed
	 * @param $output mixed
	 * @return boolean
	 */
	function supports(&$input, &$output) {
		// Check input tpye
		switch(true) {
			// Inject meta-data into an existing data object
			case is_array($input):
				// Check input type
				// We expect two array entries: a MetadataDescription and a target data object.
				if (count($input) != 3) return false;
				$metadataDescription =& $input[0];
				if (!is_a($metadataDescription, 'MetadataDescription')) return false;

				$dataObject =& $input[1];
				if (!is_a($dataObject, $this->getDataObjectClass())) return false;

				$replace = $input[2];
				if (!is_bool($replace)) return false;

				// Check the the meta-data description compliance
				if (!$this->_complies($metadataDescription)) return false;
				break;

			// Inject meta-data into a new data object
			case is_a($input, 'MetadataDescription'):
				// We just need to check the meta-data description compliance.
				if (!$this->_complies($input)) return false;
				break;

			// Create a new meta-data description from a data object
			case is_a($input, $this->getDataObjectClass()):
				break;

			default:
				// A non-supported data-type
				return false;
		}

		// Check output type
		if (is_null($output)) return true;
		switch(true) {
			case is_array($input):
			case is_a($input, 'MetadataDescription'):
				// We expect an application object (DataObject)
				return is_a($output, $this->getDataObjectClass());

			case is_a($input, $this->getDataObjectClass()):
				if (!is_a($output, 'MetadataDescription')) return false;

				// Check whether the the output
				// complies with the supported schema
				return $this->_complies($output);

			default:
				// The adapter mode must always be defined
				// when calling supports().
				assert(false);
		}
	}

	/**
	 * Convert a MetadataDescription to an application
	 * object or vice versa.
	 * @see Filter::process()
	 * @param $input mixed either a MetadataDescription or an application object
	 * @return mixed either a MetadataDescription or an application object
	 */
	function &process(&$input) {
		// Set the adapter mode and convert the input.
		switch (true) {
			case is_array($input):
				$output =& $this->injectMetadataIntoDataObject($input[0], $input[1], $input[2]);
				break;

			case is_a($input, 'MetadataDescription'):
				$nullVar = null;
				$output =& $this->injectMetadataIntoDataObject($input, $nullVar, false);
				break;

			case is_a($input, $this->getDataObjectClass()):
				$output =& $this->extractMetadataFromDataObject($input);
				break;

			default:
				// Input should be validated by now.
				assert(false);
		}

		return $output;
	}


	//
	// Protected helper methods
	//
	/**
	 * Instantiate a meta-data description that conforms to the
	 * settings of this adapter.
	 * @return MetadataDescription
	 */
	function &instantiateMetadataDescription() {
		$metadataDescription = new MetadataDescription($this->getMetadataSchemaName(), $this->getAssocType());
		return $metadataDescription;
	}

	/**
	 * Return all field names introduced by the
	 * meta-data schema that might have to be persisted.
	 * @param $translated boolean if true, return localized field
	 *  names, otherwise return additional field names.
	 * @return array an array of field names to be persisted.
	 */
	function getMetadataFieldNames($translated = true) {
		// Do we need to build the field name cache first?
		if (is_null($this->_metadataFieldNames)) {
			// Initialize the cache array
			$this->_metadataFieldNames = array();

			// Retrieve all properties and add
			// their names to the cache
			$metadataSchema =& $this->getMetadataSchema();
			$metadataSchemaNamespace = $metadataSchema->getNamespace();
			$properties =& $metadataSchema->getProperties();
			foreach($properties as $property) {
				$propertyAssocTypes = $property->getAssocTypes();
				if (in_array($this->_assocType, $propertyAssocTypes)) {
					// Separate translated and non-translated property names
					// and add the name space so that field names are unique
					// across various meta-data schemas.
					$this->_metadataFieldNames[$property->getTranslated()][] = $metadataSchemaNamespace.':'.$property->getName();
				}
			}
		}

		// Return the field names
		return $this->_metadataFieldNames[$translated];
	}

	/**
	 * Set several localized statements in a meta-data schema.
	 * @param $metadataDescription MetadataDescription
	 * @param $propertyName string
	 * @param $localizedValues array (keys: locale, values: localized values)
	 */
	function addLocalizedStatements(&$metadataDescription, $propertyName, $localizedValues) {
		if (is_array($localizedValues)) {
			foreach ($localizedValues as $locale => $values) {
				// Handle cardinality "many" and "one" in the same way.
				if (is_scalar($values)) $values = array($values);
				foreach($values as $value) {
					$metadataDescription->addStatement($propertyName, $value, $locale);
					unset($value);
				}
			}
		}
	}

	/**
	 * Directly inject all fields that are not mapped to the
	 * data object into the data object's data array for
	 * automatic persistence by the meta-data framework.
	 * @param $metadataDescription MetadataDescription
	 * @param $dataObject DataObject
	 */
	function injectUnmappedDataObjectMetadataFields(&$metadataDescription, &$dataObject) {
		// Handle translated and non-translated statements separately.
		foreach(array(true, false) as $translated) {
			// Retrieve the unmapped fields.
			foreach($this->getDataObjectMetadataFieldNames($translated) as $unmappedProperty) {
				// Identify the corresponding property name.
				list($namespace, $propertyName) = explode(':', $unmappedProperty);

				// Find out whether we have a statement for this unmapped property.
				if ($metadataDescription->hasStatement($propertyName)) {
					// Add the unmapped statement directly to the
					// data object.
					if ($translated) {
						$dataObject->setData($unmappedProperty, $metadataDescription->getStatementTranslations($propertyName));
					} else {
						$dataObject->setData($unmappedProperty, $metadataDescription->getStatement($propertyName));
					}
				}
			}
		}
	}

	/**
	 * Directly extract all fields that are not mapped to the
	 * data object from the data object's data array.
	 * @param $dataObject DataObject
	 * @param $metadataDescription MetadataDescription
	 */
	function extractUnmappedDataObjectMetadataFields(&$dataObject, &$metadataDescription) {
		$metadataSchema =& $this->getMetadataSchema();
		$handledNamespace = $metadataSchema->getNamespace();

		// Handle translated and non-translated statements separately.
		foreach(array(true, false) as $translated) {
			// Retrieve the unmapped fields.
			foreach($this->getDataObjectMetadataFieldNames($translated) as $unmappedProperty) {
				// Find out whether we have a statement for this unmapped property.
				if ($dataObject->hasData($unmappedProperty)) {
					// Identify the corresponding property name and namespace.
					list($namespace, $propertyName) = explode(':', $unmappedProperty);

					// Only extract data if the namespace of the property
					// is the same as the one handled by this adapter and the
					// property is within the current description.
					if ($namespace == $handledNamespace && $metadataSchema->hasProperty($propertyName)) {
						// Add the unmapped statement to the metadata description.
						if ($translated) {
							$this->addLocalizedStatements($metadataDescription, $propertyName, $dataObject->getData($unmappedProperty));
						} else {
							$metadataDescription->addStatement($propertyName, $dataObject->getData($unmappedProperty));
						}
					}
				}
			}
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Check whether a given meta-data description complies with
	 * the meta-data schema configured for this adapter.
	 * @param $metadataDescription MetadataDescription
	 * @return boolean true if the given description complies, otherwise false
	 */
	function _complies($metadataDescription) {
		// Check that the description describes the correct resource
		if ($metadataDescription->getAssocType() != $this->_assocType) return false;

		// Check that the description complies with the correct schema
		$descriptionSchemaName =& $metadataDescription->getMetadataSchemaName();
		$supportedSchemaName =& $this->getMetadataSchemaName();
		if ($descriptionSchemaName != $supportedSchemaName) return false;

		// Compliance was successfully checked
		return true;
	}
}
?>