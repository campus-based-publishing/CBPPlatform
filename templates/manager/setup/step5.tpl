{**
 * step5.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of journal setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.customizingTheLook"}
{include file="manager/setup/setupHeader.tpl"}

<script type="text/javascript">
{literal}
<!--

// Swap the given entries in the select field.
function swapEntries(field, i, j) {
	var tmpLabel = field.options[j].label;
	var tmpVal = field.options[j].value;
	var tmpText = field.options[j].text;
	var tmpSel = field.options[j].selected;
	field.options[j].label = field.options[i].label;
	field.options[j].value = field.options[i].value;
	field.options[j].text = field.options[i].text;
	field.options[j].selected = field.options[i].selected;
	field.options[i].label = tmpLabel;
	field.options[i].value = tmpVal;
	field.options[i].text = tmpText;
	field.options[i].selected = tmpSel;
}

// Move selected items up in the given select list.
function moveUp(field) {
	var i;
	for (i=0; i<field.length; i++) {
		if (field.options[i].selected == true && i>0) {
			swapEntries(field, i-1, i);
		}
	}
}

// Move selected items down in the given select list.
function moveDown(field) {
	var i;
	var max = field.length - 1;
	for (i = max; i >= 0; i=i-1) {
		if(field.options[i].selected == true && i < max) {
			swapEntries(field, i+1, i);
		}
	}
}

// Move selected items from select list a to select list b.
function jumpList(a, b) {
	var i;
	for (i=0; i<a.options.length; i++) {
		if (a.options[i].selected == true) {
			bMax = b.options.length;
			b.options[bMax] = new Option(a.options[i].text);
			b.options[bMax].value = a.options[i].value;
			a.options[i] = null;
			i=i-1;
		}
	}
}

function prepBlockFields() {
	var i;
	var theForm = document.setupForm;

	theForm.elements["blockSelectLeft"].value = "";
	for (i=0; i<theForm.blockSelectLeftWidget.options.length; i++) {
		theForm.blockSelectLeft.value += encodeURI(theForm.blockSelectLeftWidget.options[i].value) + " ";
	}

	theForm.blockSelectRight.value = "";
	for (i=0; i<theForm.blockSelectRightWidget.options.length; i++) {
		theForm.blockSelectRight.value += encodeURI(theForm.blockSelectRightWidget.options[i].value) + " ";
	}

	theForm.blockUnselected.value = "";
	for (i=0; i<theForm.blockUnselectedWidget.options.length; i++) {
		theForm.blockUnselected.value += encodeURI(theForm.blockUnselectedWidget.options[i].value) + " ";
	}
	return true;
}

// -->
{/literal}
</script>

<form name="setupForm" method="post" action="{url op="saveSetup" path="5"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="5" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}
<div id="journalHomepageHeader">
<h3>5.1 {translate key="manager.setup.journalHomepageHeader"}</h3>

<p>{translate key="manager.setup.journalHomepageHeaderDescription"}</p>
<div id="journalTitleAndLogo">
<h4>{translate key="manager.setup.journalTitle"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleType[{$formLocale|escape}]" id="homeHeaderTitleType-0" value="0"{if not $homeHeaderTitleType[$formLocale]} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleType-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="homeHeaderTitle[{$formLocale|escape}]" value="{$homeHeaderTitle[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleType[{$formLocale|escape}]" id="homeHeaderTitleType-1" value="1"{if $homeHeaderTitleType[$formLocale]} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleType-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderTitleImage" class="uploadField" /> <input type="submit" name="uploadHomeHeaderTitleImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderTitleImage[$formLocale]}
{translate key="common.fileName"}: {$homeHeaderTitleImage[$formLocale].name|escape} {$homeHeaderTitleImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderTitleImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderTitleImage[$formLocale].uploadName|escape:"url"}" width="{$homeHeaderTitleImage[$formLocale].width|escape}" height="{$homeHeaderTitleImage[$formLocale].height|escape}" style="border: 0;" alt="{translate key="common.homePageHeader.altText"}" />
<br />
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="homeHeaderTitleImageAltText" key="common.altText"}</td>
		<td width="80%" class="value"><input type="text" name="homeHeaderTitleImageAltText[{$formLocale|escape}]" value="{$homeHeaderTitleImageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
	</tr>
</table>
{/if}
</div>
</div>

<div class="separator"></div>

<div id="journalHomepageContent">
<h3>5.2 {translate key="manager.setup.journalHomepageContent"}</h3>

<p>{translate key="manager.setup.journalHomepageContentDescription"}</p>
</div>

<div id="journalDescription">
<h4>{translate key="manager.setup.journalDescription"}</h4>

<p>{translate key="manager.setup.journalDescriptionDescription"}</p>

<p><textarea id="description" name="description[{$formLocale|escape}]" rows="3" cols="60" class="textArea">{$description[$formLocale]|escape}</textarea></p>
</div>
<div id="homepageImage">
<h4>{translate key="manager.setup.homepageImage"}</h4>

<p>{translate key="manager.setup.homepageImageDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.homepageImage"}</td>
		<td width="80%" class="value"><input type="file" name="homepageImage" class="uploadField" /> <input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homepageImage[$formLocale]}
{translate key="common.fileName"}: {$homepageImage[$formLocale].name|escape} {$homepageImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomepageImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homepageImage[$formLocale].uploadName|escape:"url"}" width="{$homepageImage[$formLocale].width|escape}" height="{$homepageImage[$formLocale].height|escape}" style="border: 0;" alt="{translate key="common.journalHomepageImage.altText"}" />
<br />
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="homepageImageAltText" key="common.altText"}</td>
		<td width="80%" class="value"><input type="text" name="homepageImageAltText[{$formLocale|escape}]" value="{$homepageImageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
		</tr>
</table>
{/if}

<div id="currentIssue">
<h4>{translate key="manager.setup.currentIssue"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="displayCurrentIssue" id="displayCurrentIssue" value="1" {if $displayCurrentIssue} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="displayCurrentIssue">{translate key="manager.setup.displayCurrentIssue"}</label></td>
	</tr>
</table>
</div>
<div id="additionalContent">

<h4>{translate key="manager.setup.additionalContent"}</h4>

<p>{translate key="manager.setup.additionalContentDescription"}</p>

<p><textarea name="additionalHomeContent[{$formLocale|escape}]" id="additionalHomeContent" rows="12" cols="60" class="textArea">{$additionalHomeContent[$formLocale]|escape}</textarea></p>
</div>
</div>

<div class="separator"></div>
<div id="requiredSections">
<h3>5.3 Imprint Book Typesetting</h3>
<p>Select the look-and-feel for any books published by your imprint by selecting one of the pre-defined templates below:</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="imprintStylesheet" value="poem-a4"{if $imprintStylesheet == "poem-a4"} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="imprintStylesheet">'Poem' - Georgia font, 1x spacing, left-hand page margin</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="imprintStylesheet" value="book-trade"{if $imprintStylesheet == "book-trade" || $imprintStylesheet == ""} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="imprintStylesheet">'Book' - 'Trade' paper size, 12pt Palatino font, 1.5x spacing, justified text</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="imprintStylesheet" value="book-a4"{if $imprintStylesheet == "book-a4" || $imprintStylesheet == ""} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="imprintStylesheet">'Book' - A4 paper size, 12pt Palatino font, 1.5x spacing, justified text</label>
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>
<div id="requiredSections">
<h3>5.4 Imprint Books</h3>
<p>Select type of book that your Imprint will publish:</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="imprintType" value="atomistic"{if $imprintType == "atomistic"} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="imprintType">Novels by a single author</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="5%">
			<input type="radio" name="imprintType" value="collection"{if $imprintType == "collection" || $imprintType == ""} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="imprintType">Collections containing work of one or many authors (e.g. poetry, short stories or collected works)</label>
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>
<div id="setupInfo">
<h3>5.5 {translate key="manager.setup.information"}</h3>

<p>{translate key="manager.setup.information.description"}</p>

<div id="infoForReaders"><h4>{translate key="manager.setup.information.forReaders"}</h4>

<p><textarea name="readerInformation[{$formLocale|escape}]" id="readerInformation" rows="12" cols="60" class="textArea">{$readerInformation[$formLocale]|escape}</textarea></p></div>

<div id="infoForAuth"><h4>{translate key="manager.setup.information.forAuthors"}</h4>

<p><textarea name="authorInformation[{$formLocale|escape}]" id="authorInformation" rows="12" cols="60" class="textArea">{$authorInformation[$formLocale]|escape}</textarea></p></div>

<!--<div id="infoForLibs"><h4>{translate key="manager.setup.information.forLibrarians"}</h4>

<p><textarea name="librarianInformation[{$formLocale|escape}]" id="librarianInformation" rows="12" cols="60" class="textArea">{$librarianInformation[$formLocale]|escape}</textarea></p></div>
</div>  -->

<div class="separator"></div>

<p><input type="submit" onclick="prepBlockFields()" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}