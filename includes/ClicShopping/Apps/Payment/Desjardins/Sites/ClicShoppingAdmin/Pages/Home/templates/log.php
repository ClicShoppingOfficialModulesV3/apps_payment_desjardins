<?php
/**
 *
 *  @copyright 2008 - https://www.clicshopping.org
 *  @Brand : ClicShopping(Tm) at Inpi all right Reserved
 *  @Licence GPL 2 & MIT
 *  @licence MIT - Portion of osCommerce 2.4
 *  @Info : https://www.clicshopping.org/forum/trademark/
 *
 */

use ClicShopping\OM\HTML;
  use ClicShopping\OM\CLICSHOPPING;

  require_once(__DIR__ . '/template_top.php');

  $Qlog = $CLICSHOPPING_Desjardins->db->prepare('select SQL_CALC_FOUND_ROWS l.ref_id,
                                                                            l.ref_number,
                                                                            l.order_id,
                                                                            l.date,
                                                                            l.TPE,
                                                                            l.montant,
                                                                            l.code_retour,
                                                                            l.retourPlus,
                                                                            l.ipclient,
                                                                            l.brand,
                                                                            o.customers_name,
                                                                            o.customers_id
                                                  from :table_desjardins_reference l left join :table_orders o on (l.order_id = o.orders_id)
                                                  order by l.ref_id desc
                                                  limit :page_set_offset,
                                                        :page_set_max_results
                                                  ');
  $Qlog->setPageSet(MAX_DISPLAY_SEARCH_RESULTS);
  $Qlog->execute();
?>

<div class="text-end">
  <?php echo HTML::button($CLICSHOPPING_Desjardins->getDef('button_dialog_delete'), null, '#', 'danger', ['params' => 'data-button="delLogs"']); ?>
</div>
<div class="separator"></div>
<table id="ppTableLog" class="table table-hover">
  <thead>
    <tr class="dataTableHeadingRow">
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_ref_id'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_heading_date'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_order_id'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_heading_ip'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_heading_amount'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_heading_customer'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_heading_response'); ?></td>
      <td class="text-center"><?php echo $CLICSHOPPING_Desjardins->getDef('table_heading_brand'); ?></td>
    </tr>
  </thead>
  <tbody>

<?php
if ($Qlog->getPageSetTotalRows() > 0) {
    while ($Qlog->fetch()) {
      $customers_name = $Qlog->value('customers_name');

      if ($Qlog->value('brand') == 'na') {
        $brand = 'not available';
      } elseif ($Qlog->value('brand') == 'AM') {
        $brand = 'American Express';
      } elseif ($Qlog->value('brand') == 'MC') {
        $brand = 'Mastercard';
      } elseif ($Qlog->value('brand') == 'VI') {
        $brand = 'Visa';
      } elseif ($Qlog->value('brand') == 'MC') {
        $brand = 'Mastercard';
      }
?>
    <tr>
      <td><span class="label <?php echo ($Qlog->valueInt('result') === 1) ? 'label-success' : 'label-danger'; ?>"><?php echo $Qlog->value('ref_id'); ?></span></td>
      <td><?php echo $Qlog->value('date'); ?></td>
      <td class="text-end"><?php echo $Qlog->value('order_id'); ?></td>
      <td class="text-end"><?php echo $Qlog->value('ipclient'); ?></td>
      <td class="text-end"><?php echo $Qlog->value('montant'); ?></td>
      <td class="text-end"><?php echo (!empty($customers_name)) ? HTML::outputProtected($customers_name) : '<i>' . $CLICSHOPPING_Desjardins->getDef('guest') . '</i>'; ?></td>
      <td class="text-end"><?php echo $Qlog->value('code_retour'); ?></td>
      <td class="text-end"><?php echo $brand; ?></td>
    </tr>

<?php
    }
  } else {
?>

    <tr>
      <td colspan="6"><?php echo $CLICSHOPPING_Desjardins->getDef('no_entries'); ?></td>
    </tr>

<?php
  }
?>

  </tbody>
</table>

<div>
  <span class="float-end"><?php echo $Qlog->getPageSetLinks(CLICSHOPPING::getAllGET(array('page'))); ?></span>
  <?php echo $Qlog->getPageSetLabel($CLICSHOPPING_Desjardins->getDef('listing_number_of_log_entries')); ?>
</div>

<div id="delLogs-dialog-confirm" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $CLICSHOPPING_Desjardins->getDef('dialog_delete_title'); ?></h4>
      </div>

      <div class="modal-body">
        <p><?php echo $CLICSHOPPING_Desjardins->getDef('dialog_delete_body'); ?></p>
      </div>

      <div class="modal-footer">
        <?php echo HTML::button($CLICSHOPPING_Desjardins->getDef('button_delete'), null, $CLICSHOPPING_Desjardins->link('Log&DeleteAll'), 'danger'); ?>
        <?php echo HTML::button($CLICSHOPPING_Desjardins->getDef('button_cancel'), null, '#', 'warning', ['params' => 'data-dismiss="modal"']); ?>
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
  $('a[data-button="delLogs"]').click(function(e) {
    e.preventDefault();

    $('#delLogs-dialog-confirm').modal('show');
  });
});
</script>

<?php
require_once(__DIR__ . '/template_bottom.php');