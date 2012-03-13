{**
 * block.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Keyword cloud block plugin
 *
 * $Id$
 *}
<div class="block" id="sidebarKeywordCloud">
	<span class="blockTitle">{translate key="plugins.block.keywordCloud.title"}</span>
	{foreach name=cloud from=$cloudKeywords key=keyword item=count}
		<a href="{url page="search" op="advancedResults" subject=$keyword}"><span style="font-size: {math equation="((x-1) / y * 100)+75" x=$count y=$maxOccurs}%;">{$keyword}</span></a> 
	{/foreach}
</div>
