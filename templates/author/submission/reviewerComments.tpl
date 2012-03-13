{**
 * reviewerComments.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's submission summary table.
 *
 * $Id$
 *}
<div id="peerReview">
	<h3>Reviewer Comments</h3>
	<p>Show comments from <a href="?showComments={$round}">Latest Round</a>
	{foreach from=$reviewerComments key=roundNo item=round}
		{if $roundNo != "" }
			{if $roundNo == $round}
				| <a href="?showComments={$roundNo}">This Round</a>
			{else}
				| <a href="?showComments={$roundNo}">Round {$roundNo}</a>
			{/if}
		{/if}
	{/foreach}
	| <a href="?">All</a></p>
	{foreach from=$reviewerComments key=roundNo item=round}
		{foreach from=$round item=comment}
			{if $smarty.get.showComments && $roundNo == $smarty.get.showComments || !$smarty.get.showComments}
				{if $comment.viewable == 1}
					<h4>{$comment.first_name} {$comment.middle_name} {$comment.last_name}</h4>
					<table width="100%" class="data">
						<tr>
							<td width="20%" class="label">Review Round</td>
							<td width="80%"><strong>Review Round {$roundNo}</strong></td>
						</tr>
						<tr>
							<td width="20%" class="label">Date Reviewed</td>
							<td width="80%">{$comment.date_posted}</td>
						</tr>
						<tr>
							<td class="label">Comments</td>
							<td><em>"{$comment.comments}"</em></td>
						</tr>
						{if $comment.articleFile}
						<tr>
							<td class="label">Download</td>
							<td><a href="{url op="downloadFile" path=$comment.articleFile->getArticleId()|to_array:$comment.articleFile->getFileId():$comment.articleFile->getRevision()}" class="file">Download detailed comment document</a></td>
						</tr>
						{/if}
					</table>
				{/if}
			{/if}
		{/foreach}
	{/foreach}
</div>