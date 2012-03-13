{**
 * navbar.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Navigation Bar
 *
 *}
<div id="navbar">
	<ul class="menu">
		{if $homepage == true}
			<li id="home"><a href="{$baseUrl}">{translate key="navigation.home"}</a></li>
		{else}
			<li id="home"><a href="{url page="index"}">{translate key="navigation.home"}</a></li>
		{/if}

		{if $isUserLoggedIn}
			<li id="userHome"><a href="{url context="index" page="user"}">{translate key="navigation.userHome"}</a></li>
		{else}
			<li id="login"><a href="{url context="index" page="login"}">{translate key="navigation.login"}</a></li>
			{if !$hideRegisterLink}
				<li id="register"><a href="{url context="index" page="user" op="register"}">{translate key="navigation.register"}</a></li>
			{/if}
		{/if}{* $isUserLoggedIn *}

		{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li id="search"><a href="{url context="index" page="search"}">{translate key="navigation.search"}</a></li>
		{/if}

		{if $currentJournal && $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li id="current"><a href="{url page="issue" op="current"}">{translate key="navigation.current"}</a></li>
			<li id="archives"><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
		{/if}

		{if $enableAnnouncements}
			<li id="announcements"><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}{* enableAnnouncements *}

		{call_hook name="Templates::Common::Header::Navbar::CurrentJournal"}

		{foreach from=$navMenuItems item=navItem}
			{if $navItem.url != '' && $navItem.name != ''}
				<li id="navItem"><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

