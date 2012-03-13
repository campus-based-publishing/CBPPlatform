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

{foreach from=$groups item=group}
<div id="group">
	<h4>{$group->getLocalizedTitle()}</h4>
	{assign var=groupId value=$group->getId()}
	{assign var=members value=$teamInfo[$groupId]}

	<ol class="editorialTeam">
		{foreach from=$members item=member}
			{assign var=user value=$member->getUser()}
			<div id="member"><li><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$user->getId()}')">{$user->getFullName()|escape}</a>{if $user->getLocalizedAffiliation()}, {$user->getLocalizedAffiliation()|escape}{/if}{if $user->getCountry()}{assign var=countryCode value=$user->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li></div>
		{/foreach}{* $members *}
	</ol>
</div>
{/foreach}{* $groups *}

{include file="common/footer.tpl"}

