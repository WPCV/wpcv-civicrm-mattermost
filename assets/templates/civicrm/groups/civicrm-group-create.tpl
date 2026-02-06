{* template block that contains the new fields *}
<table>
  <tr class="wpcvmm-channel-create">
    <td class="label">{$form.wpcvmm_channel_create.label}</td>
    <td>{$form.wpcvmm_channel_create.html} <span class="description">{$wpcvmm_channel_description}</span></td>
  </tr>
  <tr class="wpcvmm-channel-type" style="display: none;">
    <td class="label">{$form.wpcvmm_channel_type.label}</td>
    <td>{$form.wpcvmm_channel_type.html} <span class="description">{$wpcvmm_type_description}</span></td>
  </tr>
</table>

{literal}
  <script type="text/javascript">

    // Define vars.
    var wpcvm_create = cj('tr.wpcvmm-channel-create'),
        wpcvm_type = cj('tr.wpcvmm-channel-type'),
        wpcvm_checkbox = cj('#wpcvmm_channel_create');

    /*
     * Reposition the above blocks after #someOtherBlock.
     * Note: jQuery will not move an item unless it is wrapped.
     */
    wpcvm_create.insertBefore('.crm-group-form-block .crm-group-form-block-group_type');
    wpcvm_type.insertBefore('.crm-group-form-block .crm-group-form-block-group_type');

    /**
     * Add a change event listener to the "Schedule Interval" checkbox.
     *
     * @since 1.0.0
     *
     * @param {Object} event The event object.
     */
    wpcvm_checkbox.on('change', function(event) {
      if (wpcvm_checkbox.is(":checked")) {
        wpcvm_type.show();
      } else {
        wpcvm_type.hide();
      }
    } );

  </script>
{/literal}
