{**
 * editorialTeam.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}
{/strip}

<div id="editorialTeam">
{if count($editors) > 0}
	<div id="editors">
	{if count($editors) == 1}
		<h4>{translate key="user.role.editor"}</h4>
	{else}
		<h4>{translate key="user.role.editors"}</h4>
	{/if}

	<ol class="editorialTeam">
		{foreach from=$editors item=editor}
			<li><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$editor->getId()}')">{$editor->getFullName()|escape}</a>{if $editor->getLocalizedAffiliation()}, {$editor->getLocalizedAffiliation()|escape}{/if}{if $editor->getCountry()}{assign var=countryCode value=$editor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
	</div>
{/if}

{if count($sectionEditors) > 0}
	<div id="sectionEditors">
	{if count($sectionEditors) == 1}
		<h4>{translate key="user.role.sectionEditor"}</h4>
	{else}
		<h4>{translate key="user.role.sectionEditors"}</h4>
	{/if}

	<ol class="editorialTeam">
		{foreach from=$sectionEditors item=sectionEditor}
			<li><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$sectionEditor->getId()}')">{$sectionEditor->getFullName()|escape}</a>{if $sectionEditor->getLocalizedAffiliation()}, {$sectionEditor->getLocalizedAffiliation()|escape}{/if}{if $sectionEditor->getCountry()}{assign var=countryCode value=$sectionEditor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
	</div>
{/if}

{if count($layoutEditors) > 0}
	<div id="layoutEditors">
	{if count($layoutEditors) == 1}
		<h4>{translate key="user.role.layoutEditor"}</h4>
	{else}
		<h4>{translate key="user.role.layoutEditors"}</h4>
	{/if}

	<ol class="editorialTeam">
		{foreach from=$layoutEditors item=layoutEditor}
			<li><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$layoutEditor->getId()}')">{$layoutEditor->getFullName()|escape}</a>{if $layoutEditor->getLocalizedAffiliation()}, {$layoutEditor->getLocalizedAffiliation()|escape}{/if}{if $layoutEditor->getCountry()}{assign var=countryCode value=$layoutEditor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
	</div>
{/if}

{if count($copyEditors) > 0}
	<div id="copyEditors">
	{if count($copyEditors) == 1}
		<h4>{translate key="user.role.copyeditor"}</h4>
	{else}
		<h4>{translate key="user.role.copyeditors"}</h4>
	{/if}

	<ol class="editorialTeam">
		{foreach from=$copyEditors item=copyEditor}
			<li><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$copyEditor->getId()}')">{$copyEditor->getFullName()|escape}</a>{if $copyEditor->getLocalizedAffiliation()}, {$copyEditor->getLocalizedAffiliation()|escape}{/if}{if $copyEditor->getCountry()}{assign var=countryCode value=$copyEditor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
	</div>
{/if}

{if count($proofreaders) > 0}
	<div id="proofreaders">
	{if count($proofreaders) == 1}
		<h4>{translate key="user.role.proofreader"}</h4>
	{else}
		<h4>{translate key="user.role.proofreaders"}</h4>
	{/if}

	<ol class="editorialTeam">
		{foreach from=$proofreaders item=proofreader}
			<li><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$proofreader->getId()}')">{$proofreader->getFullName()|escape}</a>{if $proofreader->getLocalizedAffiliation()}, {$proofreader->getLocalizedAffiliation()|escape}{/if}{if $proofreader->getCountry()}{assign var=countryCode value=$proofreader->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
	</div>
{/if}
</div>

{include file="common/footer.tpl"}

