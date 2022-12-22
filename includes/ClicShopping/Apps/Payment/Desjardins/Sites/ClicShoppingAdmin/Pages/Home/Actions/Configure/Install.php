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

  namespace ClicShopping\Apps\Payment\Desjardins\Sites\ClicShoppingAdmin\Pages\Home\Actions\Configure;

  use ClicShopping\OM\Registry;

  use ClicShopping\OM\Cache;
  use ClicShopping\OM\CLICSHOPPING;

  class Install extends \ClicShopping\OM\PagesActionsAbstract {

    public function execute() {

      $CLICSHOPPING_MessageStack = Registry::get('MessageStack');
      $CLICSHOPPING_Desjardins = Registry::get('Desjardins');

      $current_module = $this->page->data['current_module'];

      $m = Registry::get('DesjardinsAdminConfig' . $current_module);
      $m->install();

      $CLICSHOPPING_MessageStack->add($CLICSHOPPING_Desjardins->getDef('alert_module_install_success'), 'success', 'Desjardins');


      $this->installDesjardinBD();
      $this->installDbMenuAdministration();

      $CLICSHOPPING_Desjardins->redirect('Configure&module=' . $current_module);
    }

    private function installDesjardinBD() {
      $CLICSHOPPING_Db = Registry::get('Db');
      $CLICSHOPPING_Desjardins = Registry::get('Desjardins');

      $this->app = $CLICSHOPPING_Desjardins;

      $Qcheck = $CLICSHOPPING_Db->query('show tables like ":table_desjardins_reference"');

      if ($Qcheck->fetch() === false) {
        $sql = <<<EOD
CREATE TABLE :table_desjardins_reference (
  ref_id int(11) NOT NULL auto_increment,
  ref_number varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  order_id int(11) NOT NULL DEFAULT 0,
  date varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  TPE varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  montant varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  code_retour varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  retourPLUS varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  text_libre varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  ipclient varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  brand  varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  session_id  varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (ref_id),
  KEY idx_oapl_ref_id (ref_id)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
EOD;
        $CLICSHOPPING_Db->exec($sql);
      }
    }


    private static function installDbMenuAdministration() {
      $CLICSHOPPING_Db = Registry::get('Db');
      $CLICSHOPPING_Language = Registry::get('Language');
      $Qcheck = $CLICSHOPPING_Db->get('administrator_menu', 'app_code', ['app_code' => 'app_payment_desjardins']);

      if ($Qcheck->fetch() === false) {

        $sql_data_array = ['sort_order' => 4,
                           'link' => 'index.php?A&Payment\Desjardins&Configure',
                           'image' => 'modules_payment.gif',
                           'b2b_menu' => 0,
                           'access' => 1,
                           'app_code' => 'app_payment_desjardins'
        ];

        $insert_sql_data = ['parent_id' => 186];

        $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

        $CLICSHOPPING_Db->save('administrator_menu', $sql_data_array);

        $id = $CLICSHOPPING_Db->lastInsertId();

        $languages = $CLICSHOPPING_Language->getLanguages();

        for ($i=0, $n=count($languages); $i<$n; $i++) {

          $language_id = $languages[$i]['id'];

          $sql_data_array = ['label' => 'Desjardins'];

          $insert_sql_data = ['id' => (int)$id,
                              'language_id' => (int)$language_id
          ];

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          $CLICSHOPPING_Db->save('administrator_menu_description', $sql_data_array );

        }

        Cache::clear('menu-administrator');
      }
    }
  }
