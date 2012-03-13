<?php

/**
 * @file classes/file/ArticleFileManager.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleFileManager
 * @ingroup file
 *
 * @brief Class defining operations for article file management.
 *
 * Article directory structure:
 * [article id]/note
 * [article id]/public
 * [article id]/submission
 * [article id]/submission/original
 * [article id]/submission/review
 * [article id]/submission/editor
 * [article id]/submission/copyedit
 * [article id]/submission/layout
 * [article id]/supp
 * [article id]/attachment
 */


import('lib.pkp.classes.file.FileManager');

/* File type suffixes */
define('ARTICLE_FILE_SUBMISSION',	'SM');
define('ARTICLE_FILE_REVIEW',		'RV');
define('ARTICLE_FILE_EDITOR',		'ED');
define('ARTICLE_FILE_COPYEDIT',		'CE');
define('ARTICLE_FILE_LAYOUT',		'LE');
define('ARTICLE_FILE_PUBLIC',		'PB');
define('ARTICLE_FILE_SUPP',		'SP');
define('ARTICLE_FILE_NOTE',		'NT');
define('ARTICLE_FILE_ATTACHMENT',	'AT');

class ArticleFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated article */
	var $articleId;

	/** @var Article the associated article */
	var $article;

	/**
	 * Constructor.
	 * Create a manager for handling article file uploads.
	 * @param $articleId int
	 */
	function ArticleFileManager($articleId) {
		$this->articleId = $articleId;
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->article =& $articleDao->getArticle($articleId);
		$journalId = $this->article->getJournalId();
		$this->filesDir = Config::getVar('files', 'files_dir') . '/journals/' . $journalId .
		'/articles/' . $articleId . '/';
	}

	/**
	 * Upload a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionFile($fileName, $fileId = null, $overwrite = false) {
		return $this->handleUpload($fileName, ARTICLE_FILE_SUBMISSION, $fileId, $overwrite);
	}

	/**
	 * Upload a file to the review file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, ARTICLE_FILE_REVIEW, $fileId);
	}

	/**
	 * Upload a file to the editor decision file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadEditorDecisionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, ARTICLE_FILE_EDITOR, $fileId);
	}

	/**
	 * Upload a file to the copyedit file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadCopyeditFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, ARTICLE_FILE_COPYEDIT, $fileId);
	}

	/**
	 * Upload a section editor's layout editing file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is null if failure
	 */
	function uploadLayoutFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_LAYOUT, $fileId, $overwrite);
	}

	/**
	 * Upload a supp file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSuppFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Upload a public file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadPublicFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Upload a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionNoteFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_NOTE, $fileId, $overwrite);
	}

	/**
	 * Write a public file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writePublicFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, ARTICLE_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Copy a public file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyPublicFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, ARTICLE_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Write a supplemental file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writeSuppFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, ARTICLE_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy a supplemental file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copySuppFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, ARTICLE_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy an attachment file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyAttachmentFile($url, $mimeType, $fileId = null, $overwrite = true, $assocId = null) {
		return $this->handleCopy($url, $mimeType, ARTICLE_FILE_ATTACHMENT, $fileId, $overwrite, $assocId);
	}

	/**
	 * Retrieve file information by file ID.
	 * @return ArticleFile
	 */
	function &getFile($fileId, $revision = null) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile =& $articleFileDao->getArticleFile($fileId, $revision, $this->articleId);
		return $articleFile;
	}

	/**
	 * Read a file's contents.
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function readFile($fileId, $revision = null, $output = false) {
		$articleFile =& $this->getFile($fileId, $revision);

		if (isset($articleFile)) {
			$fileType = $articleFile->getFileType();
			$filePath = $this->filesDir . $articleFile->getType() . '/' . $articleFile->getFileName();

			return parent::readFile($filePath, $output);

		} else {
			return false;
		}
	}

	/**
	 * Delete a file by ID.
	 * If no revision is specified, all revisions of the file are deleted.
	 * @param $fileId int
	 * @param $revision int (optional)
	 * @return int number of files removed
	 */
	function deleteFile($fileId, $revision = null) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$CBPPlatformDao = & DAORegistry::getDAO('CBPPlatformDAO');

		$files = array();
		if (isset($revision)) {
			$file =& $articleFileDao->getArticleFile($fileId, $revision);
			if (isset($file)) {
				$files[] = $file;
			}

		} else {
			$files =& $articleFileDao->getArticleFileRevisions($fileId);
		}

		foreach ($files as $f) {
			parent::deleteFile($this->filesDir . $f->getType() . '/' . $f->getFileName());
			
			//%CBP% Remove object datastreams
			//TODO: Check with multiple file revisions in foreach above
			$objectInfo = $CBPPlatformDao->getFedoraObjectInformation($f->getFileId());
			$digitalObject = new CBPPlatformDigitalObject();
			$digitalObject->setPID($objectInfo['fedora_pid']);
			$digitalObject->setNamespace($objectInfo['fedora_namespace']);
			$digitalObject->setDatastream(Array($objectInfo['fedora_dsid'] => Array ()));
			if ($f->getType() != "supp") {
				// FIXME: the below causes an error when deleting a journal/imprint
				//$digitalObject->setDatastream(Array($objectInfo['fedora_dsid'] . "02" => Array ()));
				//$digitalObject->setDatastream(Array($objectInfo['fedora_dsid'] . "03" => Array ()));
				//$digitalObject->setDatastream(Array($objectInfo['fedora_dsid'] . "04" => Array ()));
			}
			//if ($digitalObject->deleteDatastreams()) { 
				//do nothing
			//}
		}

		$articleFileDao->deleteArticleFileById($fileId, $revision);

		return count($files);
	}

	/**
	 * Delete the entire tree of files belonging to an article.
	 */
	function deleteArticleTree() {
		parent::rmtree($this->filesDir);
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $revision int the revision of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $revision = null, $inline = false, $format = "docx", $issueId = 0, $articleId = 0) {
		//%CBP% if we are passed a DSID and PID
		if (intval($fileId) == false && intval($revision) == false) {
			$CBPPlatformDao = & DAORegistry::getDAO('CBPPlatformDAO');
			$pid = $fileId;
			$dsid = $revision;

			if ($issueId != 0) { 
				$issueDao = & DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getIssueById($issueId);
				$label = $issue->getIssueTitle();
				switch ($dsid) {
					case 'content' :
						$label .= ".pdf";
					break;
					case 'content02' :
						$label .= ".epub";
					break;
					case 'content03' :
						$label .= ".mobi";
					break;
				}
			} elseif ($articleId !=0) { 
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				$article = $articleDao->getArticle($articleId);
				$label = $article->getArticleTitle();
				switch ($dsid) {
					case 'content' :
						$label .= ".docx";
					break;
					case 'content02' :
						$label .= ".pdf";
					break;
					case 'content03' :
						$label .= ".epub";
					break;
					case 'content04' :
						$label .= ".mobi";
					break;
				}
			}
			$filePath = Config::getVar('general', 'repository_url');
			$filePath = "$filePath/" . $pid . "/datastreams/" . $dsid . "/content";
		} else {
			$articleFile =& $this->getFile($fileId, $revision);
			if (isset($articleFile)) {
				$fileType = $articleFile->getFileType();
				$filePath = $this->filesDir . $articleFile->getType() . '/' . $articleFile->getFileName();
				return parent::downloadFile($filePath, $fileType, $inline);
			} else {
				return false;
			}
		}
		parent::downloadFile($filePath, null, null, $label, true);
	}

	/**
	 * View a file inline (variant of downloadFile).
	 * @see ArticleFileManager::downloadFile
	 */
	function viewFile($fileId, $revision = null) {
		$this->downloadFile($fileId, $revision, true);
	}

	/**
	 * Copies an existing file to create a review file.
	 * @param $originalFileId int the file id of the original file.
	 * @param $originalRevision int the revision of the original file.
	 * @param $destFileId int the file id of the current review file
	 * @return int the file id of the new file.
	 */
	function copyToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_REVIEW, $destFileId);
	}

	/**
	 * Copies an existing file to create an editor decision file.
	 * @param $fileId int the file id of the review file.
	 * @param $revision int the revision of the review file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function copyToEditorFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_EDITOR, $destFileId);
	}

	/**
	 * Copies an existing file to create a copyedit file.
	 * @param $fileId int the file id of the editor file.
	 * @param $revision int the revision of the editor file.
	 * @return int the file id of the new file.
	 */
	function copyToCopyeditFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_COPYEDIT);
	}

	/**
	 * Copies an existing file to create a layout file.
	 * @param $fileId int the file id of the copyedit file.
	 * @param $revision int the revision of the copyedit file.
	 * @return int the file id of the new file.
	 */
	function copyToLayoutFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_LAYOUT);
	}

	/**
	 * Return type path associated with a type code.
	 * @param $type string
	 * @return string
	 */
	function typeToPath($type) {
		switch ($type) {
			case ARTICLE_FILE_PUBLIC: return 'public';
			case ARTICLE_FILE_SUPP: return 'supp';
			case ARTICLE_FILE_NOTE: return 'note';
			case ARTICLE_FILE_REVIEW: return 'submission/review';
			case ARTICLE_FILE_EDITOR: return 'submission/editor';
			case ARTICLE_FILE_COPYEDIT: return 'submission/copyedit';
			case ARTICLE_FILE_LAYOUT: return 'submission/layout';
			case ARTICLE_FILE_ATTACHMENT: return 'attachment';
			case ARTICLE_FILE_SUBMISSION: default: return 'submission/original';
		}
	}

	/**
	 * Copies an existing ArticleFile and renames it.
	 * @param $sourceFileId int
	 * @param $sourceRevision int
	 * @param $destType string
	 * @param $destFileId int (optional)
	 */
	function copyAndRenameFile($sourceFileId, $sourceRevision, $destType, $destFileId = null) {
		if (HookRegistry::call('ArticleFileManager::copyAndRenameFile', array(&$sourceFileId, &$sourceRevision, &$destType, &$destFileId, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();

		$destTypePath = $this->typeToPath($destType);
		$destDir = $this->filesDir . $destTypePath . '/';

		if ($destFileId != null) {
			$currentRevision = $articleFileDao->getRevisionNumber($destFileId);
			$revision = $currentRevision + 1;
		} else {
			$revision = 1;
		}

		$sourceArticleFile = $articleFileDao->getArticleFile($sourceFileId, $sourceRevision, $this->articleId);

		if (!isset($sourceArticleFile)) {
			return false;
		}

		$sourceDir = $this->filesDir . $sourceArticleFile->getType() . '/';

		if ($destFileId != null) {
			$articleFile->setFileId($destFileId);
		}
		$articleFile->setArticleId($this->articleId);
		$articleFile->setSourceFileId($sourceFileId);
		$articleFile->setSourceRevision($sourceRevision);
		$articleFile->setFileName($sourceArticleFile->getFileName());
		$articleFile->setFileType($sourceArticleFile->getFileType());
		$articleFile->setFileSize($sourceArticleFile->getFileSize());
		$articleFile->setOriginalFileName($sourceArticleFile->getFileName());
		$articleFile->setType($destTypePath);
		$articleFile->setDateUploaded(Core::getCurrentDate());
		$articleFile->setDateModified(Core::getCurrentDate());
		$articleFile->setRound($this->article->getCurrentRound()); // FIXME This field is only applicable for review files?
		$articleFile->setRevision($revision);
		//%CBP% set fedora/repository information
		$articleFile->setFedoraNamespace($sourceArticleFile->getFedoraNamespace());
		$articleFile->setFedoraPid($sourceArticleFile->getFedoraPid());
		$articleFile->setFedoraDsid($sourceArticleFile->getFedoraDsid());
		
		$fileId = $articleFileDao->insertArticleFile($articleFile);

		// Rename the file.
		$fileExtension = $this->parseFileExtension($sourceArticleFile->getFileName());
		$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'-'.$destType.'.'.$fileExtension;

		if (!$this->fileExists($destDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($destDir);
		}

		copy($sourceDir.$sourceArticleFile->getFileName(), $destDir.$newFileName);

		$articleFile->setFileName($newFileName);
		$articleFileDao->updateArticleFile($articleFile);
		
		return $fileId;
	}

	/**
	 * PRIVATE routine to generate a dummy file. Used in handleUpload.
	 * @param $article object
	 * @return object articleFile
	 */
	function &generateDummyFile(&$article) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();
		$articleFile->setArticleId($article->getId());
		$articleFile->setFileName('temp');
		$articleFile->setOriginalFileName('temp');
		$articleFile->setFileType('temp');
		$articleFile->setFileSize(0);
		$articleFile->setType('temp');
		$articleFile->setDateUploaded(Core::getCurrentDate());
		$articleFile->setDateModified(Core::getCurrentDate());
		$articleFile->setRound(0);
		$articleFile->setRevision(1);

		$articleFile->setFileId($articleFileDao->insertArticleFile($articleFile));

		return $articleFile;
	}

	/**
	 * PRIVATE routine to remove all prior revisions of a file.
	 */
	function removePriorRevisions($fileId, $revision) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$revisions = $articleFileDao->getArticleFileRevisions($fileId);
		foreach ($revisions as $revisionFile) {
			if ($revisionFile->getRevision() != $revision) {
				$this->deleteFile($fileId, $revisionFile->getRevision());
			}
		}
	}

	/**
	 * PRIVATE routine to generate a filename for an article file. Sets the filename
	 * field in the articleFile to the generated value.
	 * @param $articleFile The article to generate a filename for
	 * @param $type The type of the article (e.g. as supplied to handleUpload)
	 * @param $originalName The name of the original file
	 */
	function generateFilename(&$articleFile, $type, $originalName) {
		$extension = $this->parseFileExtension($originalName);
		$newFileName = $articleFile->getArticleId().'-'.$articleFile->getFileId().'-'.$articleFile->getRevision().'-'.$type.'.'.$extension;
		$articleFile->setFileName($newFileName);
		return $newFileName;
	}

	/**
	 * PRIVATE routine to upload the file, add it to the database, convert into proof formats and ingest in repository.
	 * @param $fileName string index into the $_FILES array
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleUpload($fileName, $type, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('ArticleFileManager::handleUpload', array(&$fileName, &$type, &$fileId, &$overwrite, &$result))) return $result;

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';
		
		//%CBP% get the most recent supplementary files - this is used later for upating supplmentary file datastreams	
		$CBPPlatformDao =& DAORegistry::getDAO('CBPPlatformDAO');
		$mostRecentSupplementaryFiles = $CBPPlatformDao->getMostRecentSupplementaryFiles($this->articleId, $fileId);

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$articleFile =& $this->generateDummyFile($this->article);
		} else {
			$dummyFile = false;
			$articleFile = new ArticleFile();
			$articleFile->setRevision($articleFileDao->getRevisionNumber($fileId)+1);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileId($fileId);
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
		}
		
		$article = $articleDao->getArticle($articleFile->getArticleId());

		$articleFile->setFileType($_FILES[$fileName]['type']);
		$articleFile->setFileSize($_FILES[$fileName]['size']);
		$articleFile->setOriginalFileName(ArticleFileManager::truncateFileName($_FILES[$fileName]['name'], 127));
		$articleFile->setType($typePath);
		$articleFile->setRound($this->article->getCurrentRound());

		$newFileName = $this->generateFilename($articleFile, $type, $this->getUploadedFileName($fileName));
		
		if (!$this->uploadFile($fileName, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());
			return false;
		}

		if ($dummyFile) $articleFileDao->updateArticleFile($articleFile);
		else $articleFileDao->insertArticleFile($articleFile);

		if ($overwrite) $this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());
	
		//%CBP% Upload file to Repository and update database with PID/DsId
		$epubconvert = new CBPPlatformEpubConvert();
		$mobiconvert = new CBPPlatformMobiConvert();
		$pdfconvert = new CBPPlatformPdfConvert();
		
		if ($type == "SP") { //if we're uploading a supplementary file
			if ($fileId) {
				$dsSuffix = str_replace("content", "", $mostRecentSupplementaryFiles[0]['fedora_dsid']);
			} else {
				$mostRecentSupplementaryFiles = $CBPPlatformDao->getMostRecentSupplementaryFiles($this->articleId);
			}
			if (count($mostRecentSupplementaryFiles) == 1 && !$fileId) {
				$dsSuffix = "05"; 
			} elseif (count($mostRecentSupplementaryFiles) > 1 && !$fileId) {
				$mostRecentSupplementaryFiles = $CBPPlatformDao->getMostRecentSupplementaryFiles($this->articleId);
				$dsId = str_replace("content", "", $mostRecentSupplementaryFiles[1]['fedora_dsid']);
				$dsId = (int)$dsId;
				$dsId++;
				$dsSuffix = sprintf("%02d", $dsId);
			}
		}
		if(mime_content_type($dir.$newFileName) == "application/zip" && $type != "SP") {
			$ebookFileName = str_replace($this->parseFileExtension($newFileName), "", $newFileName);
			$epubArgs = array("author" => $this->article->getAuthorString(), "title" => $this->article->getArticleTitle(), "description" => $this->article->getArticleAbstract());
			
			$epubconvert->createEpub($dir, $newFileName, $dir . $ebookFileName . "epub", null, $this->article->getJournalId(), $epubArgs);
			$mobiconvert->createMobi($dir, $newFileName, $dir . $ebookFileName . "mobi", null, $this->article->getJournalId(), $epubArgs);
			$pdfconvert->createPdf($dir, $newFileName, $dir . $ebookFileName . "pdf", null, $this->article->getJournalId(), $epubArgs);
			
			$digitalObject = new CBPPlatformDigitalObject();
			$digitalObject->setPID($this->articleId); //we use articleId as the unique identifier -- this should correspond with articleId in OJS database
			$digitalObject->setLabel($article->getArticleTitle()); //label could be anything
			$digitalObject->setNamespace(Config::getVar('general', 'component_namespace'));
			$digitalObject->setPrimaryFormat("application/vnd.openxmlformats-officedocument.wordprocessingml.document");
			
			$ext = $this->parseFileExtension($newFileName);
			$label = str_replace(".$ext", "", $newFileName);
			
			$ext = "docx";
			$digitalObject->setDatastream(Array($dsId = $dsPrefix . "content" => Array ("id" => $dsPrefix . "content", "file" => "$dir$newFileName", "label" => $label . ".$ext", "mimetype" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "filetype" => $ext)));
			//
			$ext = "pdf";
			$digitalObject->setDatastream(Array($dsPrefix . "content02" => Array ("id" => $dsPrefix . "content02", "file" => "$dir$ebookFileName" . "$ext", "label" => $label . ".$ext", "mimetype" => "application/pdf", "filetype" => $ext)));
			//
			$ext = "epub";
			$digitalObject->setDatastream(Array($dsPrefix . "content03" => Array ("id" => $dsPrefix . "content03", "file" => "$dir$ebookFileName" . "$ext", "label" => $label . ".$ext", "mimetype" => "application/epub+zip", "filetype" => $ext)));
			///
			$ext = "mobi";
			$digitalObject->setDatastream(Array($dsPrefix . "content04" => Array ("id" => $dsPrefix . "content04", "file" => "$dir$ebookFileName" . "$ext", "label" => $label . ".$ext", "mimetype" => "application/x-mobipocket-ebook", "filetype" => $ext)));
		} else { 
			$digitalObject = new CBPPlatformDigitalObject();
			$digitalObject->setPID($this->articleId); //we use articleId as the unique identifier -- this should correspond with articleId in OJS database
			$digitalObject->setLabel($article->getArticleTitle()); //label could be anything
			$digitalObject->setNamespace(Config::getVar('general', 'component_namespace'));
			if ($type != "SP") $digitalObject->setPrimaryFormat(mime_content_type($dir.$newFileName));
			$label = $newFileName;
			$path_parts = pathinfo("$dir$newFileName");
			
			$digitalObject->setDatastream(Array($dsId = $dsPrefix . "content" . $dsSuffix => Array ("id" => $dsPrefix . "content" . $dsSuffix, "file" => "$dir$newFileName", "label" => $label, "mimetype" => $this->returnMIMEType($dir.$newFileName), "filetype" => $path_parts['extension'])));
		}

		$obj = new SimpleXMLElement($digitalObject->readObjectProperties());		
		foreach ($obj as $result) {
			$noObjects = $result->count();
		}
		if ($noObjects==0) {
			$digitalObject->createObject();
		}
		$digitalObject->createObjectDatastreams();
		if ($type != "SP") $digitalObject->makeHydraCompliant(null, $article->getArticleTitle(), $article->getAuthorString());
		if ($digitalObject->obj->PID != "" && $dsId != "") {
			$CBPPlatformDao->setFedoraObjectInformation($digitalObject->obj->PID, $digitalObject->obj->ns, $dsId, $articleFile->getFileId(), $articleFile->getRevision());
		}

		return $articleFile->getFileId();
	}
	
	/**
	 * Function to return the MIMETYPE of a file, first by parsing file extension and then returning mime_content_type
	 * @param str $filename
	 * @return str
	 */
	function returnMIMEType($filename) {
	
        $path_parts = pathinfo($filename);
		
        switch($path_parts['extension'])
        {
            case "js" :
                return "application/x-javascript";

            case "json" :
                return "application/json";

            case "jpg" :
            case "jpeg" :
            case "jpe" :
                return "image/jpg";

            case "png" :
            case "gif" :
            case "bmp" :
            case "tiff" :
                return "image/".strtolower($fileSuffix[1]);

            case "css" :
                return "text/css";

            case "xml" :
                return "application/xml";

            case "docx" :
            	return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
            case "doc" :
                return "application/msword";

            case "xls" :
            case "xlt" :
            case "xlm" :
            case "xld" :
            case "xla" :
            case "xlc" :
            case "xlw" :
            case "xll" :
                return "application/vnd.ms-excel";

            case "ppt" :
            case "pps" :
                return "application/vnd.ms-powerpoint";

            case "rtf" :
                return "application/rtf";

            case "pdf" :
                return "application/pdf";

            case "html" :
            case "htm" :
            case "php" :
                return "text/html";

            case "txt" :
                return "text/plain";

            case "mpeg" :
            case "mpg" :
            case "mpe" :
                return "video/mpeg";

            case "mp3" :
                return "audio/mpeg3";

            case "wav" :
                return "audio/wav";

            case "aiff" :
            case "aif" :
                return "audio/aiff";

            case "avi" :
                return "video/msvideo";

            case "wmv" :
                return "video/x-ms-wmv";

            case "mov" :
                return "video/quicktime";

            case "zip" :
                return "application/zip";

            case "tar" :
                return "application/x-tar";

            case "swf" :
                return "application/x-shockwave-flash";

            default :
	            if(function_exists("mime_content_type"))
	            {
	                $fileSuffix = mime_content_type($filename);
	            }
	            return $fileSuffix;
        }
    }

	/**
	 * PRIVATE routine to write an article file and add it to the database.
	 * @param $fileName original filename of the file
	 * @param $contents string contents of the file to write
	 * @param $mimeType string the mime type of the file
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleWrite($fileName, &$contents, $mimeType, $type, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('ArticleFileManager::handleWrite', array(&$fileName, &$contents, &$mimeType, &$fileId, &$overwrite, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$articleFile =& $this->generateDummyFile($this->article);
		} else {
			$dummyFile = false;
			$articleFile = new ArticleFile();
			$articleFile->setRevision($articleFileDao->getRevisionNumber($fileId)+1);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileId($fileId);
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
		}

		$articleFile->setFileType($mimeType);
		$articleFile->setFileSize(strlen($contents));
		$articleFile->setOriginalFileName(ArticleFileManager::truncateFileName($fileName, 127));
		$articleFile->setType($typePath);
		$articleFile->setRound($this->article->getCurrentRound());

		$newFileName = $this->generateFilename($articleFile, $type, $fileName);

		if (!$this->writeFile($dir.$newFileName, $contents)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());

			return false;
		}

		if ($dummyFile) $articleFileDao->updateArticleFile($articleFile);
		else $articleFileDao->insertArticleFile($articleFile);

		if ($overwrite) $this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}

	/**
	 * PRIVATE routine to copy an article file and add it to the database.
	 * @param $url original filename/url of the file
	 * @param $mimeType string the mime type of the file
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleCopy($url, $mimeType, $type, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('ArticleFileManager::handleCopy', array(&$url, &$mimeType, &$type, &$fileId, &$overwrite, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$articleFile =& $this->generateDummyFile($this->article);
		} else {
			$dummyFile = false;
			$articleFile = new ArticleFile();
			$articleFile->setRevision($articleFileDao->getRevisionNumber($fileId)+1);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileId($fileId);
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
		}

		$articleFile->setFileType($mimeType);
		$articleFile->setOriginalFileName(ArticleFileManager::truncateFileName(basename($url), 127));
		$articleFile->setType($typePath);
		$articleFile->setRound($this->article->getCurrentRound());

		$newFileName = $this->generateFilename($articleFile, $type, $articleFile->getOriginalFileName());

		if (!$this->copyFile($url, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());
			return false;
		}

		$articleFile->setFileSize(filesize($dir.$newFileName));

		if ($dummyFile) $articleFileDao->updateArticleFile($articleFile);
		else $articleFileDao->insertArticleFile($articleFile);

		if ($overwrite) $this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}

	/**
	 * Copy a temporary file to an article file.
	 * @param TemporaryFile
	 * @return int the file ID (false if upload failed)
	 */
	function temporaryFileToArticleFile(&$temporaryFile, $type, $assocId = null) {
		if (HookRegistry::call('ArticleFileManager::temporaryFileToArticleFile', array(&$temporaryFile, &$type, &$assocId, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		$articleFile =& $this->generateDummyFile($this->article);
		$articleFile->setFileType($temporaryFile->getFileType());
		$articleFile->setOriginalFileName($temporaryFile->getOriginalFileName());
		$articleFile->setType($typePath);
		$articleFile->setRound($this->article->getCurrentRound());
		$articleFile->setAssocId($assocId);

		$newFileName = $this->generateFilename($articleFile, $type, $articleFile->getOriginalFileName());

		if (!$this->copyFile($temporaryFile->getFilePath(), $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());

			return false;
		}

		$articleFile->setFileSize(filesize($dir.$newFileName));
		$articleFileDao->updateArticleFile($articleFile);
		$this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}
}

?>
