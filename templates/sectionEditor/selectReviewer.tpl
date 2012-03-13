{**
 * selectReviewer.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List reviewers and give the ability to select a reviewer.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="user.role.reviewers"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function sortSearch(heading, direction) {
  document.submit.sort.value = heading;
  document.submit.sortDirection.value = direction;
  document.submit.submit() ;
}
// -->
{/literal}
</script>

<div id="selectReviewer">
<h3>{translate key="editor.article.selectReviewer"}</h3>

<p>{foreach from=$alphaList item=letter}<a href="{url op="selectReviewer" path=$articleId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="selectReviewer" path=$articleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<p><a class="action" href="{url op="enrollSearch" path=$articleId}">{translate key="sectionEditor.review.enrollReviewer"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="createReviewer" path=$articleId}">{translate key="sectionEditor.review.createReviewer"}</a>{foreach from=$reviewerDatabaseLinks item="link"}{if !empty($link.title) && !empty($link.url)}&nbsp;|&nbsp;<a href="{$link.url|escape}" target="_new" class="action">{$link.title|escape}</a>{/if}{/foreach}</p>

<div id="reviewers">
<table class="listing" width="100%">
{assign var=numCols value=7}
{if $rateReviewerOnQuality}
	{assign var=numCols value=$numCols+1}
{/if}
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="20%">{sort_search key="user.name" sort="reviewerName"}</td>
	<td>{translate key="user.interests"}</td>
	{if $rateReviewerOnQuality}
		<td width="7%">{sort_search key="reviewer.averageQuality" sort="quality"}</td>
	{/if}
	<td width="7%">{sort_search key="reviewer.completedReviews" sort="done"}</td>
	<td width="7%">{sort_search key="editor.submissions.averageTime" sort="average"}</td>
	<td width="13%">{sort_search key="editor.submissions.lastAssigned" sort="latest"}</td>
	<td width="5%">{sort_search key="common.active" sort="active"}</td>
	<td width="7%" class="heading">{translate key="common.action"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
{iterate from=reviewers item=reviewer}
{assign var="userId" value=$reviewer->getId()}
{assign var="qualityCount" value=$averageQualityRatings[$userId].count}
{assign var="reviewerStats" value=$reviewerStatistics[$userId]}

<tr valign="top">
	<td><a class="action" href="{url op="userProfile" path=$userId}">{$reviewer->getFullName()|escape}</a></td>
	<td>{$reviewer->getInterests()|urldecode|escape}</td>
	{if $rateReviewerOnQuality}<td>
		{if $qualityCount}{$averageQualityRatings[$userId].average|string_format:"%.1f"}
		{else}{translate key="common.notApplicableShort"}{/if}
	</td>{/if}

	<td>
		{if $completedReviewCounts[$userId]}
			{$completedReviewCounts[$userId]}
		{else}
			0
		{/if}
	</td>

	<td>
		{if $reviewerStats.average_span}
			{math equation="round(theSpan)" theSpan=$reviewerStats.average_span}
		{else}
			&mdash;
		{/if}
	</td>
	<td>{if $reviewerStats.last_notified}{$reviewerStats.last_notified|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	<td>{$reviewerStats.incomplete|default:0}</td>
	<td>
		{if $reviewer->review_id}
			{translate key="common.alreadyAssigned"}
		{else}
		<a class="action" href="{url op="selectReviewer" path=$articleId|to_array:$reviewer->getId()}">{translate key="common.assign"}</a>
		{/if}
	</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="{if $reviewers->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $reviewers->wasEmpty()}
<tr>
<td colspan="{$numCols|escape}" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$reviewers}</td>
		<td colspan="{$numCols-2}" align="right">{page_links anchor="reviewers" name="reviewers" iterator=$reviewers searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>

<h4>{translate key="common.notes"}</h4>
<p>{translate key="editor.article.selectReviewerNotes"}</p>
</div>
</div>

{include file="common/footer.tpl"}

