{**
 * site.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 * $Id$
 *}
{strip}
{if $siteTitle}
	{assign var="pageTitleTranslated" value=$siteTitle}
{/if}
{include file="common/header.tpl"}
{/strip}

<!-- <img src="/public/img/homepage-feature.jpg" />  -->

      <div class="hero-unit">
        <h1>Welcome to<br/>{$pageTitleTranslated}</h1>
        <p>{if $intro}{$intro|nl2br}{/if}</p>
        <p><a class="btn btn-primary btn-large" href="http://www.jisc.ac.uk/whatwedo/programmes/inf11/inf11scholcomm/larkinpress.aspx">Learn more &raquo;</a></p>
  </div>
  
  
      <!-- Example row of columns -->
      <div class="row-fluid">
        <div class="span6">
	        <h1>Start Reading</h1>
	        {if $newIssues|@count gt 0}
				{foreach from=$newIssues key=index item=newIssue}
					<div class="journal homepageImage">
					<a href="{$baseUrl}/index.php/{$newIssue.path}/issue/view/{$newIssue.issue_id}" title="{$newIssue.setting_value}"><img src="{$newIssue.cover}" width="90px" /></a><p><a href="{$baseUrl}/index.php/{$newIssue.path}/issue/view/{$newIssue.issue_id}" title="{$newIssue.setting_value}"><h3>{$newIssue.setting_value}</h3></a> from <a href="{$baseUrl}/index.php/{$newIssue.path}/">{$newIssue.journal}</a></p>
					</div>
				{/foreach}
			{/if}
        </div>
        <div class="span6">
			<h1>Discover Imprints</h1>
			{iterate from=journals item=journal}
			
				{assign var="displayHomePageImage" value=$journal->getLocalizedSetting('homepageImage')}
				{assign var="displayHomePageLogo" value=$journal->getLocalizedPageHeaderLogo(true)}
				{assign var="displayPageHeaderLogo" value=$journal->getLocalizedPageHeaderLogo()}
			
				<div class="journal">
				{if $displayHomePageImage && is_array($displayHomePageImage)}
					{assign var="altText" value=$journal->getLocalizedSetting('homepageImageAltText')}
					<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayHomePageImage.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
				{elseif $displayHomePageLogo && is_array($displayHomePageLogo)}
					{assign var="altText" value=$journal->getLocalizedSetting('homeHeaderLogoImageAltText')}
					<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayHomePageLogo.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
				{elseif $displayPageHeaderLogo && is_array($displayPageHeaderLogo)}
					{assign var="altText" value=$journal->getLocalizedSetting('pageHeaderLogoImageAltText')}
					<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayPageHeaderLogo.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
				{/if}
				</div>
			
				<h3>{$journal->getLocalizedTitle()|escape}</h3>
				{if $journal->getLocalizedDescription()}
					<p>{$journal->getLocalizedDescription()|nl2br}</p>
				{/if}
			
				<p><a class="btn btn-primary btn-mini" href="{url journal=$journal->getPath()}" class="action">{translate key="site.journalView"}</a> <a class="btn btn-primary btn-mini" href="{url journal=$journal->getPath() page="issue" op="current"}" class="action">{translate key="site.journalCurrent"}</a></p>
			{/iterate}  
       </div>
       </div>

      <div class="hero-unit">
        <h2>Get invovled with CBP Platform</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        <p><a class="btn btn-success btn-large" href="http://www.jisc.ac.uk/whatwedo/programmes/inf11/inf11scholcomm/larkinpress.aspx">Learn more &raquo;</a></p>
      </div>

<!--<div style="float: left; width: 100%;" class="shelf">
	<h3 class="shelfHeading">Featured Imprints</h3>
	
	<div style="float: left; margin-right: 20px; width: 190px;"><img src="http://www.tripextras.com/files/cities/humberside_humber_bridge_0.jpg" height="150" /><p><a href="{$baseUrl}/index.php/hn/index"><strong>Humberside Novelists</strong></a></p><p><em>"Creative output of the MA in Creative Writing"</em></p></div>
	<div style="float: left; width: 190px;"><img src="http://upload.wikimedia.org/wikipedia/en/4/48/Philip_Larkin_in_a_library.gif" height="150" /><p><a href="{$baseUrl}/index.php/l26/index"><strong>Larkin 26 Collection of Poetry</strong></a></p><p><em>"Poetry to celebrate the life and work of the poet, novelist, librarian and jazz critic Philip Larkin, marking the 26th anniversary of his death."</em></p></div>
</div> -->


<!--<div style="float: left; width: 100%;">
	<h3 class="shelfHeading">Featured Authors</h3>
	
	<div style="float: left; padding-right: 20px; width: 190px;"><img src="http://www.sweden.se/upload/Sweden_se/english/articles/SI/2006%20uppdaterad/On%20the%20trail/author_mankell_sweden.jpg" height="150" /><p><strong>Scott Adams</strong></p><p><em>"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium"</em></p></div>
	<div style="float: left; width: 190px;"><img src="http://mhpbooks.com/mobylives/wp-content/uploads/2011/03/egan.jpg" height="150" /><p><strong>Emico Larkin</strong></p><p><em>"Nemo enim ipsam voluptatem quia voluptas sit aspernatur"</em></p></div>
</div>  -->

<!--<div style="float: left; width: 100%" class="shelf">
	<h3 class="shelfHeading">What's Hot</h3>
	<ul>
	{foreach from=$popularArticles key=index item=popularArticle}
		<div class="featuredItem"><img src="http://www.ajm-environmental.com/images/placeHolder.png" height="90px" /><p><a href="{$baseUrl}/index.php/{$popularArticle.path}/article/view/{$popularArticle.article_id}" title="{$popularArticle.setting_value}"><strong>{$popularArticle.setting_value}</strong><br />By {$popularArticle.first_name} {$popularArticle.middle_name} {$popularArticle.last_name}</a><br /><em>{$popularArticle.views} views</em></p></div>
	{/foreach}
	</ul>
</div> -->

{include file="common/footer.tpl"}