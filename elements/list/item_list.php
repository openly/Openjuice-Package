<?php
defined('C5_EXECUTE') or die(_("Access Denied."));
echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper($list->getTitle(), false, false, false);
?>
<div class="ccm-pane-body">
  <?php echo $list->getAddButton(); ?>
  <div style='clear:both;'></div><br />
  <table class='ccm-results-list'>
    <tr>
      <?php foreach ($list->getHeaders() as $header) {?>
      <th><?php echo $header; ?></th>
      <?php }?>
    </tr>
    <?php foreach ($list->getRecords() as $recFields) {?>
    <tr class='ccm-list-record'>
      <?php foreach ($recFields as $field) {?>
      <td><?php echo $field; ?></td>
      <?php }?>
    </tr>
    <?php }?>
  </table>
</div>
<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper(false);?>
