{**
 * @file plugins/generic/booksForReview/templates/editor/booksForReviewAll.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of books for review for editor management.
 *
 *}
{assign var="pageTitle" value="plugins.generic.booksForReview.editor.booksForReviewAll"}
{include file="common/header.tpl"}

<div id="booksForReview">

<ul class="menu">
	<li class="current"><a href="{url op="booksForReview"}">{translate key="plugins.generic.booksForReview.editor.all"}</a></li>
	<li><a href="{url op="booksForReview" path="available"}">{translate key="plugins.generic.booksForReview.editor.available"} ({$counts[$smarty.const.BFR_STATUS_AVAILABLE]})</a></li>
	{if $mode == $smarty.const.BFR_MODE_FULL}
		<li><a href="{url op="booksForReview" path="requested"}">{translate key="plugins.generic.booksForReview.editor.requested"} ({$counts[$smarty.const.BFR_STATUS_REQUESTED]})</a></li>
		<li><a href="{url op="booksForReview" path="assigned"}">{translate key="plugins.generic.booksForReview.editor.assigned"} ({$counts[$smarty.const.BFR_STATUS_ASSIGNED]})</a></li>
		<li><a href="{url op="booksForReview" path="mailed"}">{translate key="plugins.generic.booksForReview.editor.mailed"} ({$counts[$smarty.const.BFR_STATUS_MAILED]})</a></li>
	{/if}
	<li><a href="{url op="booksForReview" path="submitted"}">{translate key="plugins.generic.booksForReview.editor.submitted"} ({$counts[$smarty.const.BFR_STATUS_SUBMITTED]})</a></li>
	<li><a href="{url op="booksForReviewSettings"}">{translate key="plugins.generic.booksForReview.settings"}</a></li>
</ul>

{include file="../plugins/generic/booksForReview/templates/editor/booksForReview.tpl"}

<a href="{url op="createBookForReview" returnPage=$pageToDisplay}" class="action">{translate key="plugins.generic.booksForReview.editor.create"}</a>

</div>

{include file="common/footer.tpl"}
