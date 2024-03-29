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

  namespace ClicShopping\Apps\Payment\Desjardins\Sites\ClicShoppingAdmin\Pages\Home\Actions;

  use ClicShopping\OM\Registry;

  class Log extends \ClicShopping\OM\PagesActionsAbstract {
    public function execute()  {
      $CLICSHOPPING_Desjardins = Registry::get('Desjardins');

      $this->page->setFile('log.php');
      $this->page->data['action'] = 'Log';

      $CLICSHOPPING_Desjardins->loadDefinitions('ClicShoppingAdmin/log');
    }
  }
