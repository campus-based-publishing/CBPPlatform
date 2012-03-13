<?php

/**
 * @defgroup file
 */

/**
 * @file classes/file/FileManager.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileManager
 * @ingroup file
 *
 * @brief Class defining basic operations for file management.
 */

// $Id$

include('rest/digitalobject.class.php');

define('FILE_MODE_MASK', 0666);
define('DIRECTORY_MODE_MASK', 0777);

define('DOCUMENT_TYPE_DEFAULT', 'default');
define('DOCUMENT_TYPE_EXCEL', 'excel');
define('DOCUMENT_TYPE_HTML', 'html');
define('DOCUMENT_TYPE_IMAGE', 'image');
define('DOCUMENT_TYPE_PDF', 'pdf');
define('DOCUMENT_TYPE_WORD', 'word');
define('DOCUMENT_TYPE_ZIP', 'zip');

class FileManager {
	/**
	 * Constructor
	 */
	function FileManager() {
	}

	/**
	 * Return true if an uploaded file exists.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function uploadedFileExists($fileName) {
		if (isset($_FILES[$fileName]['tmp_name']) && is_uploaded_file($_FILES[$fileName]['tmp_name'])) {
			return true;
		}
		return false;
	}

	/**
	 * Return true iff an error occurred when trying to upload a file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return boolean
	 */
	function uploadError($fileName) {
		return (isset($_FILES[$fileName]) && $_FILES[$fileName]['error'] != 0);
	}

	/**
	 * Return the (temporary) path to an uploaded file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return string (boolean false if no such file)
	 */
	function getUploadedFilePath($fileName) {
		if (isset($_FILES[$fileName]['tmp_name']) && is_uploaded_file($_FILES[$fileName]['tmp_name'])) {
			return $_FILES[$fileName]['tmp_name'];
		}
		return false;
	}

	/**
	 * Return the user-specific (not temporary) filename of an uploaded file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return string (boolean false if no such file)
	 */
	function getUploadedFileName($fileName) {
		if (isset($_FILES[$fileName]['name'])) {
			return $_FILES[$fileName]['name'];
		}
		return false;
	}

	/**
	 * Return the type of an uploaded file.
	 * @param $fileName string the name of the file used in the POST form
	 * @return string
	 */
	function getUploadedFileType($fileName) {
		if (isset($_FILES[$fileName])) {
			$type = String::mime_content_type($_FILES[$fileName]['tmp_name']);
			if (!empty($type)) return $type;
			return $_FILES[$fileName]['type'];
		}
		return false;
	}

	/**
	 * Upload a file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $dest string the path where the file is to be saved
	 * @return boolean returns true if successful
	 */
	function uploadFile($fileName, $destFileName) {
		$destDir = dirname($destFileName);
		if (!FileManager::fileExists($destDir, 'dir')) {
			// Try to create the destination directory
			FileManager::mkdirtree($destDir);
		}
		if (!isset($_FILES[$fileName])) return false;
		if (move_uploaded_file($_FILES[$fileName]['tmp_name'], $destFileName))
			return FileManager::setMode($destFileName, FILE_MODE_MASK);
		return false;
	}

	/**
	 * Write a file.
	 * @param $dest string the path where the file is to be saved
	 * @param $contents string the contents to write to the file
	 * @return boolean returns true if successful
	 */
	function writeFile($dest, &$contents) {
		$success = true;
		$destDir = dirname($dest);
		if (!FileManager::fileExists($destDir, 'dir')) {
			// Try to create the destination directory
			FileManager::mkdirtree($destDir);
		}
		if (($f = fopen($dest, 'wb'))===false) $success = false;
		if ($success && fwrite($f, $contents)===false) $success = false;
		@fclose($f);

		if ($success)
			return FileManager::setMode($dest, FILE_MODE_MASK);
		return false;
	}

	/**
	 * Copy a file.
	 * @param $source string the source URL for the file
	 * @param $dest string the path where the file is to be saved
	 * @return boolean returns true if successful
	 */
	function copyFile($source, $dest) {
		$success = true;
		$destDir = dirname($dest);
		if (!FileManager::fileExists($destDir, 'dir')) {
			// Try to create the destination directory
			FileManager::mkdirtree($destDir);
		}
		if (copy($source, $dest))
			return FileManager::setMode($dest, FILE_MODE_MASK);
		return false;
	}

	/**
	 * Copy a directory.
	 * Adapted from code by gimmicklessgpt at gmail dot com, at http://php.net/manual/en/function.copy.php
	 * @param $source string the path to the source directory
	 * @param $dest string the path where the directory is to be saved
	 * @return boolean returns true if successful
	 */
	function copyDir($source, $dest) {
		if (is_dir($source)) {
			FileManager::mkdir($dest);
			$destDir = dir($source);

			while (($entry = $destDir->read()) !== false) {
				if ($entry == '.' || $entry == '..') {
					continue;
				}

				$Entry = $source . DIRECTORY_SEPARATOR . $entry;
				if (is_dir($Entry) ) {
					FileManager::copyDir($Entry, $dest . DIRECTORY_SEPARATOR . $entry );
					continue;
				}
				FileManager::copyFile($Entry, $dest . DIRECTORY_SEPARATOR . $entry );
			}

			$destDir->close();
		} else {
			FileManager::copyFile($source, $target);
		}

		if (FileManager::fileExists($dest, 'dir')) {
			return true;
		} else return false;
	}


