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

  namespace ClicShopping\Apps\Payment\Desjardins\Sites\ClicShoppingAdmin\Pages\Home;

  use ClicShopping\OM\Registry;


  use ClicShopping\OM\Cache;

  use ClicShopping\Apps\Payment\Desjardins\Desjardins;

  class Home extends \ClicShopping\OM\PagesAbstract {
    public mixed $app;

    protected function init() {
      $CLICSHOPPING_Desjardins = new Desjardins();
      Registry::set('Desjardins', $CLICSHOPPING_Desjardins);

      $this->app = $CLICSHOPPING_Desjardins;

      $this->app->loadDefinitions('Sites/ClicShoppingAdmin/main');
      $this->app->loadDefinitions('Sites/ClicShoppingAdmin/log');
    }
  }
