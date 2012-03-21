{**
 * block.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- user tools.
 *
 * $Id$
 *}
	
	{if !$implicitAuth}
		<p><span class="blockTitle">{translate key="navigation.userLogin"}</span></p>
	{/if}
	
	{if $isUserLoggedIn}
		{translate key="plugins.block.user.loggedInAs"}<br />
		<p><strong>{$loggedInUsername|escape}</strong></p>
		<ul>
			{if $hasOtherJournals}
				<li><a href="{url journal="index" page="user"}">{translate key="plugins.block.user.myJournals"}</a></li>
			{/if}
			<li><a href="{url page="user" op="profile"}">{translate key="plugins.block.user.myProfile"}</a></li>
			<li><a href="{url page="login" op="signOut"}">{translate key="plugins.block.user.logout"}</a></li>
			{if $userSession->getSessionVar('signedInAs')}
				<li><a href="{url page="login" op="signOutAsUser"}">{translate key="plugins.block.user.signOutAsUser"}</a></li>
			{/if}
		</ul>
	{else}
		{if $implicitAuth}	
			<p><a href="{url page="login" op="implicitAuthLogin"}">Journals Login</a></p>	
		{else}
			<form method="post" action="{$userBlockLoginUrl}">
				<table>
					<tr>
						<td><label for="sidebar-username">{translate key="user.username"}</label></td>
						<td><input type="text" id="sidebar-username" name="username" value="" size="12" maxlength="32" class="textField" /></td>
					</tr>
					<tr>
						<td><label for="sidebar-password">{translate key="user.password"}</label></td>
						<td><input type="password" id="sidebar-password" name="password" value="{$password|escape}" size="12" maxlength="32" class="textField" /></td>
					</tr>
					<tr>
						<td colspan="2"><input type="checkbox" id="remember" name="remember" value="1" /> <label for="remember">{translate key="plugins.block.user.rememberMe"}</label></td>
					</tr>
					<tr>
						<td colspan="2"><input type="submit" value="{translate key="user.login"}" class="button" /></td>
					</tr>
				</table>
			</form>
		{/if}
	{/if}
<!-- %LP% social media buttons -->
{if $issue}
		<div class="fb-like" data-href="{$currentUrl}" data-send="true" data-layout="box_count" data-width="450" data-show-faces="true" data-font="lucida grande"></div>
		<br />
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="{$currentUrl}" data-count="vertical">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
{else}
		<div class="fb-like" data-href="{$baseUrl}" data-send="true" data-layout="box_count" data-width="450" data-show-faces="true" data-font="lucida grande"></div>
		<br />
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="{$baseUrl}" data-count="vertical">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
{/if}
<div class="block" id="sidebarUser" style="margin-top: 25px;">
<p><small><strong>CBP Platform</strong> is a proof-of-concept prototype from a campus-based publishing project at the University of Hull, the project aim being to develop a web platform for authors, editors and reviewers to create, manage and disseminate multi-format academic output (eBook and Print).</small></p>
</div>
