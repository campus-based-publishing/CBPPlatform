{**
 * index.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal management index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.journalManagement"}
{include file="common/header.tpl"}
{/strip}
<div id="managementPages">
<h3>{translate key="manager.managementPages"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="payments"}">{translate key="manager.payments"}</a></li>
</ul>
</div>
<div id="managerUsers">
<h3>{translate key="manager.users"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="all"}">{translate key="manager.people.allEnrolledUsers"}</a></li>
	<li>&#187; <a href="{url op="enrollSearch"}">{translate key="manager.people.allSiteUsers"}</a></li>
	<li>&#187; <a href="{url op="showNoRole"}">{translate key="manager.people.showNoRole"}</a></li>
	{url|assign:"managementUrl" page="manager"}
	<li>&#187; <a href="{url op="createUser" source=$managementUrl}">{translate key="manager.people.createUser"}</a></li>
	<li>&#187; <a href="{url op="mergeUsers"}">{translate key="manager.people.mergeUsers"}</a></li>
	{call_hook name="Templates::Manager::Index::Users"}
</ul>
</div>
<div id="managerRoles">
<h3>{translate key="manager.roles"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="managers"}">{translate key="user.role.managers"}</a></li>
	<li>&#187; <a href="{url op="people" path="editors"}">{translate key="user.role.editors"}</a></li>
	{if $roleSettings.useLayoutEditors}
		<li>&#187; <a href="{url op="people" path="layoutEditors"}">{translate key="user.role.layoutEditors"}</a></li>
	{/if}
	{if $roleSettings.useCopyeditors}
		<li>&#187; <a href="{url op="people" path="copyeditors"}">{translate key="user.role.copyeditors"}</a></li>
	{/if}
	{if $roleSettings.useProofreaders}
		<li>&#187; <a href="{url op="people" path="proofreaders"}">{translate key="user.role.proofreaders"}</a></li>
	{/if}
	<li>&#187; <a href="{url op="people" path="reviewers"}">{translate key="user.role.reviewers"}</a></li>
	<li>&#187; <a href="{url op="people" path="authors"}">{translate key="user.role.authors"}</a></li>
	<li>&#187; <a href="{url op="people" path="readers"}">{translate key="user.role.readers"}</a></li>
	{call_hook name="Templates::Manager::Index::Roles"}
</ul>
</div>
{include file="common/footer.tpl"}