	/**
	 * Read a file's contents.
	 * @param $filePath string the location of the file to be read
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function &readFile($filePath, $output = false) {
		if (is_readable($filePath)) {
			$f = fopen($filePath, 'rb');
			$data = '';
			while (!feof($f)) {
				$data .= fread($f, 4096);
				if ($output) {
					echo $data;
					$data = '';
				}
			}
			fclose($f);

			if ($output) {
				$returner = true;
				return $returner;
			} else {
				return $data;
			}

		} else {
			$returner = false;
			return $returner;
		}
	}

	/**
	 * Download a file.
	 * Outputs HTTP headers and file content for download
	 * @param $filePath string the location of the file to be sent
	 * @param $type string the MIME type of the file, optional
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($filePath, $type = null, $inline = false, $label = null, $repo = false) {
		if ($repo == false) {
			if (HookRegistry::call('FileManager::downloadFile', array(&$filePath, &$type, &$inline, &$result))) return $result;
			if (is_readable($filePath)) {
				if ($type == null) {
					$type = String::mime_content_type($filePath);
					if (empty($type)) $type = 'application/octet-stream';
				}
				Registry::clear(); // Free some memory
	
				header("Content-Type: $type");
				header("Content-Length: ".filesize($filePath));
				header("Content-Disposition: " . ($inline ? 'inline' : 'attachment') . "; filename=\"" .basename($filePath)."\"");
				header("Cache-Control: private");
				header("Pragma: public");
	
				import('lib.pkp.classes.file.FileManager');
				FileManager::readFile($filePath, true);
	
				return true;
	
			} else {
				return false;
			}
		} else {
			//return file from repository
			$ch = curl_init($filePath);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$c = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
			
			if ($label == null) {
				$filename =  str_replace("/content", "", $filePath);
				$filename = substr($filename, strrpos($filename, "/") + 1);
			}
	
			header("Etag: " . md5($filename)); 
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	  	  	header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header("Content-Type:" . $info['content_type']);
			header("Content-Disposition: " . ($inline ? 'inline' : 'attachment') . "; filename=\"" . $label ."\"");
			header("Cache-Control: private");
			header("Pragma: public");
			readfile($filePath);
		}
	}

	/**
	 * View a file inline (variant of downloadFile).
	 * @see FileManager::downloadFile
	 */
	function viewFile($filePath, $type = null) {
		FileManager::downloadFile($filePath, $type, true);
	}

	/**
	 * Delete a file.
	 * @param $filePath string the location of the file to be deleted
	 * @return boolean returns true if successful
	 */
	function deleteFile($filePath) {
		if (FileManager::fileExists($filePath)) {
			return unlink($filePath);
		} else {
			return false;
		}
	}

	/**
	 * Create a new directory.
	 * @param $dirPath string the full path of the directory to be created
	 * @param $perms string the permissions level of the directory (optional)
	 * @return boolean returns true if successful
	 */
	function mkdir($dirPath, $perms = null) {
		if ($perms !== null) {
			return mkdir($dirPath, $perms);
		} else {
			if (mkdir($dirPath))
				return FileManager::setMode($dirPath, DIRECTORY_MODE_MASK);
			return false;
		}
	}

	/**
	 * Remove a directory.
	 * @param $dirPath string the full path of the directory to be delete
	 * @return boolean returns true if successful
	 */
	function rmdir($dirPath) {
		return rmdir($dirPath);
	}

	/**
	 * Delete all contents including directory (equivalent to "rm -r")
	 * @param $file string the full path of the directory to be removed
	 */
	function rmtree($file) {
		if (file_exists($file)) {
			if (is_dir($file)) {
				$handle = opendir($file);
				import('lib.pkp.classes.file.FileManager');
				while (($filename = readdir($handle)) !== false) {
					if ($filename != '.' && $filename != '..') {
						FileManager::rmtree($file . '/' . $filename);
					}
				}
				closedir($handle);
				rmdir($file);

			} else {
				unlink($file);
			}
		}
	}

