<?php

/**
 * @file classes/oai/OAIStruct.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIConfig
 * @ingroup oai
 * @see OAI
 *
 * @brief Data structures associated with the OAI request handler.
 */



/**
 * OAI repository configuration.
 */
class OAIConfig {
	/** @var $baseUrl string URL to the OAI front-end */
	var $baseUrl = '';

	/** @var $repositoryId string identifier of the repository */
	var $repositoryId = 'oai';

	/** @var $granularity string record datestamp granularity */
	// Must be either 'YYYY-MM-DD' or 'YYYY-MM-DDThh:mm:ssZ'
	var $granularity = 'YYYY-MM-DDThh:mm:ssZ';

	/** @var $tokenLifetime int TTL of resumption tokens */
	var $tokenLifetime = 3600;

	/** @var $maxIdentifiers int maximum identifiers returned per request */
	var $maxIdentifiers = 500;

	/** @var $maxRecords int maximum records returned per request */
	var $maxRecords;

	/** @var $maxSets int maximum sets returned per request */
	// Must be set to zero if sets not supported by repository
	var $maxSets = 50;


	/**
	 * Constructor.
	 */
	function OAIConfig($baseUrl, $repositoryId) {
		$this->baseUrl = $baseUrl;
		$this->repositoryId = $repositoryId;

		$this->maxRecords = Config::getVar('oai', 'oai_max_records');
		if (!$this->maxRecords) $this->maxRecords = 100;
	}
}

/**
 * OAI repository information.
 */
class OAIRepository {

	/** @var $repositoryName string name of the repository */
	var $repositoryName;

	/** @var $adminEmail string administrative contact email */
	var $adminEmail;

	/** @var $earliestDatestamp int earliest *nix timestamp in the repository */
	var $earliestDatestamp;

	/** @var $delimiter string delimiter in identifier */
	var $delimiter = ':';

	/** @var $sampleIdentifier string example identifier */
	var $sampleIdentifier;
}


/**
 * OAI resumption token.
 * Used to resume a record retrieval at the last-retrieved offset.
 */
class OAIResumptionToken {

	/** @var $id string unique token ID */
	var $id;

	/** @var $offset int record offset */
	var $offset;

	/** @var $params array request parameters */
	var $params;

	/** @var $expire int expiration timestamp */
	var $expire;


	/**
	 * Constructor.
	 */
	function OAIResumptionToken($id, $offset, $params, $expire) {
		$this->id = $id;
		$this->offset = $offset;
		$this->params = $params;
		$this->expire = $expire;
	}
}


/**
 * OAI metadata format.
 * Used to generated metadata XML according to a specified schema.
 */
class OAIMetadataFormat {

	/** @var $prefix string metadata prefix */
	var $prefix;

	/** @var $schema string XML schema */
	var $schema;

	/** @var $namespace string XML namespace */
	var $namespace;

	/**
	 * Constructor.
	 */
	function OAIMetadataFormat($prefix, $schema, $namespace) {
		$this->prefix = $prefix;
		$this->schema = $schema;
		$this->namespace = $namespace;
	}

	function getLocalizedData($data, $locale) {
		foreach ($data as $element) {
			if (isset($data[$locale])) return $data[$locale];
		}
		return '';
	}

	/**
	 * Retrieve XML-formatted metadata for the specified record.
	 * @param $record OAIRecord
	 * @param $format string OAI metadata prefix
	 * @return string
	 */
	function toXml($record, $format = null) {
		return '';
	}

	function stripAssocArray($values) {
		foreach (array_keys($values) as $key) {
			$values[$key] = strip_tags($values[$key]);
		}
		return $values;
	}
}


/**
 * OAI set.
 * Identifies a set of related records.
 */
class OAISet {

	/** @var $spec string unique set specifier */
	var $spec;

	/** @var $name string set name */
	var $name;

	/** @var $description string set description */
	var $description;


	/**
	 * Constructor.
	 */
	function OAISet($spec, $name, $description) {
		$this->spec = $spec;
		$this->name = $name;
		$this->description = $description;
	}
}


/**
 * OAI identifier.
 */
class OAIIdentifier {
	/** @var $identifier string unique OAI record identifier */
	var $identifier;

	/** @var $datestamp int last-modified *nix timestamp */
	var $datestamp;

	/** @var $sets array sets this record belongs to */
	var $sets;

	function OAIIdentifier() {
	}
}


/**
 * OAI record.
 * Describes metadata for a single record in the repository.
 */
class OAIRecord extends OAIIdentifier {
	var $data;

	function OAIRecord() {
		parent::OAIIdentifier();
		$this->data = array();
	}

	function setData($name, &$value) {
		$this->data[$name] =& $value;
	}

	function &getData($name) {
		if (isset($this->data[$name])) $returner =& $this->data[$name];
		else $returner = null;

		return $returner;
	}
}

?>
