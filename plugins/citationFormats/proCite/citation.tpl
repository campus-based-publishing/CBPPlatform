{**
 * citation.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ProCite citation format generator
 *
 * $Id$
 *}
{if $galleyId}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId|to_array:$galleyId}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId}
{/if}
TY  - JOUR
{foreach from=$article->getAuthors() item=author}
AU  - {$author->getFullName(true)|escape}
{/foreach}
{if $article->getDatePublished()}
PY  - {$article->getDatePublished()|date_format:"%Y"}
{elseif $issue->getDatePublished()}
PY  - {$issue->getDatePublished()|date_format:"%Y"}
{else}
PY  - {$issue->getYear()|escape}
{/if}
TI  - {$article->getLocalizedTitle()|strip_tags}
JF  - {$journal->getLocalizedTitle()}{if $issue}; {$issue->getIssueIdentification()|strip_tags}{/if}

KW  - {$article->getLocalizedSubject()|escape}
N2  - {$article->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
UR  - {$articleUrl}
