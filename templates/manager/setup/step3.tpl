{**
 * step3.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of journal setup.
 *}
{assign var="pageTitle" value="manager.setup.guidingSubmissions"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="3"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locale">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="3" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}

<div id="authorGuidelinesInfo">
<h3>3.1 {translate key="manager.setup.authorGuidelines"}</h3>

<p>{translate key="manager.setup.authorGuidelinesDescription"}</p>

<p>
	<textarea name="authorGuidelines[{$formLocale|escape}]" id="authorGuidelines" rows="12" cols="60" class="textArea">{$authorGuidelines[$formLocale]|escape}</textarea>
</p>

</div>

<div id="submissionPreparationChecklist">
<h4>{translate key="manager.setup.submissionPreparationChecklist"}</h4>

<p>{translate key="manager.setup.submissionPreparationChecklistDescription"}</p>

{foreach name=checklist from=$submissionChecklist[$formLocale] key=checklistId item=checklistItem}
	{if !$notFirstChecklistItem}
		{assign var=notFirstChecklistItem value=1}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="5%">{translate key="common.order"}</td>
				<td width="95%" colspan="2">&nbsp;</td>
			</tr>
	{/if}
	{if $checklistId != 1 && $checklistId != 0}
	<tr valign="top">
		<td width="5%" class="label"><input type="text" name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][order]" value="{$checklistItem.order|escape}" size="3" maxlength="2" class="textField" /></td>
		<td class="value"><textarea name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][content]" id="submissionChecklist-{$checklistId|escape}" rows="3" cols="40" class="textArea">{$checklistItem.content|escape}</textarea></td>
		<td width="100%"><input type="submit" name="delChecklist[{$checklistId|escape}]" value="{translate key="common.delete"}" class="button" /></td>
	</tr>
	{else}
	<tr valign="top">
		<td width="5%" class="label"><input type="text" name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][order]" value="{$checklistItem.order|escape}" size="3" maxlength="2" class="textField" readonly="readonly" /></td>
		<td class="value">{$checklistItem.content|escape} <strong>{translate key="manager.setup.checklist.compulsory"}</strong></td>
		<td width="100%"></td>
	</tr>
	{/if}
{/foreach}

{if $notFirstChecklistItem}
	</table>
{/if}

<p><input type="submit" name="addChecklist" value="{translate key="manager.setup.addChecklistItem"}" class="button" /></p>
</div>

<div class="separator"></div>

<div id="authorCopyrightNotice">
<h3>3.2 {translate key="manager.setup.authorCopyrightNotice"}</h3>

{url|assign:"sampleCopyrightWordingUrl" page="information" op="sampleCopyrightWording"}
<p>{translate key="manager.setup.authorCopyrightNoticeDescription" sampleCopyrightWordingUrl=$sampleCopyrightWordingUrl}</p>

<p><textarea name="copyrightNotice[{$formLocale|escape}]" id="copyrightNotice" rows="12" cols="60" class="textArea">{$copyrightNotice[$formLocale]|escape}</textarea></p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $copyrightNoticeAgree} checked="checked"{/if} />
		</td>
		<td width="95%" class="value"><label for="copyrightNoticeAgree">{translate key="manager.setup.authorCopyrightNoticeAgree"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="checkbox" name="includeCreativeCommons" id="includeCreativeCommons" value="1"{if $includeCreativeCommons} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="includeCreativeCommons">{translate key="manager.setup.includeCreativeCommons"}</label>
		</td>
	</tr>
</table>
</div>
<div class="separator"></div>

<div id="requiredSections">
<h3>3.3 Required Sections</h3>
<p>This enables you to select the sections that will be used to create your imprint. Select from the values below to customise the included sections:</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="collectionRequiredSections" value="articlesBiographies"{if $collectionRequiredSections|@count == 1} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="collectionRequiredSections">Primary content is stored in 'Articles' section, additional sections are 'Author Biographies' (with biography requests automatically sent to authors)</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="collectionRequiredSections" value="articlesPrefaceIntroduction"{if $collectionRequiredSections|@count == 2} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="collectionRequiredSections">Primary content is stored in 'Articles' section, additional sections are 'Preface' (to be provided by the Editor-in-Chief), and an 'Introduction' section (to be provided by the Editor-in-Chief)</label>
		</td>
	</tr>	
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="collectionRequiredSections" value="noSections"{if $collectionRequiredSections|@count == 0} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="collectionRequiredSections">No required sections</label>
		</td>
	</tr>
</table>
</div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

