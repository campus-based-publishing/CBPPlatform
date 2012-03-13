<?php

/**
 * @file classes/file/wrappers/HTTPSFileWrapper.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HTTPSFileWrapper
 * @ingroup file_wrappers
 *
 * @brief Class abstracting operations for reading remote files using various protocols.
 * (for when allow_url_fopen is disabled).
 *
 */

// $Id$


import('lib.pkp.classes.file.wrappers.HTTPFileWrapper');

class HTTPSFileWrapper extends HTTPFileWrapper {
	function HTTPSFileWrapper($url, &$info) {
		parent::HTTPFileWrapper($url, $info);
		$this->setDefaultPort(443);
		$this->setDefaultHost('ssl://localhost');
		if (isset($this->info['host'])) {
			$this->info['host'] = 'ssl://' . $this->info['host'];
		}
	}
}

?>
