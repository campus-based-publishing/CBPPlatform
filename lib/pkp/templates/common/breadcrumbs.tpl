{**
 * breadcrumbs.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Breadcrumbs
 *
 *}
 <!-- CBP converting to UL -->
<ul class="breadcrumb">
	<li><a href="{$baseUrl}">{translate key="navigation.baseHome"}</a></li> <span class="divider">/</span>
	{if $homepage != true}
		{if $currentJournal && $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			{if $journalHomepage == true}
				<li><strong><a href="{url context=$homeContext page="index"}">{$siteTitle} {translate key="navigation.home"}</a></strong></li> <span class="divider">/</span>
			{else}
				<li><a href="{url context=$homeContext page="index"}">{$siteTitle} {translate key="navigation.home"}</a></li> <span class="divider">/</span>
			{/if}
		{/if}
		{foreach from=$pageHierarchy item=hierarchyLink}
			{if $hierarchyLink[1] != "archive.archives" && $hierarchyLink[1] != "current.current"}
				<li><a href="{$hierarchyLink[0]|escape}" class="hierarchyLink">{if not $hierarchyLink[2]}{translate key=$hierarchyLink[1]}{else}{$hierarchyLink[1]|escape}{/if}</a></li> <span class="divider">/</span>
			{/if}
		{/foreach}
		{* Disable linking to the current page if the request is a post (form) request. Otherwise following the link will lead to a form submission error. *}
		{if $journalHomepage != true}
			{if $requiresFormRequest}<li><span class="current">{else}<a href="{$currentUrl|escape}" class="current">{/if}{$pageCrumbTitleTranslated}{if $requiresFormRequest}</span>{else}</a></li> <span class="divider">/</span>{/if}
		{/if}
	{/if}
</ul>
