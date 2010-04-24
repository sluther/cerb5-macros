<table width="100%">
	{foreach from=$actions item=action key=action_id}
	<tr>
		<td valign="top">
			<input type="checkbox" id="{$action_id}" name="do[]" value="{$action->id}"{if isset($macro->actions.$action_id)} checked="checked"{/if}>{$action->name}:
		</td>
		{if $action_id == 'cerberusweb.macros.action.status'}
		{assign var=act_status value=$macro->actions.$action_id}
		<td>
			<!-- Status field -->
			<select name="do_status" onchange="document.getElementById('{$action_id}').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
				<option value="0" {if isset($act_status) && !$act_status.is_closed && !$act_status.is_deleted}selected="selected"{/if}>{$translate->_('status.open')|capitalize}</option>
				<option value="1" {if isset($act_status) && $act_status.is_closed && !$act_status.is_deleted}selected="selected"{/if}>{$translate->_('status.closed')|capitalize}</option>
				
				{* ticket fields *}
				{if $source == 'cerberusweb.macros.ticket'}
					<option value="3" {if isset($act_status) && !$act_status.is_closed && !$act_status.is_deleted && $act_status.is_waiting}selected="selected"{/if}>Waiting</option>
					<option value="2" {if isset($act_status) && $act_status.is_deleted}selected="selected"{/if}>Deleted</option>
				{/if}
			</select>
		</td>
		{elseif $action_id=='cerberusweb.macros.action.move'}
		{assign var=act_move value=$macro->actions.$action_id}
		<td>
			<!-- Move field -->
			<select name="do_move" onchange="document.getElementById('{$action_id}').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
	      		<optgroup label="Move to Group">
	      		{foreach from=$groups item=tm}
	      			{assign var=k value='t'|cat:$tm->id}
	      			<option value="{$k}" {if $tm->id==$act_move.group_id && 0==$act_move.bucket_id}selected="selected"{/if}>{$tm->name}</option>
	      		{/foreach}
	      		</optgroup>
	      		{foreach from=$team_categories item=categories key=teamId}
	      			{assign var=tm value=$groups.$teamId}
	      			<optgroup label="{$tm->name}">
	      			{foreach from=$categories item=category}
	      				{assign var=k value='c'|cat:$category->id}
	    				<option value="c{$category->id}" {if $category->id==$act_move.bucket_id}selected="selected"{/if}>{$category->name}</option>
	    			{/foreach}
	    			</optgroup>
	     		{/foreach}
			</select>
		</td>
		{elseif $action_id=='cerberusweb.macros.action.assign'}
		{assign var=act_assign value=$macro->actions.$action_id}
		<td>
			<!-- Assign field -->
			<select name="do_assign" onchange="document.getElementById('{$action_id}').checked=((''==selectValue(this))?false:true);">
				<option value=""></option>
				<option vale="0">Anybody</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=next_worker_id_sel equation="x+1" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $act_assign.worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select> 
	      	{if !empty($next_worker_id_sel)}
	      		<button type="button" onclick="this.form.do_assign.selectedIndex = {$next_worker_id_sel}; document.getElementById('{$action_id}').checked=(true)">me</button>
	      		<button type="button" onclick="this.form.do_assign.selectedIndex = 1; document.getElementById('{$action_id}').checked=(true)">anybody</button>
	      	{/if}
		</td>
		{/if}
	
	
	</tr>
	{/foreach}
</table>