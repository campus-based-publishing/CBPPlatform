{**
 * workshop.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer index.
 *
 * $Id$
 *}
{assign var="pageTitle" value="reviewer.workshopPendingReviews"}
{include file="common/header.tpl"}

<table>
	<tr>
		<th></th>
		<th>Title</th>
		<th>Author</th>
		{if $workshop == "hybrid"}<th>Round</th>{/if}
		<th></th>
		<th></th>
	</tr>
{foreach from=$workshopArticles key=myId item=i}
	{if $i.highlight}<tr style="background-color: yellow;"><td><strong>New!</strong></td>
	{else}<tr><td></td>{/if}
	  	<td>{$i.title}</td>
	  	<td>{$i.first_name} {$i.last_name}</td>
	  	{if $workshop == "hybrid"}<td>Round {$i.current_round}</td>{/if}
	  	<td>{if $i.review_id != "" && $i.review_count}Previously reviewed {$i.review_count} times{/if}</td>
	  	<td>[<a href="{url page="reviewer" op="submission" path=$i.article_id}">REVIEW</a>]</td>
	</tr>
{/foreach}
</table>
	
{include file="common/footer.tpl"}