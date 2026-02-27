{* template block that contains the new field *}
<table>
  <tr class="wpcvmm_edit_block">
    <td class="label"><label for="wpcvmm_edit">{$wpcvmm_label}</label></td>
    <td>
      <p class="description">{$wpcvmm_description}</p>
      {if $wpcvmm_url}
        <p class="mm-channel-url">{$wpcvmm_url} <span class="mm-channel-edit">{$wpcvmm_change}</span></p>
      {/if}
      {if $form.wpcvmm_channel_id}
        <div class="mm-channel-change">
          <div class="label">{$form.wpcvmm_channel_id.label}</div>
          <div class="content">{$form.wpcvmm_channel_id.html}</div>
          <p class="description">{$wpcvmm_advice}</p>
          <div class="clear"></div>
        </div>
      {/if}
    </td>
  </tr>
</table>

{* reposition the above block after #someOtherBlock *}
<style type="text/css">
  .mm-channel-edit {
    cursor: pointer;
    text-decoration: underline;
  }
  .mm-channel-change {
    display: none;
  }
</style>
<script type="text/javascript">
  // jQuery will not move an item unless it is wrapped.
  cj('tr.wpcvmm_edit_block').insertBefore('.crm-group-form-block .crm-group-form-block-group_type');
  cj('.mm-channel-edit').on('click', function() {
    cj('.mm-channel-change').toggle();
  });
</script>
