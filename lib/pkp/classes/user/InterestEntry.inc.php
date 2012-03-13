<?php

/**
 * @file Interest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Interest
 * @ingroup user
 * @see InterestDAO
 *
 * @brief Basic class describing a reviewer interest
 */

//$Id$


import('lib.pkp.classes.controlledVocab.ControlledVocabEntry');

class InterestEntry extends ControlledVocabEntry {
	//
	// Get/set methods
	//
	
	/**
	 * Get the interest
	 * @return string
	 */
	function getInterest() {
		return $this->getData('interest');
	}

	/**
	 * Set the interest text
	 * @param interest
	 */
	function setInterest($interest) {
		$this->setData('interest', $interest);
	}
}

?>
