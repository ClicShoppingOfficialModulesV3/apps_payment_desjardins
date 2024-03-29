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

  namespace ClicShopping\Apps\Payment\Desjardins\Sites\ClicShoppingAdmin\Pages\Home\Actions\Log;

  class View extends \ClicShopping\OM\PagesActionsAbstract {
    public function execute()  {
      if (isset($_GET['lID']) && is_numeric($_GET['lID'])) {

        $Qlog = $this->page->app->db->prepare('select l.*,
                                                unix_timestamp(o.date_purchased) as date_added,
                                                o.customers_name
                                                from :table_desjardins_reference l
                                                left join :table_orders c on (l.order_id = o.orders_id)
                                                where id = :id
                                              ');
        $Qlog->bindInt(':id', $_GET['lID']);
        $Qlog->execute();

        if ($Qlog->fetch() !== false) {
          $this->page->data['log_request'] = [];

          $req = explode("\n", $Qlog->value('request'));

          foreach ($req as $r) {
              $p = explode(':', $r, 2);

              $this->page->data['log_request'][$p[0]] = $p[1];
          }

          $this->page->data['log_response'] = [];

          $res = explode("\n", $Qlog->value('response'));

          foreach ($res as $r) {
              $p = explode(':', $r, 2);

              $this->page->data['log_response'][$p[0]] = $p[1];
          }

          $this->page->setFile('log_view.php');
        }
      }
    }
  }
