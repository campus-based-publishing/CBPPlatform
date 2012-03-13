{**
 * subscriptionForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common subscription fields
 *
 * $Id$
 *}

<script type="text/javascript">
<!--
{literal}
function chooseEndDate() {
	var lengths = {{/literal}
		{* Build up an array of typeId => Duration in Javascript land *}
		{foreach from=$subscriptionTypes item=subscriptionType}
			{if !$subscriptionType->getNonExpiring()}
				{$subscriptionType->getTypeId()}: "{$subscriptionType->getDuration()|escape:"javascript"}",
			{/if}
		{/foreach}
	{literal}};

	var selectedTypeIndex = document.subscriptionForm.typeId.selectedIndex;
	var selectedTypeId = document.subscriptionForm.typeId.options[selectedTypeIndex].value;

	if (typeof(lengths[selectedTypeId]) != "undefined") {
		var duration = lengths[selectedTypeId];
		var dateStart = new Date(
			document.subscriptionForm.dateStartYear.options[document.subscriptionForm.dateStartYear.selectedIndex].value,
			document.subscriptionForm.dateStartMonth.options[document.subscriptionForm.dateStartMonth.selectedIndex].value - 1,
			document.subscriptionForm.dateStartDay.options[document.subscriptionForm.dateStartDay.selectedIndex].value,
			0, 0, 0
		);
		var dateEnd = dateStart;

		var months = duration % 12;
		var years = Math.floor(duration / 12);

		if (months + dateStart.getMonth() > 11) {
			dateEnd.setFullYear(dateStart.getFullYear()+1);
		}
		dateEnd.setFullYear(dateEnd.getFullYear() + years);
		dateEnd.setMonth((dateStart.getMonth() + months) % 12);

		// dateEnd now contains the calculated date of the subscription expiry.
		document.subscriptionForm.dateEndDay.selectedIndex = dateEnd.getDate() - 1;
		document.subscriptionForm.dateEndMonth.selectedIndex = dateEnd.getMonth();

		var i;
		for (i=0; i < document.subscriptionForm.dateEndYear.length; i++) {
			if (document.subscriptionForm.dateEndYear.options[i].value == dateEnd.getFullYear()) {
				document.subscriptionForm.dateEndYear.selectedIndex = i;
				break;
			}
		}
	}
}
{/literal}
// -->
</script>

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="status" required="true" key="manager.subscriptions.form.status"}</td>
	<td width="80%" class="value"><select name="status" id="status" class="selectMenu">
	{html_options_translate options=$validStatus selected=$status}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="typeId" required="true" key="manager.subscriptions.form.typeId"}</td>
	<td class="value"><select name="typeId" id="typeId" class="selectMenu" onchange="chooseEndDate()">
		{foreach from=$subscriptionTypes item=subscriptionType}
			<option value="{$subscriptionType->getTypeId()}"{if $typeId == $subscriptionType->getTypeId()} selected="selected"{/if}>{$subscriptionType->getSummaryString()|escape}</option>
		{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateStart" key="manager.subscriptions.form.dateStart"}</td>
	<td class="value" id="dateStart">{html_select_date prefix="dateStart" all_extra="class=\"selectMenu\" onchange=\"chooseEndDate()\"" start_year="$yearOffsetPast" end_year="$yearOffsetFuture" time="$dateStart"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateEnd" key="manager.subscriptions.form.dateEnd"}</td>
	<td class="value" id="dateEnd">
		{html_select_date prefix="dateEnd" start_year="$yearOffsetPast" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" time="$dateEnd"}
		<input type="hidden" name="dateEndHour" value="23" />
		<input type="hidden" name="dateEndMinute" value="59" />
		<input type="hidden" name="dateEndSecond" value="59" />
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="membership" key="manager.subscriptions.form.membership"}</td>
	<td class="value">
		<input type="text" name="membership" value="{$membership|escape}" id="membership" size="30" maxlength="40" class="textField" />
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="referenceNumber" key="manager.subscriptions.form.referenceNumber"}</td>
	<td class="value">
		<input type="text" name="referenceNumber" value="{$referenceNumber|escape}" id="referenceNumber" size="30" maxlength="40" class="textField" />
	</td>
</tr>

{* For new subscriptions, select end date for default subscription type *}
{if !$subscriptionId}
	<script type="text/javascript">
	<!--
	chooseEndDate();
	// -->
	</script>
{/if}
