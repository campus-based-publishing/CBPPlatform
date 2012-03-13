<?php
/**
 * @file classes/filter/Filter.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Filter
 * @ingroup filter
 *
 * @brief Class that provides the basic template for a filter. Filters are
 *  generic data processors that take in a well-specified data type
 *  and return another well-specified data type.
 *
 *  Filters enable us to re-use data transformations between applications.
 *  Generic filter implementations can sequence, (de-)multiplex or iterate
 *  over other filters. Thereby filters can be nested and combined in many
 *  different ways to form complex and easy-to-customize data processing
 *  networks or pipelines.
 *
 *  NB: This also means that filters only make sense if they accept and
 *  return standardized formats that are understood by other filters. Otherwise
 *  the extra implementation effort for a filter won't result in improved code
 *  re-use.
 *
 *  Objects from different applications (e.g. Papers and Articles) can first be
 *  transformed by an application specific filter into a common format and then
 *  be processed by application agnostic import/export filters or vice versa.
 *  Filters can be used to pre-process data before it is indexed for search.
 *  They also provide a framework to customize the processing applied in citation
 *  parsing and lookup (i.e. which parsers and lookup sources should be applied).
 *
 *  Filters can be used stand-alone outside PKP applications.
 *
 *  The following is a complete list of all use-cases that have been identified
 *  for filters:
 *  1) Decode/Encode
 *  * import/export: transform application objects (e.g. an Article object)
 *    into structured (rich) data formats (e.g. XML, OpenURL KEV, CSV) or
 *    vice versa.
 *  * parse: transform unstructured clob/blob data (e.g. a Word Document)
 *    into application objects (e.g. an Article plus Citation objects) or
 *    into structured data formats (e.g. XML).
 *  * render: transform application objects or structured clob/blob data into
 *    an unstructured document (e.g. PDF, HTML, Word Document).
 *
 *  2) Normalize
 *  * lookup: compare the data of a given entity (e.g. a bibliographic
 *    reference) with data from other sources (e.g. CrossRef) and use this
 *    to normalize data or improve data quality.
 *  * harvest: cleanse and normalize incoming meta-data
 *
 *  3) Map
 *  * cross-walk: transform one meta-data format into another. Meta-data
 *    can be represented as structured clob/blob data (e.g. XML) or as
 *    application objects (i.e. a MetadataRecord instance).
 *  * meta-data extraction: retrieve meta-data from OO entities
 *    (e.g. an Article) into a standardized meta-data record (e.g. NLM
 *    element-citation).
 *  * meta-data injection: inject data from a standardized meta-data
 *    record into application objects.
 *
 *  4) Convert documents
 *  * binary converters: wrap binary document converters (e.g. antidoc) in
 *    a well-defined and re-usable way.
 *
 *  5) Search
 *  * indexing: pre-process data (extract, tokenize, remove stopwords,
 *    stem) for indexing.
 *  * finding: pre-process queries (parse, tokenize, remove stopwords,
 *    stem) to access the index
 */

import('lib.pkp.classes.core.DataObject');
import('lib.pkp.classes.filter.TypeDescriptionFactory');

class Filter extends DataObject {
	/** @var TypeDescriptionFactory */
	var $_typeDescriptionFactory;

	/** @var TypeDescription */
	var $_inputType;

	/** @var TypeDescription */
	var $_outputType;

	/** @var mixed */
	var $_input;

	/** @var mixed */
	var $_output;

	/** @var array a list of FilterSetting objects */
	var $_settings = array();

	/** @var array a list of errors occurred while filtering */
	var $_errors = array();

	/**
	 * @var RuntimeEnvironment the installation requirements required to
	 * run this filter instance, false on initialization.
	 */
	var $_runtimeEnvironment = false;

