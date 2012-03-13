<?php

/**
 * @file ControlledVocabEntry.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabEntry
 * @ingroup controlled_vocabs
 * @see ControlledVocabEntryDAO
 *
 * @brief Basic class describing a controlled vocab.
 */

//$Id$

class ControlledVocabEntry extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the controlled vocab.
	 * @return int
	 */
	function getControlledVocabId() {
		return $this->getData('controlledVocabId');
	}

	/**
	 * Set the ID of the controlled vocab.
	 * @param $controlledVocabId int
	 */
	function setControlledVocabId($controlledVocabId) {
		return $this->setData('controlledVocabId', $controlledVocabId);
	}

	/**
	 * Get sequence number.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence number.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Get the localized name.
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the name of the controlled vocabulary entry.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the controlled vocabulary entry.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}
}

?>
