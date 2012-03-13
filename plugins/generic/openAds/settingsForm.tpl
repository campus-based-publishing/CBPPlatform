{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2009 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * openAds plugin settings
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.openads"}
{include file="common/header.tpl"}
{/strip}
<div id="openAdsSettings">
<div id="description">{translate key="plugins.generic.openads.settings.description"}</div>

<div class="separator">&nbsp;</div>

<h3>{translate key="plugins.generic.openads.manager.settings"}</h3>

<form method="post" action="{url path="generic"|to_array:$pluginName:"settings":"save"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="headerAdId" key="plugins.generic.openads.headerAd"}</td>
		<td width="80%" class="value">
			<select name="headerAdId" class="selectMenu" id="headerAdId">
				<option value="0">{translate key="common.disabled"}</option>
				{foreach from=$ads item=ad}
					<option {if $headerAdId == $ad->getAdId()}selected="selected" {/if}value="{$ad->getAdId()|escape}">{$ad->getName()|escape}</option>
				{/foreach}
			</select>&nbsp;&nbsp;
			<select name="headerAdOrientation" class="selectMenu" id="headerAdOrientation">
				{foreach from=$orientationOptions key=orientationOption item=orientationOptionKey}
					<option {if $headerAdOrientation == $orientationOption}selected="selected" {/if}value="{$orientationOption|escape}">{translate key=$orientationOptionKey}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contentAdId" key="plugins.generic.openads.contentAd"}</td>
		<td width="80%" class="value">
			<select name="contentAdId" class="selectMenu" id="contentAdId">
				<option value="0">{translate key="common.disabled"}</option>
				{foreach from=$ads item=ad}
					<option {if $contentAdId == $ad->getAdId()}selected="selected" {/if}value="{$ad->getAdId()|escape}">{$ad->getName()|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sidebarAdId" key="plugins.generic.openads.sidebarAd"}</td>
		<td width="80%" class="value">
			<select name="sidebarAdId" class="selectMenu" id="sidebarAdId">
				<option value="0">{translate key="common.disabled"}</option>
				{foreach from=$ads item=ad}
					<option {if $sidebarAdId == $ad->getAdId()}selected="selected" {/if}value="{$ad->getAdId()|escape}">{$ad->getName()|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
</table>
<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
