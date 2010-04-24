<div id="macroSetup"></div>
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=config&a=handleTabAction&tab=macros.config.tab&action=showMacroPanel&id=0',null,false,'550');"><span class="cerb-sprite sprite-funnel"></span> Add Macro</button>
</form>
{if !empty($macros)}
<div class="block" id="configMacroAction">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleTabAction">
	<input type="hidden" name="tab" value="macros.config.tab">
	<input type="hidden" name="action" value="saveTab">

	<h2>Macros</h2>
	<br>


	<table cellspacing="2" cellpadding="2">
		<tr>
			<td><b>Macro</b></td>
			<td align="center"><b>{$translate->_('common.remove')|capitalize}</b></td>
		</tr>
		{counter start=0 print=false name=order}
		{foreach from=$macros item=macro key=macro_id name=macros}
			<tr>
				<td style="{if $macro->is_sticky}background-color:rgb(255,255,221);border:2px solid rgb(255,215,0);{else}{/if}padding:5px;">
					<a href="javascript:;" onclick="genericAjaxPanel('c=config&a=handleTabAction&tab=macros.config.tab&action=showMacroPanel&id={$macro_id}',null,false,'550');" style="color:rgb(0,120,0);font-weight:bold;">{$macro->name|escape}</a>
					<br>
					
					{foreach from=$macro->actions item=act key=action_key}
						
					{/foreach}
					
					<blockquote style="margin:2px;margin-left:20px;font-size:95%;color:rgb(100,100,100);">
						{foreach from=$macro->actions item=action key=action_key}
							{if $action_key=="status"}
								{if $action.is_deleted==1}Delete Ticket{elseif $action.is_closed==1}Close Ticket{elseif $action.is_waiting==1}Waiting for Reply{else}Open Ticket{/if}<br>
							{elseif $action_key=="move"}
								{assign var=g_id value=$action.group_id}
								{assign var=b_id value=$action.bucket_id}
								{if isset($groups.$g_id) && (0==$b_id || isset($buckets.$b_id))}
									Move to 
									<b>{$groups.$g_id->name}</b>:
									<b>{if 0==$b_id}Inbox{else}{$buckets.$b_id->name}{/if}</b>
								{/if}
								<br>
							{elseif $action_key=="assign"}
								{assign var=worker_id value=$action.worker_id}
								{if isset($workers.$worker_id)}
									Assign to <b>{$workers.$worker_id->getName()}</b><br>
								{/if}
							{elseif $action_key=="spam"}
								{if $action.is_spam}Report Spam{else}Mark Not Spam{/if}<br>
							{elseif 0==strcasecmp('cf_',substr($action_key,0,3))}
								{include file="$core_tpl/internal/custom_fields/filters/render_action_list.tpl"}
							{/if}
						{/foreach}
					</blockquote>
				</td>
				<td valign="top" align="center">
					<label><input type="checkbox" name="deletes[]" value="{$macro_id}">
					<input type="hidden" name="ids[]" value="{$macro_id}">
				</td>
			</tr>
		{/foreach}
	</table>
	<br>	
		<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
		</form>
	</div>
	<br>
{/if}


