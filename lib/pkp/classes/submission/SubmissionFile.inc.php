<?php

/**
 * @file classes/submission/SubmissionFile.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFile
 * @ingroup submission
 *
 * @brief Submission file class.
 */

// $Id$


class SubmissionFile extends DataObject {

	/**
	 * Constructor.
	 */
	function SubmissionFile() {
		parent::DataObject();
	}


	//
	// Get/set methods
	//

	/**
	 * Get ID of file.
	 * @return int
	 */
	function getFileId() {
		// WARNING: Do not modernize getter/setters without considering
		// ID clash with subclasses ArticleGalley and ArticleNote!
		return $this->getData('fileId');
	}

	/**
	 * Set ID of file.
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		// WARNING: Do not modernize getter/setters without considering
		// ID clash with subclasses ArticleGalley and ArticleNote!
		return $this->setData('fileId', $fileId);
	}

	/**
	 * Get source file ID of this file.
	 * @return int
	 */
	function getSourceFileId() {
		return $this->getData('sourceFileId');
	}

	/**
	 * Set source file ID of this file.
	 * @param $sourceFileId int
	 */
	function setSourceFileId($sourceFileId) {
		return $this->setData('sourceFileId', $sourceFileId);
	}

	/**
	 * Get source revision of this file.
	 * @return int
	 */
	function getSourceRevision() {
		return $this->getData('sourceRevision');
	}

	/**
	 * Set source revision of this file.
	 * @param $sourceRevision int
	 */
	function setSourceRevision($sourceRevision) {
		return $this->setData('sourceRevision', $sourceRevision);
	}

	/**
	 * Get associated ID of file. (Used, e.g., for email log attachments.)
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set associated ID of file. (Used, e.g., for email log attachments.)
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}

	/**
	 * Get revision number.
	 * @return int
	 */
	function getRevision() {
		return $this->getData('revision');
	}

	/**
	 * Set revision number.
	 * @param $revision int
	 */
	function setRevision($revision) {
		return $this->setData('revision', $revision);
	}

	/**
	 * Get ID of submission.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set ID of submission.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get file name of the file.
	 * @param return string
	 */
	function getFileName() {
		return $this->getData('fileName');
	}

	/**
	 * Set file name of the file.
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName', $fileName);
	}

	/**
	 * Get file type of the file.
	 * @ return string
	 */
	function getFileType() {
		return $this->getData('fileType');
	}

	/**
	 * Set file type of the file.
	 * @param $fileType string
	 */
	function setFileType($fileType) {
		return $this->setData('fileType', $fileType);
	}

	/**
	 * Get original uploaded file name of the file.
	 * @param return string
	 */
	function getOriginalFileName() {
		return $this->getData('originalFileName');
	}

	/**
	 * Set original uploaded file name of the file.
	 * @param $originalFileName string
	 */
	function setOriginalFileName($originalFileName) {
		return $this->setData('originalFileName', $originalFileName);
	}

	/**
	 * Get type of the file.
	 * @ return string
	 */
	function getType() {
		return $this->getData('type');
	}

	/**
	 * Set type of the file.
	 * @param $type string
	 */
	function setType($type) {
		return $this->setData('type', $type);
	}

	/**
	 * Get uploaded date of file.
	 * @return date
	 */

	function getDateUploaded() {
		return $this->getData('dateUploaded');
	}


	/**
	 * Set uploaded date of file.
	 * @param $dateUploaded date
	 */

	function setDateUploaded($dateUploaded) {
		return $this->SetData('dateUploaded', $dateUploaded);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */

	function getDateModified() {
		return $this->getData('dateModified');
	}


	/**
	 * Set modified date of file.
	 * @param $dateModified date
	 */

	function setDateModified($dateModified) {
		return $this->SetData('dateModified', $dateModified);
	}

	/**
	 * Get file size of file.
	 * @return int
	 */

	function getFileSize() {
		return $this->getData('fileSize');
	}


	/**
	 * Set file size of file.
	 * @param $fileSize int
	 */

	function setFileSize($fileSize) {
		return $this->SetData('fileSize', $fileSize);
	}

	/**
	 * Get nice file size of file.
	 * @return string
	 */

	function getNiceFileSize() {
		return FileManager::getNiceFileSize($this->getData('fileSize'));
	}

	/**
	 * Get round.
	 * @return int
	 */

	function getRound() {
		return $this->getData('round');
	}


	/**
	 * Set round.
	 * @param $round int
	 */

	function setRound($round) {
		return $this->SetData('round', $round);
	}

	/**
	 * Get viewable.
	 * @return boolean
	 */

	function getViewable() {
		return $this->getData('viewable');
	}


	/**
	 * Set viewable.
	 * @param $viewable boolean
	 */

	function setViewable($viewable) {
		return $this->SetData('viewable', $viewable);
	}
	
	/**
	 * %CBP% Set Fedora Namespace
	 * @param $namespace string
	 */
	
	function setFedoraNamespace($namespace) {
		return $this->SetData('fedora_namespace', $namespace);
	}
	
	/**
	 * %CBP% Set Fedora Pid
	 * @param $pid string
	 */
	
	function setFedoraPid($pid) {
		return $this->SetData('fedora_pid', $pid);
	}
	
	/**
	 * %CBP% Set Fedora Dsid
	 * @param $dsid string
	 */
	
	function setFedoraDsid($dsid) {
		return $this->SetData('fedora_dsid', $dsid);
	}
	
	/**
	 * %CBP% Get Fedora Namespace
	 * @param $namespace string
	 */
	
	function getFedoraNamespace() {
		return $this->getData('fedora_namespace');
	}
	
	/**
	 * %CBP% Get Fedora Pid
	 * @param $pid string
	 */
	
	function getFedoraPid() {
		return $this->getData('fedora_pid');
	}
	
	/**
	 * %CBP% Get Fedora Dsid
	 * @param $dsid string
	 */
	
	function getFedoraDsid() {
		return $this->getData('fedora_dsid');
	}

}

?>
