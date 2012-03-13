{**
 * issue.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site issue administration index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.issueManagement"}
{include file="common/header.tpl"}
{/strip}

<h3>Books for Publication</h3>
<p>Only show books from:</p>
<form action="" method="post">
	<select name="filter">
	<option value="0">Show all...</option>
	{foreach from=$journalsWithPendingIssues key=title item=journalId}
		<option value="{$journalId}" {if $filter == $journalId}selected="selected"{/if}>{$title}</option>
	{/foreach}
	</select>
	<input type="submit" value="Filter" class="button" />
</form>

<div class="separator"></div>

{foreach from=$pendingIssues item=pendingIssue}
<h4>{$pendingIssue->getIssueTitle()|escape}</h4>
<h5>Inside this Book</h5>
{assign var="issueId" value=$pendingIssue->getIssueId()}
<table width="100%" class="listing" id="issueToc-{$sectionKey|escape}">
	<tr class="heading" valign="bottom">
		<td>{translate key="article.authors"}</td>
		<td>{translate key="article.title"}</td>
		<td>Description</td>
		<!--<td width="5%">{translate key="editor.issues.proofed"}</td>-->
	</tr>
	{foreach from=$publishedArticles.$issueId item=article}
	<tr>
		<td>{$article->getAuthorString()}</td>
		<td>{$article->getArticleTitle()}</td>
		<td>{$article->getArticleAbstract()}</td>
	</tr>
	{/foreach}
</table>
	<div class="separator"></div>
	<h5>Reviewer Comments</h5>
	{if $issueComments|@count gt 0}
		{foreach from=$issueComments.$issueId item=comment}
			<p><strong>On {$comment.date_commented}, {$user->getUserFullName($comment.reviewer_id)} 
			{if $comment.reviewer_id == 1} (Press Editor-in-Chief) {/if}commented:</strong></p>
			<p>{$comment.comment}</p>
		{/foreach}
	{/if}
	<div class="separator"></div>
	<h6>Enter your own comments</h6>
	<form action="" method="post">
		<input type="hidden" name="issueId" value="{$issueId}" />
		<textarea rows="5" cols="75" name="comment"></textarea><br />
		<input type="submit" class="button" value="Post Comment" />
	</form>
	<div class="separator"></div>
<p><strong><a href="{url page="article" op="download"}/{$issueObjects.$issueId.pid}/{$issueObjects.$issueId.dsid}02/{$pendingIssue->getIssueId()}" class="file">Download proof [ePub Format]</a></strong> | <strong><a href="{url page="article" op="download"}/{$issueObjects.$issueId.pid}/{$issueObjects.$issueId.dsid}/{$pendingIssue->getIssueId()}" class="file">Download proof [PDF Format]</a></strong> | <strong><a href="{url page="article" op="download"}/{$issueObjects.$issueId.pid}/{$issueObjects.$issueId.dsid}03/{$pendingIssue->getIssueId()}" class="file">Download proof [Amazon Kindle Format]</a></strong></p>
<p><a href="?publish={$pendingIssue->getIssueId()}"><input type="button" value="Publish" class="button" /></a></p>
<div class="separator"></div>
{/foreach}

{include file="common/footer.tpl"}