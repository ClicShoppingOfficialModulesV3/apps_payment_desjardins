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

  use ClicShopping\OM\Registry;

  class DeleteAll extends \ClicShopping\OM\PagesActionsAbstract {
    public function execute()  {
      $CLICSHOPPING_MessageStack = Registry::get('MessageStack');
      $CLICSHOPPING_Desjardins = Registry::get('Desjardins');

      $CLICSHOPPING_Desjardins->db->delete('desjardins_reference');

      $CLICSHOPPING_MessageStack->add($CLICSHOPPING_Desjardins->getDef('alert_delete_success'), 'success', 'Desjardins');

      $CLICSHOPPING_Desjardins->redirect('Log');
    }
  }
