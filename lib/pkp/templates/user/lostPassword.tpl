{**
 * lostPassword.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Password reset form.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="user.login.resetPassword"}
{include file="common/header.tpl"}
{/strip}
{if !$registerLocaleKey}
	{assign var="registerLocaleKey" value="user.login.registerNewAccount"}
{/if}

<form name="reset" action="{url page="login" op="requestResetPassword"}" method="post">
<p><span class="instruct">{translate key="user.login.resetPasswordInstructions"}</span></p>

{if $error}
	<p><span class="formError">{translate key="$error"}</span></p>
{/if}

<table id="lostPasswordTable" class="data" width="100%">
<tr valign="top">
	<td class="label" width="25%">{translate key="user.login.registeredEmail"}</td>
	<td class="value" width="75%"><input type="text" name="email" value="{$username|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="user.login.resetPassword"}" class="button defaultButton" /></p>

{if !$hideRegisterLink}
	&#187; <a href="{url page="user" op=$registerOp}">{translate key=$registerLocaleKey}</a>
{/if}

<script type="text/javascript">
<!--
	document.reset.email.focus();
// -->
</script>
</form>

{include file="common/footer.tpl"}

