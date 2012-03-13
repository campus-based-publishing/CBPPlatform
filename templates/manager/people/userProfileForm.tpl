{**
 * userProfileForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form under journal management.
 *
 * $Id$
 *}
{strip}
{url|assign:"currentUrl" op="people" path="all"}
{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}
{/strip}

{literal}
<script type="text/javascript">
	$(document).ready(function(){
		$("#interestsTextOnly").hide();
		$("#interests").tagit({
			{/literal}{if $existingInterests}{literal}
			// This is the list of interests in the system used to populate the autocomplete
			availableTags: [{/literal}{foreach name=existingInterests from=$existingInterests item=interest}"{$interest|escape|escape:'javascript'}"{if !$smarty.foreach.existingInterests.last}, {/if}{/foreach}{literal}],{/literal}{/if}
			// This is the list of the user's interests that have already been saved
			{if $interestsKeywords}{literal}currentTags: [{/literal}{foreach name=currentInterests from=$interestsKeywords item=interest}"{$interest|escape|escape:'javascript'}"{if !$smarty.foreach.currentInterests.last}, {/if}{/foreach}{literal}]{/literal}
			{else}{literal}currentTags: []{/literal}{/if}{literal}
		});
	});
</script>
{/literal}

{if not $userId}
{assign var="passwordRequired" value="true"}

{literal}
<script type="text/javascript">
<!--
	function setGenerateRandom(value) {
		if (value) {
			document.userForm.password.value='********';
			document.userForm.password2.value='********';
			document.userForm.password.disabled=1;
			document.userForm.password2.disabled=1;
			document.userForm.sendNotify.checked=1;
			document.userForm.sendNotify.disabled=1;
		} else {
			document.userForm.password.disabled=0;
			document.userForm.password2.disabled=0;
			document.userForm.sendNotify.disabled=0;
			document.userForm.password.value='';
			document.userForm.password2.value='';
			document.userForm.password.focus();
		}
	}

	function enablePasswordFields() {
		document.userForm.password.disabled=0;
		document.userForm.password2.disabled=0;
	}

	function generateUsername() {
		var req = makeAsyncRequest();

		if (document.userForm.lastName.value == "") {
			alert("{/literal}{translate key="manager.people.mustProvideName"}{literal}");
			return;
		}

		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				document.userForm.username.value = req.responseText;
			}
		}
		sendAsyncRequest(req, '{/literal}{url op="suggestUsername" firstName="REPLACE1" lastName="REPLACE2" escape=false}{literal}'.replace('REPLACE1', escape(document.userForm.firstName.value)).replace('REPLACE2', escape(document.userForm.lastName.value)), null, 'get');
	}

// -->
</script>
{/literal}
{/if}

{if $userCreated}
<p>{translate key="manager.people.userCreatedSuccessfully"}</p>
{/if}

<h3>{if $userId}{translate key="manager.people.editProfile"}{else}{translate key="manager.people.createUser"}{/if}</h3>

<form name="userForm" method="post" action="{url op="updateUser"}" onsubmit="enablePasswordFields()">
<input type="hidden" name="source" value="{$source|escape}" />
{if $userId}
<input type="hidden" name="userId" value="{$userId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table width="100%" class="data">
{if count($formLocales) > 1}
	 <tr valign="top">
	 	<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"userFormUrl" page="manager" op="editUser" path=$userId escape=false}
			{form_language_chooser form="userForm" url=$userFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="salutation" key="user.salutation"}</td>
		<td class="value"><input type="text" name="salutation" id="salutation" value="{$salutation|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
		<td class="value"><input type="text" name="firstName" id="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="middleName" id="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="lastName" id="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
		<td class="value"><input type="text" name="initials" id="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
	</tr>
	{if not $userId}
	<tr valign="top">
		<td class="label">{fieldLabel name="enrollAs" key="manager.people.enrollUserAs"}</td>
		<td class="value">
			<select name="enrollAs[]" id="enrollAs" multiple="multiple" size="11" class="selectMenu">
			{html_options_translate options=$roleOptions selected=$enrollAs}
			</select>
			<br />
			<span class="instruct">{translate key="manager.people.enrollUserAsDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
		<td class="value">
			<input type="text" name="username" id="username" value="{$username|escape}" size="20" maxlength="32" class="textField" />&nbsp;&nbsp;<input type="button" class="button" value="{translate key="common.suggest"}" onclick="generateUsername()" />
			<br />
			<span class="instruct">{translate key="user.register.usernameRestriction"}</span>
		</td>
	</tr>
	{else}
	<tr valign="top">
		<td class="label">{fieldLabel suppressId="true" name="username" key="user.username"}</td>
		<td class="value"><strong>{$username|escape}</strong></td>
	</tr>
	{/if}
	{if $authSourceOptions}
	<tr valign="top">
		<td class="label">{fieldLabel name="authId" key="manager.people.authSource"}</td>
		<td class="value"><select name="authId" id="authId" size="1" class="selectMenu">
			<option value=""></option>
			{html_options options=$authSourceOptions selected=$authId}
		</select></td>
	</tr>
	{/if}

	{if !$implicitAuth}
		<tr valign="top">
			<td class="label">{fieldLabel name="password" required=$passwordRequired key="user.password"}</td>
			<td class="value">
				<input type="password" name="password" id="password" value="{$password|escape}" size="20" maxlength="32" class="textField" />
				<br />
				<span class="instruct">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</span>
			</td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="password2" required=$passwordRequired key="user.register.repeatPassword"}</td>
			<td class="value"><input type="password" name="password2"  id="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
		</tr>
		{if $userId}
		<tr valign="top">
			<td>&nbsp;</td>
			<td class="value">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}<br />{translate key="user.profile.leavePasswordBlank"}</td>
		</tr>
		{else}
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><input type="checkbox" onclick="setGenerateRandom(this.checked)" name="generatePassword" id="generatePassword" value="1"{if $generatePassword} checked="checked"{/if} /> <label for="generatePassword">{translate key="manager.people.createUserGeneratePassword"}</label></td>
		</tr>
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><input type="checkbox" name="sendNotify" id="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /> <label for="sendNotify">{translate key="manager.people.createUserSendNotify"}</label></td>
		</tr>
		{/if}
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><input type="checkbox" name="mustChangePassword" id="mustChangePassword" value="1"{if $mustChangePassword} checked="checked"{/if} /> <label for="mustChangePassword">{translate key="manager.people.userMustChangePassword"}</label></td>
		</tr>
	{/if}{* !$implicitAuth *}
	<tr valign="top">
		<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $userId}<input type="submit" name="createAnother" value="{translate key="manager.people.saveAndCreateAnotherUser"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $source == ''}history.go(-1);{else}document.location='{$source|escape:"jsparam"}';{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{if $generatePassword}
{literal}
	<script type="text/javascript">
		<!--
		setGenerateRandom(1);
		// -->
	</script>
{/literal}
{/if}

{include file="common/footer.tpl"}

