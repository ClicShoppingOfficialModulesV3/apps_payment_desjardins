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

namespace ClicShopping\Apps\Payment\Desjardins\Module\Hooks\ClicShoppingAdmin\Orders;

  use ClicShopping\OM\HTML;
  use ClicShopping\OM\Registry;

  use ClicShopping\Apps\Payment\Desjardins\Desjardins as DesjardinsApp;

  class PageTab implements \ClicShopping\OM\Modules\HooksInterface {
    protected $app;

    public function __construct()  {
      if (!Registry::exists('Desjardins')) {
          Registry::set('Desjardins', new DesjardinsApp());
      }

      $this->app = Registry::get('Desjardins');
    }

    public function display()  {

      if (!defined('CLICSHOPPING_APP_DESJARDINS_HO_STATUS')) {
          return false;
      }

      $this->app->loadDefinitions('hooks/ClicShoppingAdmin/orders/page_tab');

      $output = '';

      $Qc = $this->app->db->prepare('select ref_id,
                                            ref_number,
                                            order_id,
                                            date,
                                            TPE,
                                            montant,
                                            code_retour,
                                            retourPlus,
                                            ipclient,
                                            brand
                                     from :table_desjardins_reference
                                     where order_id = :order_id
                                     order by ref_id desc
                                   ');
      $Qc->bindInt(':order_id', (int)$_GET['oID']);
      $Qc->execute();

      if ($Qc->fetch() !== false) {
        if (CLICSHOPPING_APP_DESJARDINS_HO_SERVER == 'Test') {
          $dejardins_button = HTML::button($this->app->getDef('button_view_at_desjardins'), null, 'https://www.monetico-services.com/fr/test/identification/authentification.html', 'info', ['newwindow' => 'blank']);
        } else {
          $dejardins_button = HTML::button($this->app->getDef('button_view_at_desjardins'), null, 'https://www.monetico-services.com/fr/identification/authentification.html', 'info', ['newwindow' => 'blank']);
        }

         $tab_title = addslashes($this->app->getDef('tab_title'));

         $content = '
          <div class="separator"></div>
            <div class="col-md-12">
              <table border="0" width="100%" cellspacing="0" cellpadding="2">
                <td>
                  <table class="table table-sm table-hover">
                    <thead>
                      <tr class="dataTableHeadingRow">
                        <td>' .  $this->app->getDef('table_heading_ref_number') . '</td>
                        <td>' .  $this->app->getDef('table_heading_date') . '</td>
                        <td>' .  $this->app->getDef('table_heading_order_id') . '&nbsp;</td>
                        <td>' .  $this->app->getDef('table_heading_tpe') . '</td>
                        <td>' .  $this->app->getDef('table_heading_montant') . '&nbsp;</td>
                        <td>' .  $this->app->getDef('table_heading_code_retour') . '&nbsp;</td>
                        <td>' .  $this->app->getDef('table_heading_retour_plus') . '</td>
                        <td>' .  $this->app->getDef('table_heading_ip_customer') . '&nbsp;</td>
                      </tr>
                    </thead>
                    <tbody>
                      <td>' . $Qc->value('ref_number') . '</td>
                      <td>' . $Qc->value('date') . '</td>
                      <td>' . $Qc->valueInt('order_id'). '</td>
                      <td>' . $Qc->value('TPE') . '</td>
                      <td>' . $Qc->value('montant') . '</td>
                      <td>' . $Qc->value('code_retour') . '</td>
                      <td>' . $Qc->value('retourPLUS') . '</td>
                      <td>' . $Qc->value('ipclient') . '</td>
                    </tbody>
                  </table>
                </td>
              </table>
            </div>
          </div>
         ';

         $output = <<<EOD
<div class="tab-pane" id="section_DesjardinsApp_content">
  <div class="mainTitle"></div>
  <div class="adminformTitle">
  <div class="separator"></div>
    {$dejardins_button}
    {$content}
  </div>
</div>

<script>
$('#section_DesjardinsApp_content').appendTo('#orderTabs .tab-content');
$('#orderTabs .nav-tabs').append('    <li class="nav-item"><a data-target="#section_DesjardinsApp_content" role="tab" data-toggle="tab" class="nav-link">{$tab_title}</a></li>');
</script>
EOD;

        }
      return $output;
    }

}
