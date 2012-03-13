{**
 * submissionReview.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission review.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.review"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	<li class="current"><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>
</ul>


{include file="author/submission/summary.tpl"}

{if $workshop != "workshop"}

<div class="separator"></div>

{include file="author/submission/peerReview.tpl"}

{/if}

<div class="separator"></div>

{if $creativelyComplete == 1}
<p><strong>You have marked this article as creatively complete in lieu of full reviewer approval</strong></p><div class="separator"></div>
{elseif $creativelyComplete == "public"}
<!-- do nothing -->
{else}
<p><a href="?f=creatively_complete">Mark this item of work as creatively complete</a></p><div class="separator"></div>
{/if}

{if $editorRequest == 1 && $workshop != ""}
<p><strong>You have requested an editor be brought into proceedings</strong></p><div class="separator"></div>
{elseif $workshop != ""}
<p><a href="?f=editor_request">Request editor be brought into proceedings</a></p><div class="separator"></div>
{/if}

{include file="author/submission/reviewerComments.tpl"}

<div class="separator"></div>

{include file="author/submission/editorDecision.tpl"}

{include file="common/footer.tpl"}

