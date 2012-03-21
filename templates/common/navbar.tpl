{**
 * navbar.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Navigation Bar
 *
 *}
 <div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
    <div class="container-fluid"><a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </a> <a class="brand" href="{$baseUrl}">Campus-based Publishing Platform</a>
<div class="nav-collapse">
	<ul class="nav">
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

		<!--{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li id="search"><a href="{url context="index" page="search"}">{translate key="navigation.search"}</a></li>
		{/if}-->

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
		<!-- %CBP% search in the context of the press rather than a specific imprint -->
		<form method="post" action="{url journal="index" page="search" op="results"}" class="navbar-form pull-left"><input type="text" class="search-query span2" placeholder="Search" id="query" name="query" ></form>
</div>
      </div>
        <!--/.nav-collapse --> 
      </div>
  </div>
  </div>