	/**
	 * Create a new directory, including all intermediate directories if required (equivalent to "mkdir -p")
	 * @param $dirPath string the full path of the directory to be created
	 * @param $perms string the permissions level of the directory (optional)
	 * @return boolean returns true if successful
	 */
	function mkdirtree($dirPath, $perms = null) {
		if (!file_exists($dirPath)) {
			if (FileManager::mkdirtree(dirname($dirPath), $perms)) {
				return FileManager::mkdir($dirPath, $perms);
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if a file path is valid;
	 * @param $filePath string the file/directory to check
	 * @param $type string (file|dir) the type of path
	 */
	function fileExists($filePath, $type = 'file') {
		switch ($type) {
			case 'file':
				return file_exists($filePath);
			case 'dir':
				return file_exists($filePath) && is_dir($filePath);
			default:
				return false;
		}
	}

	/**
	 * Returns a file type, based on generic categories defined above
	 * @param $type String
	 * @return string (Enuemrated DOCUMENT_TYPEs)
	 */
	function getDocumentType($type) {
		if ( $this->getImageExtension($type) )
			return DOCUMENT_TYPE_IMAGE;
		switch ($type) {
			case 'application/pdf':
			case 'application/x-pdf':
			case 'text/pdf':
			case 'text/x-pdf':
				return DOCUMENT_TYPE_PDF;
			case 'application/word':
				return DOCUMENT_TYPE_WORD;
			case 'application/excel':
				return DOCUMENT_TYPE_EXCEL;
			case 'text/html':
				return DOCUMENT_TYPE_HTML;
			case 'application/zip':
			case 'application/x-zip':
			case 'application/x-zip-compressed':
			case 'application/x-compress':
			case 'application/x-compressed':
			case 'multipart/x-zip':
				return DOCUMENT_TYPE_ZIP;
			default:
				return DOCUMENT_TYPE_DEFAULT;
		}
	}

	/**
	 * Returns file extension associated with the given document type,
	 * or false if the type does not belong to a recognized document type.
	 * @param $type string
	 */
	function getDocumentExtension($type) {
		switch ($type) {
			case 'application/pdf':
				return '.pdf';
			case 'application/word':
				return '.doc';
			case 'text/html':
				return '.html';
			default:
				return false;
		}
	}

	/**
	 * Returns file extension associated with the given image type,
	 * or false if the type does not belong to a recognized image type.
	 * @param $type string
	 */
	function getImageExtension($type) {
		switch ($type) {
			case 'image/gif':
				return '.gif';
			case 'image/jpeg':
			case 'image/pjpeg':
				return '.jpg';
			case 'image/png':
			case 'image/x-png':
				return '.png';
			case 'image/vnd.microsoft.icon':
			case 'image/x-icon':
			case 'image/x-ico':
			case 'image/ico':
				return '.ico';
			case 'application/x-shockwave-flash':
				return '.swf';
			case 'video/x-flv':
			case 'application/x-flash-video':
			case 'flv-application/octet-stream':
				return '.flv';
			case 'audio/mpeg':
				return '.mp3';
			case 'audio/x-aiff':
				return '.aiff';
			case 'audio/x-wav':
				return '.wav';
			case 'video/mpeg':
				return '.mpg';
			case 'video/quicktime':
				return '.mov';
			case 'video/mp4':
				return '.mp4';
			case 'text/javascript':
				return '.js';
			default:
				return false;
		}
	}

	/**
	 * Parse file extension from file name.
	 * @param string a valid file name
	 * @return string extension
	 */
	function getExtension($fileName) {
		$extension = '';
		$fileParts = explode('.', $fileName);
		if (is_array($fileParts)) {
			$extension = $fileParts[count($fileParts) - 1];
		}
		return $extension;
	}

	/**
	 * Truncate a filename to fit in the specified length.
	 */
	function truncateFileName($fileName, $length = 127) {
		if (String::strlen($fileName) <= $length) return $fileName;
		$ext = FileManager::getExtension($fileName);
		$truncated = String::substr($fileName, 0, $length - 1 - String::strlen($ext)) . '.' . $ext;
		return String::substr($truncated, 0, $length);
	}

	/**
	 * Return pretty file size string (in B, KB, MB, or GB units).
	 * @param $size int file size in bytes
	 * @return string
	 */
	function getNiceFileSize($size) {
		$niceFileSizeUnits = array('B', 'KB', 'MB', 'GB');
		for($i = 0; $i < 4 && $size > 1024; $i++) {
			$size >>= 10;
		}
		return $size . $niceFileSizeUnits[$i];
	}

	/**
	 * Set file/directory mode based on the 'umask' config setting.
	 * @param $path string
	 * @param $mask int
	 * @return boolean
	 */
	function setMode($path, $mask) {
		$umask = Config::getVar('files', 'umask');
		if (!$umask)
			return true;
		return chmod($path, $mask & ~$umask);
	}

	/**
	 * Parse the file extension from a filename/path.
	 * @param $fileName string
	 * @return string
	 */
	function parseFileExtension($fileName) {
		$fileParts = explode('.', $fileName);
		if (is_array($fileParts)) {
			$fileExtension = $fileParts[count($fileParts) - 1];
		}

		// FIXME Check for evil
		if (!isset($fileExtension) || strstr($fileExtension, 'php') || strlen($fileExtension) > 6 || !preg_match('/^\w+$/', $fileExtension)) {
			$fileExtension = 'txt';
		}

		return $fileExtension;
	}
}

?>
