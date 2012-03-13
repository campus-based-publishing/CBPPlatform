<?php

/**
 * @file classes/file/wrappers/FTPFileWrapper.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file.wrappers
 * @ingroup file_wrappers
 *
 * @brief Class abstracting operations for reading remote files using various protocols.
 * (for when allow_url_fopen is disabled).
 *
 */

// $Id$


class FTPFileWrapper extends FileWrapper {

	/** @var $ctrl Control socket */
	var $ctrl;

	function open($mode = 'r') {
		$user = isset($this->info['user']) ? $this->info['user'] : 'anonymous';
		$pass = isset($this->info['pass']) ? $this->info['pass'] : 'user@example.com';
		$host = isset($this->info['host']) ? $this->info['host'] : 'localhost';
		$port = isset($this->info['port']) ? (int)$this->info['port'] : 21;
		$path = isset($this->info['path']) ? $this->info['path'] : '/';

		if (!($this->ctrl = fsockopen($host, $port, $errno, $errstr)))
			return false;

		if ($this->_open($user, $pass, $path))
			return true;

		$this->close();
		return false;
	}

	function close() {
		if ($this->fp) {
			parent::close();
			$rc = $this->_receive(); // FIXME Check rc == 226 ?
		}

		$this->_send('QUIT'); // FIXME Check rc == 221?
		$rc = $this->_receive();

		fclose($this->ctrl);
		$this->ctrl = null;
	}

	function _open($user, $pass, $path) {
		// Connection establishment
		if ($this->_receive() != '220')
			return false;

		// Authentication
		$this->_send('USER', $user);
		$rc = $this->_receive();
		if ($rc == '331') {
			$this->_send('PASS', $pass);
			$rc = $this->_receive();
		}
		if ($rc != '230')
			return false;

		// Binary transfer mode
		$this->_send('TYPE', 'I');
		if ($this->_receive() != '200')
			return false;

		// Enter passive mode and open data transfer connection
		$this->_send('PASV');
		if ($this->_receiveLine($line) != '227')
			return false;

		if (!preg_match('/(\d+),(\d+),(\d+),(\d+),(\d+),(\d+)/', $line, $matches))
			return false;
		list($tmp, $h1, $h2, $h3, $h4, $p1, $p2) = $matches;

		$host = "$h1.$h2.$h3.$h4";
		$port = ($p1 << 8) + $p2;

		if (!($this->fp = fsockopen($host, $port, $errno, $errstr)))
			return false;

		// Retrieve file
		$this->_send('RETR', $path);
		$rc = $this->_receive();
		if ($rc != '125' && $rc != '150')
			return false;

		return true;
	}

	function _send($command, $data = '') {
		return fwrite($this->ctrl, $command . (empty($data) ? '' : ' ' . $data) . "\r\n");
	}

	function _receive() {
		return $this->_receiveLine($line);
	}

	function _receiveLine(&$line) {
 		do {
 			$line = fgets($this->ctrl);
 		} while($line !== false && ($tmp = substr(trim($line), 3, 1)) != ' ' && $tmp != '');

 		if ($line !== false) {
 			return substr($line, 0, 3);
 		}
 		return false;
	}
}

?>
