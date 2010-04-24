<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmMacro">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="macros.config.tab">
<input type="hidden" name="action" value="saveMacroPanel">

<input type="hidden" name="group_id" value="{$group_id}">
<input type="hidden" name="id" value="{$macro->id}">
{if !empty($view_id)}<input type="hidden" name="view_id" value="{$view_id}">{/if}

<b>Macro Name:</b> (e.g. Close Tickets)<br>
<input type="text" name="name" value="{$macro->name|escape}" size="45" style="width:95%;"><br>


<table width="100%">
	<tr>
		<td valign="top">
			<h2>Macro object:</h2>
			<select name="source_ext_id" onchange="genericAjaxGet('divSourceActions','c=config&a=handleTabAction&tab=macros.config.tab&action=showSourceActions&ext_id='+escape(selectValue(this))+'&macro_id={$macro->id}');">
				{foreach from=$sources item=source key=source_id}
				<option value="{$source_id}"{if $macro->source_extension_id == $source_id} selected="selected"{/if}>{$source->name}</option>
				{/foreach}
				<!-- plugin contributed sources -->
				{* foreach from=$ext_sources item=ext_source key=ext_source_key*}
				<!-- <option value="{$ext_source}"{if $macro->source == $ext_source_key} selected="selected"{/if}>{$ext_source->name}</option> -->
				{* /foreach *}
			</select>
			<h2>Actions:</h2>
			<blockquote id="divSourceActions" style="margin:5px;background-color:rgb(255,255,255);padding:5px;border:1px dotted rgb(120,120,120);display:{if 1}block{else}none{/if};">
				<!-- Ticket actions -->


				{if !empty($source_ext) && is_a($source_ext,'Extension_MacroSource')}
					{$source_ext->renderConfig($macro, $source_ext->manifest->id)}
				{/if}

			</blockquote>
		</td>
	</tr>
</table>




{if !empty($view_id)}
	<button type="button" onclick="ajax.postAndReloadView('frmMacro','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{else}
	<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
{/if}
</form>
<br>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"Add Macro");
	} );
</script>