	/**
	 * Constructor
	 *
	 * NB: Filters should always either have no constructor
	 * arguments or only optional constructor arguments.
	 * All optional constructor arguments must also be accessible
	 * via setters. Filter parameters must be stored as data
	 * in the underlying DataObject.
	 *
	 * This is necessary as the FilterDAO does not support
	 * constructor configuration. Filter parameters will be
	 * configured via DataObject::setData(). Only parameters
	 * that are available in the DataObject will be persisted.
	 */
	function Filter() {
		// If we only support one transformation then we can
		// set it immediately. Otherwise this has to be done by
		// the user.
		$supportedTransformations = $this->getSupportedTransformations();
		if (count($supportedTransformations) == 1) {
			$supportedTransformation = $supportedTransformations[0];
			$this->setTransformationType($supportedTransformation[0], $supportedTransformation[1]);
		}

		// Initialize the parent filter id.
		$this->setParentFilterId(0);
		$this->setIsTemplate(false);
	}

	//
	// Setters and Getters
	//
	/**
	 * Set the input/output type of this filter instance.
	 *
	 * @param $inputType TypeDescription|string
	 * @param $outputType TypeDescription|string
	 *
	 * @see TypeDescriptionFactory::instantiateTypeDescription() for more details
	 *
	 * NB: the input/output type combination must be one of those
	 * returned by getSupportedTransformations().
	 */
	function setTransformationType(&$inputType, &$outputType) {
		$typeDescriptionFactory =& TypeDescriptionFactory::getInstance();

		// We need both, the input/output type as a string and
		// as a TypeDescription
		if (is_a($inputType, 'TypeDescription')) {
			$inputTypeString = $inputType->getTypeDescription();
		} else {
			$inputTypeString = $inputType;
			$inputType =& $typeDescriptionFactory->instantiateTypeDescription($inputType);
		}
		if (is_a($outputType, 'TypeDescription')) {
			$outputTypeString = $outputType->getTypeDescription();
		} else {
			$outputTypeString = $outputType;
			$outputType =& $typeDescriptionFactory->instantiateTypeDescription($outputType);
		}

		// Make sure that this transformation is valid
		if (!$this->isValidTransformation($inputTypeString, $outputTypeString)) fatalError('Trying to set an invalid transformation type.');

		$this->_inputType =& $inputType;
		$this->_outputType =& $outputType;
	}

	/**
	 * Set the display name
	 * @param $displayName string
	 */
	function setDisplayName($displayName) {
		$this->setData('displayName', $displayName);
	}

	/**
	 * Get the display name
	 *
	 * NB: The standard implementation of this
	 * method will initialize the display name
	 * with the filter class name. Subclasses can of
	 * course override this behavior by explicitly
	 * setting a display name.
	 *
	 * @return string
	 */
	function getDisplayName() {
		if (!$this->hasData('displayName')) {
			$this->setData('displayName', get_class($this));
		}

		return $this->getData('displayName');
	}

	/**
	 * Get the input type
	 * @return TypeDescription
	 */
	function &getInputType() {
		return $this->_inputType;
	}

	/**
	 * Get the output type
	 * @return TypeDescription
	 */
	function &getOutputType() {
		return $this->_outputType;
	}

	/**
	 * Set whether this is a transformation template
	 * rather than an actual transformation.
	 *
	 * Transformation templates are saved to the database
	 * when the filter is first registered. They are
	 * configured with default settings and will be used
	 * to let users identify available transformation
	 * types.
	 *
	 * There must be exactly one transformation template
	 * for each supported transformation type.
	 *
	 * @param $isTemplate boolean
	 */
	function setIsTemplate($isTemplate) {
		$this->setData('isTemplate', (boolean)$isTemplate);
	}

	/**
	 * Is this a transformation template rather than
	 * an actual transformation?
	 * @return boolean
	 */
	function getIsTemplate() {
		return $this->getData('isTemplate');
	}

	/**
	 * Set the parent filter id
	 * @param $parentFilterId integer
	 */
	function setParentFilterId($parentFilterId) {
		$this->setData('parentFilterId', $parentFilterId);
	}

	/**
	 * Get the parent filter id
	 * @return integer
	 */
	function getParentFilterId() {
		return $this->getData('parentFilterId');
	}

