{**
 * index.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal management index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.pendingUsers.title"}
{include file="common/header.tpl"}
{/strip}

{if $usersApproved}
	<p><strong style="color: red;">{translate key="manager.pendingUsers.usersApproved"}</strong></p>
{/if}

<table>
<tr>
	<th>Name</th>
	<th>Requested Role</th>
	<th>Date Requested</th>
	<th>Email</th>
	<th></th>
</tr>
{foreach from=$pendingUsers item=user}
<tr>
	<td>{$user.first_name} {$user.middle_name} {$user.last_name}</td>
	<td>{$user.role}</td>
	<td>{$user.date_registered}</td>
	<td>{$user.email}</td>
	<td><a href="?approve={$user.user_id}&role={$user.role_id}" title="Approve User Registration Request">Approve</a></td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}