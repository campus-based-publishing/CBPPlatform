{**
 * upgrade.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Upgrade form.
 *
 * $Id$
 *}
{strip}
{include file="common/header.tpl"}
{/strip}

{translate key="installer.upgradeInstructions" version=$version->getVersionString() baseUrl=$baseUrl}


<div class="separator"></div>


<form method="post" action="{url op="installUpgrade"}">
{include file="common/formErrors.tpl"}

{if $isInstallError}
<div id="installError">
<p>
	<span class="formError">{translate key="installer.installErrorsOccurred"}:</span>
	<ul class="formErrorList">
		<li>{if $dbErrorMsg}{translate key="common.error.databaseError" error=$dbErrorMsg}{else}{translate key=$errorMsg}{/if}</li>
	</ul>
</p>
</div>
{/if}


<p><input type="submit" value="{translate key="installer.upgradeApplication"}" class="button defaultButton" /></p>

</form>

{include file="common/footer.tpl"}