	/**
	 * Set the sequence id
	 * @param $seq integer
	 */
	function setSeq($seq) {
		$this->setData('seq', $seq);
	}

	/**
	 * Get the sequence id
	 * @return integer
	 */
	function getSeq() {
		return $this->getData('seq');
	}

	/**
	 * Get the last valid output produced by
	 * this filter.
	 *
	 * This can be used for debugging internal
	 * filter state or for access to intermediate
	 * results when working with larger filter
	 * grids.
	 *
	 * NB: The output will be set only after
	 * output validation so that you can be
	 * sure that you'll always find valid
	 * data here.
	 *
	 * @return mixed
	 */
	function &getLastOutput() {
		return $this->_output;
	}

	/**
	 * Get the last valid input processed by
	 * this filter.
	 *
	 * This can be used for debugging internal
	 * filter state or for access to intermediate
	 * results when working with larger filter
	 * grids.
	 *
	 * NB: The input will be set only after
	 * input validation so that you can be
	 * sure that you'll always find valid
	 * data here.
	 *
	 * @return mixed
	 */
	function &getLastInput() {
		return $this->_input;
	}

	/**
	 * Add a filter error
	 * @param $message string
	 */
	function addError($message) {
		$this->_errors[] = $message;
	}

	/**
	 * Get all filter errors
	 * @return array
	 */
	function getErrors() {
		return $this->_errors;
	}

	/**
	 * Whether this filter has produced errors.
	 * @return boolean
	 */
	function hasErrors() {
		return (!empty($this->_errors));
	}

	/**
	 * Clear all processing errors.
	 */
	function clearErrors() {
		$this->_errors = array();
	}

	/**
	 * Add a filter setting
	 * @param $setting FilterSetting
	 */
	function addSetting(&$setting) {
		assert(is_a($setting, 'FilterSetting'));
		$settingName = $setting->getName();

		// Check that the setting name does not
		// collide with one of the internal settings.
		if (in_array($settingName, $this->getInternalSettings())) fatalError('Trying to override an internal filter setting!');

		assert(!isset($this->_settings[$settingName]));
		$this->_settings[$settingName] =& $setting;
	}

	/**
	 * Get a filter setting
	 * @param $settingName string
	 * @return FilterSetting
	 */
	function &getSetting($settingName) {
		assert(isset($this->_settings[$settingName]));
		return $this->_settings[$settingName];
	}

	/**
	 * Get all filter settings
	 * @return array a list of FilterSetting objects
	 */
	function &getSettings() {
		return $this->_settings;
	}

	/**
	 * Check whether a given setting
	 * is present in this filter.
	 */
	function hasSetting($settingName) {
		return isset($this->_settings[$settingName]);
	}

	/**
	 * Can this filter be parameterized?
	 * @return boolean
	 */
	function hasSettings() {
		return (is_array($this->_settings) && count($this->_settings));
	}

	/**
	 * Set the required runtime environment
	 * @param $runtimeEnvironment RuntimeEnvironment
	 */
	function setRuntimeEnvironment(&$runtimeEnvironment) {
		assert(is_a($runtimeEnvironment, 'RuntimeEnvironment'));
		$this->_runtimeEnvironment =& $runtimeEnvironment;

		// Inject the runtime settings into the data object
		// for persistence.
		$runtimeSettings = $this->supportedRuntimeEnvironmentSettings();
		foreach($runtimeSettings as $runtimeSetting => $defaultValue) {
			$methodName = 'get'.String::ucfirst($runtimeSetting);
			$this->setData($runtimeSetting, $runtimeEnvironment->$methodName());
		}
	}

	//
	// Abstract template methods to be implemented by subclasses
	//
	/**
	 * Return the fully qualified class name of the filter class.
	 *
	 * (This must be hard coded by sub-classes for PHP4 compatibility.
	 * PHP4 always returns class names lowercase which we cannot
	 * tolerate as we need this path to find the class on case sensitive
	 * file systems.)
	 */
	function getClassName() {
		assert(false);
	}

