{* template block that contains the new field *}
<table>
  <tr class="wpcvmm_edit_block">
    <td class="label"><label for="wpcvmm_edit">{$wpcvmm_label}</label></td>
    <td><span class="description">{$wpcvmm_description}{$wpcvmm_url}</span></td>
  </tr>
</table>

{* reposition the above block after #someOtherBlock *}
<script type="text/javascript">
  // jQuery will not move an item unless it is wrapped
  cj('tr.wpcvmm_edit_block').insertBefore('.crm-group-form-block .crm-group-form-block-group_type');
</script>
