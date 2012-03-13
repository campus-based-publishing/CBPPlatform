{**
 * googlescholar.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Metadata elements for articles based on preferred types for Google Scholar
 *
 * $Id$
 *}
	<meta name="gs_meta_revision" content="1.1" />
	<meta name="citation_journal_title" content="{$currentJournal->getLocalizedTitle()|strip_tags|escape}"/>
{if $currentJournal->getSetting('onlineIssn')}{assign var="issn" value=$currentJournal->getSetting('onlineIssn')}
{elseif $currentJournal->getSetting('printIssn')}{assign var="issn" value=$currentJournal->getSetting('printIssn')}
{elseif $currentJournal->getSetting('issn')}{assign var="issn" value=$currentJournal->getSetting('issn')}
{/if}
{if $issn}
	<meta name="citation_issn" content="{$issn|strip_tags|escape}"/>
{/if}
	<meta name="citation_authors" content="{foreach name="authors" from=$article->getAuthors() item=author}{$author->getLastName()|escape}, {$author->getFirstName()|escape}{if $author->getMiddleName() != ""} {$author->getMiddleName()|escape}{/if}{if !$smarty.foreach.authors.last}; {/if}{/foreach}"/>
	<meta name="citation_title" content="{$article->getLocalizedTitle()|strip_tags|escape}"/>

{**
 * Google Scholar date: Use article publication date, falling back on issue
 * year and issue publication date in sequence. Bug #6480.
 *}
{if $article->getDatePublished()}
	<meta name="citation_date" content="{$article->getDatePublished()|date_format:"%d/%m/%Y"}"/>
{elseif $issue->getYear()}
	<meta name="citation_date" content="{$issue->getYear()|escape}"/>
{elseif $issue->getDatePublished()}
	<meta name="citation_date" content="{$issue->getDatePublished()|date_format:"%d/%m/%Y"}"/>
{/if}

	<meta name="citation_volume" content="{$issue->getVolume()|strip_tags|escape}"/>
	<meta name="citation_issue" content="{$issue->getNumber()|strip_tags|escape}"/>
{if $article->getPages()}
	<meta name="citation_firstpage" content="{$article->getPages()|escape}"/>
{/if}
{if $issue->getPublished()}
	{assign var=doi value=$article->getDOI()}
{else}
	{assign var=doi value=$article->getDOI(true)}{* Don't affix DOI *}
{/if}
{if $doi}
	<meta name="citation_doi" content="{$doi|escape}"/>
{/if}
	<meta name="citation_abstract_html_url" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
{if $article->getLanguage()}
	<meta name="citation_language" content="{$article->getLanguage()|strip_tags|escape}"/>
{/if}
{if $article->getSubject(null)}{foreach from=$article->getSubject(null) key=metaLocale item=metaValue}
	{foreach from=$metaValue|explode:"; " item=gsKeyword}
		{if $gsKeyword}
			<meta name="citation_keywords" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$gsKeyword|escape}"/>
		{/if}
	{/foreach}
{/foreach}{/if}
{foreach from=$article->getGalleys() item=gs_galley}
{if $gs_galley->getFileType()=="application/pdf"}
	<meta name="citation_pdf_url" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$gs_galley->getBestGalleyId($currentJournal)}"/>
{else}
	<meta name="citation_fulltext_html_url" content="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$gs_galley->getBestGalleyId($currentJournal)}"/>
{/if}
{/foreach}

