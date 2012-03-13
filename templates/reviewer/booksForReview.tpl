{**
 * booksForReview.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="reviewer.booksForReview"}
{include file="common/header.tpl"}

{if $pendingIssues != 0}
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
	<h5>Comments</h5>
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
	{/foreach}
{else}
	<p>No books to comment on!</p>
{/if}

{include file="common/footer.tpl"}