	/**
	 * Subclasses can override this method if they
	 * support exactly one transformation.
	 *
	 * The return value of this method must be of
	 * the following format:
	 *
	 * array('input type', 'output type')
	 */
	function getSupportedTransformation() {
		// Can be implemented by subclasses.
		assert(false);
	}

	/**
	 * Subclasses can override this method if they
	 * support more than one transformation.
	 *
	 * The return value of this method must be of
	 * the following format:
	 *
	 * array(
	 *   array('input type', 'output type'),
	 *   array(...),
	 *   ...
	 * )
	 *
	 * NB: Classes that override this method must not
	 * at the same time implement getSupportedTransformation().
	 *
	 * @return array
	 */
	function getSupportedTransformations() {
		// The default implementation assumes that there is only
		// one supported transformation and returns it as an array.
		// If your filter supports more than one transformation you
		// can override this method to return an array with
		// multiple entries.
		$supportedTransformation = $this->getSupportedTransformation();
		if (is_array($supportedTransformation) && count($supportedTransformation) == 2) {
			return array($supportedTransformation);
		} else {
			return array();
		}
	}

	/**
	 * This method performs the actual data processing.
	 * NB: sub-classes must implement this method.
	 * @param $input mixed validated filter input data
	 * @return mixed non-validated filter output or null
	 *  if processing was not successful.
	 */
	function &process(&$input) {
		assert(false);
	}

	//
	// Public methods
	//
	/**
	 * Return an array with the names of filter settings.
	 *
	 * This will be used by the FilterDAO for filter
	 * setting persistence.
	 *
	 * @return array
	 */
	function getSettingNames() {
		$settingNames = array();
		foreach($this->getSettings() as $setting) {
			if (!$setting->getIsLocalized()) {
				$settingNames[] = $setting->getName();
			}
		}
		return $settingNames;
	}

	/**
	 * Return an array with the names of localized
	 * filter settings.
	 *
	 * This will be used by the FilterDAO for filter
	 * setting persistence.
	 *
	 * @return array
	 */
	function getLocalizedSettingNames() {
		$localizedSettingNames = array();
		foreach($this->getSettings() as $setting) {
			if ($setting->getIsLocalized()) {
				$localizedSettingNames[] = $setting->getName();
			}
		}
		return $localizedSettingNames;
	}

	/**
	 * Checks whether the given input/output type combination
	 * is supported by this filter.
	 *
	 * @param $inputTypeString string a text representation of the
	 *  requested input type.
	 * @param $outputTypeString string a text representation of the
	 *  requested output type.
	 */
	function isValidTransformation($inputTypeString, $outputTypeString) {
		// The default implementation retrieves a simple list of
		// allowed input/output type combinations and checks whether the
		// given combination is part of that list.
		$validTransformations = $this->getSupportedTransformations();
		foreach($validTransformations as $validTransformation) {
			assert(count($validTransformation) == 2);
			if ($validTransformation[0] == $inputTypeString && $validTransformation[1] == $outputTypeString) return true;
		}
		return false;
	}

	/**
	 * Returns true if the given input and output
	 * objects represent a valid transformation
	 * for this filter.
	 *
	 * This check must be type based. It can
	 * optionally include an additional stateful
	 * inspection of the given object instances.
	 *
	 * If the output type is null then only
	 * check whether the given input type is
	 * one of the input types accepted by this
	 * filter.
	 *
	 * The standard implementation provides full
	 * type based checking. Subclasses must
	 * implement any required stateful inspection
	 * of the provided objects.
	 *
	 * @param $input mixed
	 * @param $output mixed
	 * @return boolean
	 */
	function supports(&$input, &$output) {
		// Validate input
		$inputType =& $this->getInputType();
		$validInput = $inputType->isCompatible($input);

		// If output is null then we're done
		if (is_null($output)) return $validInput;

		// Validate output
		$outputType =& $this->getOutputType();
		$validOutput = $outputType->isCompatible($output);

		return $validInput && $validOutput;
	}

