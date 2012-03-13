{**
 * metadata_view.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View (but not edit) metadata of an article. Used by MetadataForm.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="submission.viewMetadata"}
{include file="common/header.tpl"}
{/strip}

{if $canViewAuthors}
<div id="authors">
<h3>{translate key="article.authors"}</h3>
	
<table width="100%" class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=emailString value=$author.firstName|concat:" ":$author.middleName:" ":$author.lastName:" <":$author.email:">"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl articleId=$articleId}
			{$author.firstName|escape} {$author.middleName|escape} {$author.lastName|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.url"}</td>
		<td class="value">{$author.url|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author.affiliation.$formLocale|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$author.countryLocalized|escape|default:"&mdash;"}</td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
	<tr valign="top">
		<td class="label">
			{url|assign:"competingInterestGuidelinesUrl" page="information" op="competingInterestGuidelines"}
			{translate key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}
		</td>
		<td class="value">{$author.competingInterests.$formLocale|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author.biography.$formLocale|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{/foreach}
</table>
</div>

<div class="separator"></div>
{/if}

<div id="titleAndAbstract">
<h3>{translate key="submission.titleAndAbstract"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$title[$formLocale]|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$abstract[$formLocale]|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="indexing">
<h3>{translate key="submission.indexing"}</h3>
	
<table width="100%" class="data">
	{if $currentJournal->getSetting('metaDiscipline')}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.discipline"}</td>
		<td width="80%" class="value">{$discipline[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $currentJournal->getSetting('metaSubjectClass')}
	<tr valign="top">
		<td colspan="2" class="label"><a href="{$currentJournal->getLocalizedSetting('metaSubjectClassUrl')|escape}" target="_blank">{$currentJournal->getLocalizedSetting('metaSubjectClassTitle')|escape}</a></td>
	</tr>
	<tr valign="top">
		<td width="20%"class="label">{translate key="article.subjectClassification"}</td>
		<td width="80%" class="value">{$subjectClass[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $currentJournal->getSetting('metaSubject')}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.subject"}</td>
		<td width="80%" class="value">{$subject[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $currentJournal->getSetting('metaCoverage')}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.coverageGeo"}</td>
		<td width="80%" class="value">{$coverageGeo[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageChron"}</td>
		<td class="value">{$coverageChron[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageSample"}</td>
		<td class="value">{$coverageSample[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $currentJournal->getSetting('metaType')}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.type"}</td>
		<td width="80%" class="value">{$type[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.language"}</td>
		<td width="80%" class="value">{$language|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="supportingAgencies">
<h3>{translate key="submission.supportingAgencies"}</h3>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.agencies"}</td>
		<td width="80%" class="value">{$sponsor[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

{include file="common/footer.tpl"}

