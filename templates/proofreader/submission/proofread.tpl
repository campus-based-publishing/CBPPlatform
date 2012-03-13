{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the proofreader's proofreading table.
 *
 * $Id$
 *}
{assign var=proofSignoff value=$submission->getSignoff('SIGNOFF_PROOFREADING_PROOFREADER')}
{assign var=proofreader value=$submission->getUserBySignoffType('SIGNOFF_PROOFREADING_PROOFREADER')}

<div id="proofread">
<h3>{translate key="submission.proofreading"}</h3>

<table width="100%" class="data">
	<tr>
		<td class="label" width="20%">{translate key="user.role.proofreader"}</td>
		<td class="value" width="80%">{$proofreader->getFullName()|escape}</td>
	</tr>
</table>

<a href="{url op="viewMetadata" path=$proofSignoff->getAssocId()}" class="action" target="_new">{translate key="submission.reviewMetadata"}</a>

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2">&nbsp;</td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="35%">{translate key="editor.article.authorComments"}</td>
		{assign var="authorProofreadSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_AUTHOR')}
		<td>{$authorProofreadSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$authorProofreadSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$authorProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td>2.</td>
		<td>{translate key="editor.article.proofreaderComments"}</td>
		{assign var="proofreaderProofreadSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_PROOFREADER')}
		<td>{$proofreaderProofreadSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofreaderProofreadSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>
			{url|assign:"url" op="completeProofreader" articleId=$submission->getArticleId()}
			{if not $proofreaderProofreadSignoff->getDateNotified() or not $useProofreaders or $proofreaderProofreadSignoff->getDateCompleted()}
				{icon name="mail" disabled="disabled" url=$url}
			{else}
				{translate|assign:"confirmMessage" key="common.confirmComplete"}
				{icon name="mail" onclick="return confirm('$confirmMessage')" url=$url}
			{/if}
			{$proofreaderProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>3.</td>
		<td>{translate key="editor.article.layoutEditorFinal"}</td>
		{assign var="layoutEditorProofreadSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_LAYOUT')}
		<td>{$layoutEditorProofreadSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$layoutEditorProofreadSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$layoutEditorProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
	{assign var="comment" value=$submission->getMostRecentProofreadComment()}
	<a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getArticleId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
{/if}

{if $currentJournal->getLocalizedSetting('proofInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{url op="instructions" path="proof"}')" class="action">{translate key="submission.proofread.instructions"}</a>
{/if}
</div>

