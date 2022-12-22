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


  namespace ClicShopping\Apps\Payment\Desjardins\Module\ClicShoppingAdmin\Config\HO\Params;

  use ClicShopping\OM\HTML;

  class server extends \ClicShopping\Apps\Payment\Desjardins\Module\ClicShoppingAdmin\Config\ConfigParamAbstract {
    public $default = 'Test';
    public $sort_order = 80;

    protected function init() {
        $this->title = $this->app->getDef('cfg_desjardins_server_test_title');
        $this->description = $this->app->getDef('cfg_desjardins_server_test_description');
    }

    public function getInputField()  {
      $value = $this->getInputValue();

      $input =  HTML::radioField($this->key, 'Test', $value, 'id="' . $this->key . '1" autocomplete="off"') . $this->app->getDef('cfg_desjardins_server_test') . ' ';
      $input .=  HTML::radioField($this->key, 'Production', $value, 'id="' . $this->key . '2" autocomplete="off"') . $this->app->getDef('cfg_desjardins_server_production');

      return $input;
    }
  }