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

  class simulation extends \ClicShopping\Apps\Payment\Desjardins\Module\ClicShoppingAdmin\Config\ConfigParamAbstract {
    public $default = 'False';
    public $sort_order = 90;

    protected function init() {
        $this->title = $this->app->getDef('cfg_desjardins_simulation_test_title');
        $this->description = $this->app->getDef('cfg_desjardins_simulation_test_description');
    }

    public function getInputField()  {
      $value = $this->getInputValue();

      $input =  HTML::radioField($this->key, 'True', $value, 'id="' . $this->key . '1" autocomplete="off"') . $this->app->getDef('cfg_desjardins_simulation_test_true') . ' ';
      $input .=  HTML::radioField($this->key, 'False', $value, 'id="' . $this->key . '2" autocomplete="off"') . $this->app->getDef('cfg_desjardins_simulation_test_false');

      return $input;
    }
  }