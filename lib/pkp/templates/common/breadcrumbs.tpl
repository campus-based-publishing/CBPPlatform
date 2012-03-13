{**
 * breadcrumbs.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Breadcrumbs
 *
 *}
<div id="breadcrumb">
	<a href="{$baseUrl}">{translate key="navigation.baseHome"}</a> &gt;
	{if $homepage != true}
		{if $currentJournal && $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			{if $journalHomepage == true}
				<strong><a href="{url context=$homeContext page="index"}">{$siteTitle} {translate key="navigation.home"}</a></strong>
			{else}
				<a href="{url context=$homeContext page="index"}">{$siteTitle} {translate key="navigation.home"}</a> &gt;
			{/if}
		{/if}
		{foreach from=$pageHierarchy item=hierarchyLink}
			{if $hierarchyLink[1] != "archive.archives" && $hierarchyLink[1] != "current.current"}
				<a href="{$hierarchyLink[0]|escape}" class="hierarchyLink">{if not $hierarchyLink[2]}{translate key=$hierarchyLink[1]}{else}{$hierarchyLink[1]|escape}{/if}</a> &gt;
			{/if}
		{/foreach}
		{* Disable linking to the current page if the request is a post (form) request. Otherwise following the link will lead to a form submission error. *}
		{if $journalHomepage != true}
			{if $requiresFormRequest}<span class="current">{else}<a href="{$currentUrl|escape}" class="current">{/if}{$pageCrumbTitleTranslated}{if $requiresFormRequest}</span>{else}</a>{/if}
		{/if}
	{/if}
</div>
