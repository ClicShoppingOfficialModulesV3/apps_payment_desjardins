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

  namespace ClicShopping\Apps\Payment\Desjardins\Module\HeaderTags;

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\HTML;

  use ClicShopping\Apps\Catalog\Products\Products as ProductsApp;

  class Desjardins extends \ClicShopping\OM\Modules\HeaderTagsAbstract {

    protected $lang;
    protected $app;
    public $group;

    protected function init() {
      if (!Registry::exists('Products')) {
        Registry::set('Products', new ProductsApp());
      }

      $this->app = Registry::get('Products');
      $this->lang = Registry::get('Language');
      $this->group = 'header_tags'; // could be header_tags or footer_scripts

      $this->app->loadDefinitions('Module/HeaderTags/desjardins');

      $this->title = $this->app->getDef('module_header_tags_desjardins_title');
      $this->description = $this->app->getDef('module_header_tags_desjardins_description');

      if ( defined('MODULE_HEADER_TAGS_DESJARDINS_STATUS')) {
        $this->sort_order = (int)MODULE_HEADER_TAGS_DESJARDINS_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_DESJARDINS_STATUS == 'True');
      }
    }

    public function isEnabled() {
      return $this->enabled;
    }

    public function getOutput() {
      if (!defined('CLICSHOPPING_APP_DESJARDINS_HO_STATUS') || CLICSHOPPING_APP_DESJARDINS_HO_STATUS == 'False') {
        return false;
      }

      if (isset($_GET['Checkout']) && isset($_GET['Confirmation'])) {

        $meta = '<meta http-equiv="cache-control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0" /><br />';
        $meta .= '<meta http-equiv="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT" /><br />';
        $meta .= '<meta http-equiv="pragma" content="no-cache" /><br />';

          $output =
<<<EOD
{$meta}
EOD;
     }

      return $output;
    }

    public function Install() {
      $this->app->db->save('configuration', [
          'configuration_title' => 'Do you want to install this module ?',
          'configuration_key' => 'MODULE_HEADER_TAGS_DESJARDINS_STATUS',
          'configuration_value' => 'True',
          'configuration_description' => 'Do you want to install this module ?',
          'configuration_group_id' => '6',
          'sort_order' => '1',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
          'date_added' => 'now()'
        ]
      );


      $this->app->db->save('configuration', [
          'configuration_title' => 'Display sort order',
          'configuration_key' => 'MODULE_HEADER_TAGS_DESJARDINS_SORT_ORDER',
          'configuration_value' => '162',
          'configuration_description' => 'Display sort order (The lower is displayd in first)',
          'configuration_group_id' => '6',
          'sort_order' => '215',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );
    }

    public function keys() {
      return ['MODULE_HEADER_TAGS_DESJARDINS_STATUS',
              'MODULE_HEADER_TAGS_DESJARDINS_SORT_ORDER'
             ];
    }
  }
