{**
 * submission.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission summary.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.summary" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.summary"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{url op="submission" path=$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{url op="submissionReview" path=$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<!--<li><a href="{url op="submissionEditing" path=$submission->getArticleId()}">{translate key="submission.editing"}</a></li>-->
</ul>

{include file="author/submission/management.tpl"}

{if $authorFees}
<div class="separator"></div>

{include file="author/submission/authorFees.tpl"}
{/if}

<div class="separator"></div>
{if $proofView}
	<h3>Author Proofs</h3>
	<p>Your submission has been approved by an Editor. View proofs of your submission in different formats below:</p>
	<p><a href="{url op="downloadFile"}/{$repositoryObjectPid}/{$repositoryObjectDsid}03/0/{$submission->getArticleId()}" class="file">ePub Proof Download</a></p>
	<p><a href="{url op="downloadFile"}/{$repositoryObjectPid}/{$repositoryObjectDsid}04/0/{$submission->getArticleId()}" class="file">Amazon Kindle Proof Download</a></p>
	<p><a href="{url op="downloadFile"}/{$repositoryObjectPid}/{$repositoryObjectDsid}02/0/{$submission->getArticleId()}" class="file">PDF Proof Download</a></p>
	<p><a href="{url op="downloadFile"}/{$repositoryObjectPid}/{$repositoryObjectDsid}/0/{$submission->getArticleId()}" class="file">Docx Proof Download</a></p>
	<table>
		<tr valign="top">
			<td class="label" width="40%">
				Tweak the look-and-feel of your submission by uploading new version of manuscript for publication
			</td>
			<td class="value" width="80%">
				<form method="post" action="{url op="uploadRevisedVersion"}" enctype="multipart/form-data">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
					<input type="file" name="upload" class="uploadField" />
					<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
				</form>
			</td>
		</tr>
	</table>
<div class="separator"></div>
{/if}

{include file="author/submission/status.tpl"}

<div class="separator"></div>

{include file="submission/metadata/metadata.tpl"}

{include file="common/footer.tpl"}