	/**
	 * Returns true if the given input is supported
	 * by this filter. Otherwise returns false.
	 *
	 * NB: sub-classes will not normally override
	 * this method.
	 *
	 * @param $input mixed
	 * @return boolean
	 */
	function supportsAsInput(&$input) {
		$nullVar = null;
		return($this->supports($input, $nullVar));
	}

	/**
	 * Check whether the filter is compatible with
	 * the required runtime environment.
	 * @return boolean
	 */
	function isCompatibleWithRuntimeEnvironment() {
		if ($this->_runtimeEnvironment === false) {
			// The runtime environment has never been
			// queried before.
			$runtimeSettings = $this->supportedRuntimeEnvironmentSettings();

			// Find out whether we have any runtime restrictions set.
			$hasRuntimeSettings = false;
			foreach($runtimeSettings as $runtimeSetting => $defaultValue) {
				if ($this->hasData($runtimeSetting)) {
					$$runtimeSetting = $this->getData($runtimeSetting);
					$hasRuntimeSettings = true;
				} else {
					$$runtimeSetting = $defaultValue;
				}
			}

			// If we found any runtime restrictions then construct a
			// runtime environment from the settings.
			if ($hasRuntimeSettings) {
				import('lib.pkp.classes.core.RuntimeEnvironment');
				$this->_runtimeEnvironment = new RuntimeEnvironment($phpVersionMin, $phpVersionMax, $phpExtensions, $externalPrograms);
			} else {
				// Set null so that we don't try to construct
				// a runtime environment object again.
				$this->_runtimeEnvironment = null;
			}
		}

		if (is_null($this->_runtimeEnvironment) || $this->_runtimeEnvironment->isCompatible()) return true;

		return false;
	}

	/**
	 * Filters the given input.
	 *
	 * Input and output of this method will
	 * be tested for compliance with the filter
	 * definition.
	 *
	 * NB: sub-classes will not normally override
	 * this method.
	 *
	 * @param mixed an input value that is supported
	 *  by this filter
	 * @return mixed a valid return value or null
	 *  if an error occurred during processing
	 */
	function &execute(&$input) {
		// Make sure that we don't destroy referenced
		// data somewhere out there.
		unset($this->_input, $this->_output);

		// Check the runtime environment
		if (!$this->isCompatibleWithRuntimeEnvironment()) {
			// Missing installation requirements.
			fatalError('Trying to run a transformation that is not supported in your installation environment.');
		}

		// Validate the filter input
		if (!$this->supportsAsInput($input)) {
			// We have no valid input so return
			// an empty output (see unset statement
			// above).
			return $this->_output;
		}

		// Save a reference to the last valid input
		$this->_input =& $input;

		// Process the filter
		$preliminaryOutput =& $this->process($input);

		// Validate the filter output
		if (!is_null($preliminaryOutput) && $this->supports($input, $preliminaryOutput)) {
			$this->_output =& $preliminaryOutput;
		}

		// Return processed data
		return $this->_output;
	}

	//
	// Public helper methods
	//
	/**
	 * Returns a static array with supported runtime
	 * environment settings and their default values.
	 *
	 * PHP4 workaround for missing static class members.
	 *
	 * @return array
	 */
	function supportedRuntimeEnvironmentSettings() {
		static $runtimeEnvironmentSettings = array(
			'phpVersionMin' => PHP_REQUIRED_VERSION,
			'phpVersionMax' => null,
			'phpExtensions' => array(),
			'externalPrograms' => array()
		);

		return $runtimeEnvironmentSettings;
	}

	//
	// Protected helper methods
	//
	/**
	 * Returns names of settings which are in use by the
	 * filter class and therefore cannot be set as filter
	 * settings
	 * @return array
	 */
	function getInternalSettings() {
		return array('id', 'displayName', 'isTemplate', 'parentFilterId', 'seq');
	}
}
?>
