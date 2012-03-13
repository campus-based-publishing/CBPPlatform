{**
 * citationFilterOptionBlock.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Shows a list of citation filters to be selected from.
 *
 * Parameters:
 *   $titleKey: the option block title translation key
 *   $availableFilters: an array with filters
 *}
<div class="option-block">
	<p>{translate key=$titleKey}</p>
	<div>
		{foreach from=$availableFilters item=citationFilter}
			{assign var=citationFilterFieldName value="citationFilters["|concat:$citationFilter->getId():"]"}
			{if $citationFilter->getData('isOptional')}
				{assign var=citationFilterDefault value=false}
			{else}
				{assign var=citationFilterDefault value=true}
			{/if}
			<div class="option-block-option">
				{fbvCheckbox id=$citationFilter->getDisplayName() name=$citationFilterFieldName
						checked=$citationFilterDefault}
				{fieldLabel name=$citationFilterFieldName label=$citationFilter->getDisplayName() required=false}
			</div>
		{/foreach}
	</div>
	<div class="clear"></div>
</div>
