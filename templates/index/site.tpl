{**
 * site.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 * $Id$
 *}
{strip}
{if $siteTitle}
	{assign var="pageTitleTranslated" value=$siteTitle}
{/if}
{include file="common/header.tpl"}
{/strip}

{if $intro}{$intro|nl2br}{/if}

<div style="float: left; width: 100%" class="shelf">
	<h3 class="shelfHeading">New on {$siteTitle}</h3>
	{if $newIssues|@count gt 0}
		<ul>
		{foreach from=$newIssues key=index item=newIssue}
			<div class="featuredItem"><img src="{$newIssue.cover}" height="90px" /><p><a href="{$baseUrl}/index.php/{$newIssue.path}/issue/view/{$newIssue.issue_id}" title="{$newIssue.setting_value}"><strong>{$newIssue.setting_value}</strong></a><br />from <a href="{$baseUrl}/index.php/{$newIssue.path}/">{$newIssue.journal}</a></p></div>
		{/foreach}
		</ul>
	{else}
		<p>No new items found</p>
	{/if}
</div>

<div style="float: left; width: 800px;">
	<h3 class="shelfHeading">Imprints on {$siteTitle}:</h3>
</div>
{iterate from=journals item=journal}

	{assign var="displayHomePageImage" value=$journal->getLocalizedSetting('homepageImage')}
	{assign var="displayHomePageLogo" value=$journal->getLocalizedPageHeaderLogo(true)}
	{assign var="displayPageHeaderLogo" value=$journal->getLocalizedPageHeaderLogo()}

	<div style="clear:left;" class="journal">
	{if $displayHomePageImage && is_array($displayHomePageImage)}
		{assign var="altText" value=$journal->getLocalizedSetting('homepageImageAltText')}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayHomePageImage.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{elseif $displayHomePageLogo && is_array($displayHomePageLogo)}
		{assign var="altText" value=$journal->getLocalizedSetting('homeHeaderLogoImageAltText')}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayHomePageLogo.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{elseif $displayPageHeaderLogo && is_array($displayPageHeaderLogo)}
		{assign var="altText" value=$journal->getLocalizedSetting('pageHeaderLogoImageAltText')}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayPageHeaderLogo.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{/if}
	</div>

	<h3>{$journal->getLocalizedTitle()|escape}</h3>
	{if $journal->getLocalizedDescription()}
		<p>{$journal->getLocalizedDescription()|nl2br}</p>
	{/if}

	<p><a href="{url journal=$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{url journal=$journal->getPath() page="issue" op="current"}" class="action">{translate key="site.journalCurrent"}</a> | <a href="{url journal=$journal->getPath() page="user" op="register"}" class="action">{translate key="site.journalRegister"}</a></p>
{/iterate}

{include file="common/footer.tpl"}