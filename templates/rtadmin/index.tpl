{**
 * index.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reading Tools Administrator index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="rt.readingTools"}
{include file="common/header.tpl"}
{/strip}

<div id="rtAdminStatus">
<h3>{translate key="rt.admin.status"}</h3>
<p>
{translate key="rt.admin.readingToolsEnabled"}: {if $enabled}{translate key="common.enabled"}{else}{translate key="common.disabled"}{/if}<br/>
{translate key="rt.admin.selectedVersion"}: {if $versionTitle}{$versionTitle|escape}{else}{translate key="common.disabled"}{/if}
</p>
</div>

<div id="rtAdminConfig">
<h3>{translate key="rt.admin.configuration"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="settings"}">{translate key="rt.admin.settings"}</a></li>
	<li>&#187; <a href="{url op="versions"}">{translate key="rt.versions"}</a></li>
</ul>
</div>

<div id="rtAdminManage">
<h3>{translate key="rt.admin.management"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="validateUrls"}">{translate key="rt.admin.validateUrls"}</a></li>
</ul>
</div>

<div id="rtAdminSharing">
<h3>{translate key="rt.admin.sharing"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="configureSharing"}">{translate key="rt.admin.configureSharing"}</a></li>
</ul>
</div>

{include file="common/footer.tpl"}

