{**
 * view.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a help topic.
 *
 * $Id$
 *}
{strip}
{translate|assign:applicationHelpTranslated key="help.ojsHelp" parentSiteTitle=$parentSiteTitle}
{include file="core:help/view.tpl"}
{/strip}
