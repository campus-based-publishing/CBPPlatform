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

{if $journalRt && $journalRt->getEnabled() && $journalRt->getDefineTerms() && $version}

<script type="text/javascript">
	{literal}initRelatedItems();{/literal}
</script>


<div class="block" id="sidebarRTRelatedItems">
	<span class="blockTitle">{translate key="plugins.block.relatedItems.title"}</span>
		<table width="100%">
			<tr>
				<td valign="top" style="width:26px;">
					<img src="{$baseUrl}/plugins/blocks/relatedItems/icons/lookupTerms.png" class="articleToolIcon" />
				</td>
				<td valign="top">
					<div id="relatedItems">
						<ul class="plain">
						{foreach from=$version->getContexts() item=context}
							{if !$context->getDefineTerms()}
								<li><a href="javascript:openRTWindowWithToolbar('{url page="rt" op="context" path=$articleId|to_array:$galleyId:$context->getContextId()}');">{$context->getTitle()|escape}</a></li>
							{/if}
						{/foreach}
						</ul>
					</div>
					<div id="toggleRelatedItems">
						<span id="hideRelatedItems" style="display:none;"><img src="{$baseUrl}/plugins/blocks/relatedItems/icons/magnifier_zoom_out.png" /> {translate key="plugins.block.relatedItems.hide"}</span>
						<span id="showRelatedItems"><img src="{$baseUrl}/plugins/blocks/relatedItems/icons/magnifier_zoom_in.png" /> {translate key="plugins.block.relatedItems.show"}</span>
					</div>
				</td>
			</tr>
		</table>
</div>

{/if}