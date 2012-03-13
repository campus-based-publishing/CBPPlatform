{**
 * view.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue -- This displays the issue TOC or title page, as appropriate,
 * *without* header or footer HTML (see viewPage.tpl)
 *
 * $Id$
 *}
{if $subscriptionRequired && $showGalleyLinks && $showToc}
	<div id="accessKey">
		<img src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
		{translate key="reader.openAccess"}&nbsp;
		<img src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
		{if $purchaseArticleEnabled}
			{translate key="reader.subscriptionOrFeeAccess"}
		{else}
			{translate key="reader.subscriptionAccess"}
		{/if}
	</div>
{/if}
{if !$showToc && $issue}
	{if $issueId}
		{url|assign:"currentUrl" page="issue" op="view" path=$issueId|to_array:"showToc"}
	{else}
		{url|assign:"currentUrl" page="issue" op="current" path="showToc"}
	{/if}
	<br />
	{if $coverPagePath}<div id="issueCoverImage"><img src="{$coverPagePath|escape}{$fileName|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/></div>{/if}
	<div id="issueCoverDescription">{$issue->getLocalizedCoverPageDescription()|strip_unsafe_html|nl2br}</div>

	<div id="issueDescription">{$issue->getLocalizedDescription()|strip_unsafe_html|nl2br}</div>
	
	<p><strong><a href="{url page="article" op="download"}/{$repositoryObjectPid}/{$repositoryObjectDsid}/{$issue->getIssueId()}" class="file">Download this book [PDF Format]</a></strong></p>
	<p><strong><a href="{url page="article" op="download"}/{$repositoryObjectPid}/{$repositoryObjectDsid}02/{$issue->getIssueId()}" class="file">Download this book [ePub Format]</a></strong></p>
	<p><strong><a href="{url page="article" op="download"}/{$repositoryObjectPid}/{$repositoryObjectDsid}03/{$issue->getIssueId()}" class="file">Download this book [Amazon Kindle Format]</a></strong></p>
	
	<p><em>To view other books from this Imprint, visit the Imprint's <a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a>.</em></p>
	{if $atomistic == 0}
		<h3>{translate key="issue.toc"}</h3>
		{include file="issue/issue.tpl"}
	{/if}
	{if $supplementaryFiles}
	<p><strong>Artefacts associated with this collection:</strong>
		<ul>
		{foreach from=$supplementaryFiles key=myId item=i}
			<li><a href="{url page="article" op="download"}/{$i.articleId}/{$i.fileId}" class="file">{$i.title}</a> from {$i.articleTitle}</li>
		{/foreach}
		</ul>
	{/if}
	{if $isbn}<p><strong>ISBN: {$isbn}</strong></p>{/if}
{else}
	{translate key="current.noCurrentIssueDesc"}
{/if}
