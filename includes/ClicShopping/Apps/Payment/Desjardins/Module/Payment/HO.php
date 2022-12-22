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

  namespace ClicShopping\Apps\Payment\Desjardins\Module\Payment;

  use ClicShopping\OM\HTTP;
  use ClicShopping\OM\HTML;
  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;

  use ClicShopping\Apps\Payment\Desjardins\API\HMAC;
  use ClicShopping\Apps\Payment\Desjardins\API\TPE;

  use ClicShopping\Apps\Payment\Desjardins\Desjardins as DesjardinsApp;

  use ClicShopping\Sites\Common\B2BCommon;

  define ("MONETICOPAIEMENT_CTLHMAC","V4.0.sha1.php--[CtlHmac%s%s]-%s");
  define ("MONETICOPAIEMENT_CTLHMACSTR", "CtlHmac%s%s");
  define ("MONETICOPAIEMENT_PHASE2BACK_RECEIPT","version=2\ncdr=%s");
  define ("MONETICOPAIEMENT_PHASE2BACK_MACOK","0");
  define ("MONETICOPAIEMENT_PHASE2BACK_MACNOTOK","1\n");
  define ("MONETICOPAIEMENT_PHASE2BACK_FIELDS", "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*");
  define ("MONETICOPAIEMENT_PHASE1GO_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s");

  class HO implements \ClicShopping\OM\Modules\PaymentInterface  {

    public $code;
    public $title;
    public $description;
    public $enabled;
    public mixed $app;

    public function __construct() {
      $CLICSHOPPING_Customer = Registry::get('Customer');

      if (Registry::exists('Order')) {
        $CLICSHOPPING_Order = Registry::get('Order');
      }

      if (!Registry::exists('Desjardins')) {
        Registry::set('Desjardins', new DesjardinsApp());
      }

      $this->app = Registry::get('Desjardins');
      $this->app->loadDefinitions('Module/Shop/HO/HO');

      $this->signature = 'Desjardins|' . $this->app->getVersion() . '|CMCiC|CMCiC|3.0';
      $this->api_version = $this->app->getApiVersion();

      $this->code = 'HO';
      $this->title = $this->app->getDef('module_desjardins_title');
      $this->public_title = $this->app->getDef('module_desjardins_public_title');

// Activation module du paiement selon les groupes B2B

      if (defined('CLICSHOPPING_APP_DESJARDINS_HO_STATUS')) {
        if ($CLICSHOPPING_Customer->getCustomersGroupID() != 0) {
          if ( B2BCommon::getPaymentUnallowed($this->code)) {
            if (CLICSHOPPING_APP_DESJARDINS_HO_STATUS == 'True') {
              $this->enabled = true;
            }  else {
              $this->enabled = false;
            }
          }
        } else {
          if (CLICSHOPPING_APP_DESJARDINS_HO_NO_AUTHORIZE == 'True' && $CLICSHOPPING_Customer->getCustomersGroupID() == 0) {
            if ($CLICSHOPPING_Customer->getCustomersGroupID() == 0) {
              if (CLICSHOPPING_APP_DESJARDINS_HO_STATUS == 'True') {
                $this->enabled = true;
              }  else {
                $this->enabled = false;
              }
            }
          }
        }

        if ((int)CLICSHOPPING_APP_DESJARDINS_HO_PREPARE_ORDER_STATUS_ID > 0) {
          $this->order_status = CLICSHOPPING_APP_DESJARDINS_HO_PREPARE_ORDER_STATUS_ID;
        }

  // server connexion
        if (CLICSHOPPING_APP_DESJARDINS_HO_SERVER == 'Production') {
          $this->form_action_url = 'https://p.monetico-services.com/paiement.cgi/';
        } else {
          $this->form_action_url = 'https://p.monetico-services.com/test/paiement.cgi/';
        }

        if ( $this->enabled === true ) {
          if ( isset($CLICSHOPPING_Order) && is_object($CLICSHOPPING_Order)) {
            $this->update_status();
          }
        }

        $this->sort_order = defined('CLICSHOPPING_APP_DESJARDINS_HO_SORT_ORDER') ? CLICSHOPPING_APP_DESJARDINS_HO_SORT_ORDER : 0;
      }
    }

    public function update_status() {
      $CLICSHOPPING_Order = Registry::get('Order');

      if ( ($this->enabled === true) && ((int)CLICSHOPPING_APP_DESJARDINS_HO_ZONE > 0)) {
        $check_flag = false;

        $Qcheck = $this->app->db->get('zones_to_geo_zones', 'zone_id', ['geo_zone_id' => CLICSHOPPING_APP_DESJARDINS_HO_ZONE,
                                                                        'zone_country_id' => $CLICSHOPPING_Order->delivery['country']['id']
                                                                        ],
                                                                        'zone_id'
                                      );

        while ($Qcheck->fetch()) {
          if (($Qcheck->valueInt('zone_id') < 1) || ($Qcheck->valueInt('zone_id') == $CLICSHOPPING_Order->billing['zone_id'])) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag === false) {
          $this->enabled = false;
        }
      }
    }

    public function javascript_validation() {
      return false;
    }

    public function selection() {
      $CLICSHOPPING_Template = Registry::get('Template');

      if (isset($_SESSION['cart_Desjardins_Standard_ID'])) {
        $this->order_id = substr($_SESSION['cart_Desjardins_Standard_ID'], strpos($_SESSION['cart_Desjardins_Standard_ID'], '-')+1);

        $Qcheck = $this->app->db->get('orders_status_history', 'orders_id', ['orders_id' => $this->order_id], null, 1);

        if ($Qcheck->fetch() === false) {
          $this->app->db->delete('orders', ['orders_id' => $this->order_id]);
          $this->app->db->delete('orders_total', ['orders_id' => $this->order_id]);
          $this->app->db->delete('orders_products', ['orders_id' => $this->order_id]);
          $this->app->db->delete('orders_products_attributes', ['orders_id' => $this->order_id]);
          $this->app->db->delete('orders_products_download', ['orders_id' => $this->order_id]);

          unset($_SESSION['cart_Desjardins_Standard_ID']);
        }
      }

      if (CLICSHOPPING_APP_DESJARDINS_HO_LOGO) {
        if (!empty(CLICSHOPPING_APP_DESJARDINS_HO_LOGO) && is_file($CLICSHOPPING_Template->getDirectoryTemplateImages() . 'logos/payment/' . CLICSHOPPING_APP_DESJARDINS_HO_LOGO)) {
          $this->title_selection .= HTML::image($CLICSHOPPING_Template->getDirectoryTemplateImages() . 'logos/payment/' . CLICSHOPPING_APP_DESJARDINS_HO_LOGO);
        } else {
          $this->title_selection .= $this->title;
        }
      }

      return ['id' => $this->app->vendor . '\\' . $this->app->code . '\\' . $this->code,
            'module' => $this->title_selection
            ];
    }

    public function pre_confirmation_check() {
      $CLICSHOPPING_ShoppingCart = Registry::get('ShoppingCart');
      $CLICSHOPPING_Order = Registry::get('Order');
      $CLICSHOPPING_Template = Registry::get('Template');

      if (empty($CLICSHOPPING_ShoppingCart->cartID)) {
        $_SESSION['cartID'] = $CLICSHOPPING_ShoppingCart->cartID = $CLICSHOPPING_ShoppingCart->generate_cart_id();
      }

      $CLICSHOPPING_Order->info['payment_method_raw'] = $CLICSHOPPING_Order->info['payment_method'];
      $CLICSHOPPING_Order->info['payment_method'] = HTML::image($CLICSHOPPING_Template->getDirectoryTemplateImages() . 'logos/payment/' . CLICSHOPPING_APP_DESJARDINS_HO_LOGO);
    }

    public function confirmation() {
      $CLICSHOPPING_Customer = Registry::get('Customer');
      $CLICSHOPPING_Prod = Registry::get('Prod');
      $CLICSHOPPING_Order = Registry::get('Order');
      $CLICSHOPPING_OrderTotal = Registry::get('OrderTotal');
      $CLICSHOPPING_PageManagerShop = Registry::get('PageManagerShop');
      $CLICSHOPPING_ProductsAttributes = Registry::get('ProductsAttributes');

      if (isset($_SESSION['cartID'])) {
        $insert_order = false;

        if (isset($_SESSION['cart_Desjardins_Standard_ID'])) {
          $this->order_id = substr($_SESSION['cart_Desjardins_Standard_ID'], strpos($_SESSION['cart_Desjardins_Standard_ID'], '-')+1);

          $Qorder = $this->app->db->get('orders', 'currency', ['orders_id' => (int)$this->order_id]);

          if ( ($Qorder->value('currency') != $CLICSHOPPING_Order->info['currency']) || ($_SESSION['cartID'] != substr($_SESSION['cart_Desjardins_Standard_ID'], 0, strlen($_SESSION['cartID'])))) {

            $Qcheck = $this->app->db->get('orders_status_history', 'orders_id', ['orders_id' => (int)$this->order_id],
                                                                   null,
                                                                   1
                                          );

            if ($Qcheck->fetch() === false) {
              $this->app->db->delete('orders', ['orders_id' => $this->order_id]);
              $this->app->db->delete('orders_total', ['orders_id' => $this->order_id]);
              $this->app->db->delete('orders_products', ['orders_id' => $this->order_id]);
              $this->app->db->delete('orders_products_attributes', ['orders_id' => $this->order_id]);
              $this->app->db->delete('orders_products_download', ['orders_id' => $this->order_id]);
            }

            $insert_order = true;
          }
        } else {
          $insert_order = true;
        }

        if ($insert_order === true) {
          $order_totals = [];

          if (is_array($CLICSHOPPING_OrderTotal->modules)) {
            $order_total = $CLICSHOPPING_OrderTotal->process();

            foreach ($order_total as $value) {
              if (!is_null($value['title']) && !is_null($value['title'])) {
                $order_totals[] = ['code' => $value['code'],
                                   'title' => $value['title'],
                                   'text' => $value['text'],
                                   'value' => $value['value'],
                                   'sort_order' => $value['sort_order']
                                  ];
              }
            }
          }

//gdpr
          $Qgdpr = $this->app->db->prepare('select no_ip_address
                                            from :table_customers_gdpr
                                            where customers_id = :customers_id
                                           ');
          $Qgdpr->bindInt(':customers_id', $CLICSHOPPING_Customer->getID());
          $Qgdpr->execute();

          if ($Qgdpr->valueInt('no_ip_address') == 1) {
            $client_computer_ip = '';
            $provider_name_client = '';
          } else {
           $client_computer_ip =  HTTP::getIPAddress();
           $provider_name_client = HTTP::getProviderNameCustomer();
          }

          if ( isset($CLICSHOPPING_Order->info['payment_method_raw'])) {
            $CLICSHOPPING_Order->info['payment_method'] = $CLICSHOPPING_Order->info['payment_method_raw'];
            unset($CLICSHOPPING_Order->info['payment_method_raw']);
          }

          $sql_data_array = ['customers_id' => (int)$CLICSHOPPING_Customer->getID(),
                              'customers_group_id' => (int)$CLICSHOPPING_Order->customer['group_id'],
                              'customers_name' => $CLICSHOPPING_Order->customer['firstname'] . ' ' . $CLICSHOPPING_Order->customer['lastname'],
                              'customers_company' => $CLICSHOPPING_Order->customer['company'],
                              'customers_street_address' => $CLICSHOPPING_Order->customer['street_address'],
                              'customers_suburb' => $CLICSHOPPING_Order->customer['suburb'],
                              'customers_city' => $CLICSHOPPING_Order->customer['city'],
                              'customers_postcode' => $CLICSHOPPING_Order->customer['postcode'],
                              'customers_state' => $CLICSHOPPING_Order->customer['state'],
                              'customers_country' => $CLICSHOPPING_Order->customer['country']['title'],
                              'customers_telephone' => $CLICSHOPPING_Order->customer['telephone'],
                              'customers_email_address' => $CLICSHOPPING_Order->customer['email_address'],
                              'customers_address_format_id' => (int)$CLICSHOPPING_Order->customer['format_id'],
                              'delivery_name' => trim($CLICSHOPPING_Order->delivery['firstname'] . ' ' . $CLICSHOPPING_Order->delivery['lastname']),
                              'delivery_company' => $CLICSHOPPING_Order->delivery['company'],
                              'delivery_street_address' => $CLICSHOPPING_Order->delivery['street_address'],
                              'delivery_suburb' => $CLICSHOPPING_Order->delivery['suburb'],
                              'delivery_city' => $CLICSHOPPING_Order->delivery['city'],
                              'delivery_postcode' => $CLICSHOPPING_Order->delivery['postcode'],
                              'delivery_state' => $CLICSHOPPING_Order->delivery['state'],
                              'delivery_country' => $CLICSHOPPING_Order->delivery['country']['title'],
                              'delivery_address_format_id' => (int)$CLICSHOPPING_Order->delivery['format_id'],
                              'billing_name' => $CLICSHOPPING_Order->billing['firstname'] . ' ' . $CLICSHOPPING_Order->billing['lastname'],
                              'billing_company' => $CLICSHOPPING_Order->billing['company'],
                              'billing_street_address' => $CLICSHOPPING_Order->billing['street_address'],
                              'billing_suburb' => $CLICSHOPPING_Order->billing['suburb'],
                              'billing_city' => $CLICSHOPPING_Order->billing['city'],
                              'billing_postcode' => $CLICSHOPPING_Order->billing['postcode'],
                              'billing_state' => $CLICSHOPPING_Order->billing['state'],
                              'billing_country' => $CLICSHOPPING_Order->billing['country']['title'],
                              'billing_address_format_id' => (int)$CLICSHOPPING_Order->billing['format_id'],
                              'payment_method' => $CLICSHOPPING_Order->info['payment_method'],
                              'cc_type' => $CLICSHOPPING_Order->info['cc_type'],
                              'cc_owner' => $CLICSHOPPING_Order->info['cc_owner'],
                              'cc_number' => $CLICSHOPPING_Order->info['cc_number'],
                              'cc_expires' => $CLICSHOPPING_Order->info['cc_expires'],
                              'date_purchased' => 'now()',
                              'orders_status' => (int)$this->order_status,
                              'orders_status_invoice' => (int)$CLICSHOPPING_Order->info['order_status_invoice'],
                              'currency' => $CLICSHOPPING_Order->info['currency'],
                              'currency_value' => $CLICSHOPPING_Order->info['currency_value'],
                              'client_computer_ip' => $client_computer_ip,
                              'provider_name_client' => $provider_name_client,
                              'customers_cellular_phone' => $CLICSHOPPING_Order->customer['cellular_phone']
                             ];

// recuperation des informations societes pour les clients B2B (voir fichier la classe OrderAdmin)
          if ($CLICSHOPPING_Customer->getCustomersGroupID() != 0) {
            $sql_data_array['customers_siret'] = $CLICSHOPPING_Order->customer['siret'];
            $sql_data_array['customers_ape'] = $CLICSHOPPING_Order->customer['ape'];
            $sql_data_array['customers_tva_intracom'] = $CLICSHOPPING_Order->customer['tva_intracom'];
          }

          $this->app->db->save('orders', $sql_data_array);

          $this->insertID = $this->app->db->lastInsertId();

          $page_manager_general_condition = $CLICSHOPPING_PageManagerShop->pageManagerGeneralCondition();

          $sql_data_array = ['orders_id' => (int)$this->insertID,
                             'customers_id' => (int)$CLICSHOPPING_Customer->getID(),
                             'page_manager_general_condition' => $page_manager_general_condition
                            ];

          $this->app->db->save('orders_pages_manager', $sql_data_array);

// orders total
          for ($i=0, $n=count($order_totals); $i<$n; $i++) {
            $sql_data_array = ['orders_id' => (int)$this->insertID,
                                'title' => $order_totals[$i]['title'],
                                'text' => $order_totals[$i]['text'],
                                'value' => (float)$order_totals[$i]['value'],
                                'class' => $order_totals[$i]['code'],
                                'sort_order' => (int)$order_totals[$i]['sort_order']
                              ];

            $this->app->db->save('orders_total', $sql_data_array);
          }

          for ($i=0, $n=count($CLICSHOPPING_Order->products); $i<$n; $i++) {

// search the good model
            if ($CLICSHOPPING_Customer->getCustomersGroupID() != 0) {
              $QproductsModuleCustomersGroup = $this->app->db->prepare('select products_model_group
                                                                         from :table_products_groups
                                                                         where products_id = :products_id
                                                                         and customers_group_id =  :customers_group_id
                                                                        ');
              $QproductsModuleCustomersGroup->bindInt(':products_id', $CLICSHOPPING_Prod::getProductID ($this->products[$i]['id']));
              $QproductsModuleCustomersGroup->bindInt(':customers_group_id', $CLICSHOPPING_Customer->getCustomersGroupID());
              $QproductsModuleCustomersGroup->execute();

              $products_model = $QproductsModuleCustomersGroup->value('products_model_group');

              if (empty($products_model)) $products_model = $this->products[$i]['model'];

            } else {
              $products_model = $this->products[$i]['model'];
            }

// save data
            $sql_data_array = ['orders_id' => (int)$this->insertID,
                              'products_id' => (int)$CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id']),
                              'products_model' => $products_model,
                              'products_name' => $CLICSHOPPING_Order->products[$i]['name'],
                              'products_price' => (float)$CLICSHOPPING_Order->products[$i]['price'],
                              'final_price' => (float)$CLICSHOPPING_Order->products[$i]['final_price'],
                              'products_tax' => (float)$CLICSHOPPING_Order->products[$i]['tax'],
                              'products_quantity' => (int)$CLICSHOPPING_Order->products[$i]['qty']
                              ];

            $this->app->db->save('orders_products', $sql_data_array);

            $order_products_id = $this->app->db->lastInsertId();

            if (isset($CLICSHOPPING_Order->products[$i]['attributes'])) {
              for ($j=0, $n2=count($CLICSHOPPING_Order->products[$i]['attributes']); $j<$n2; $j++) {

                $Qattributes = $CLICSHOPPING_ProductsAttributes->getAttributesDownloaded($CLICSHOPPING_Order->products[$i]['id'], $CLICSHOPPING_Order->products[$i]['attributes'][$j]['option_id'], $CLICSHOPPING_Order->products[$i]['attributes'][$j]['value_id'], $this->app->lang->getId());

                $sql_data_array = ['orders_id' => (int)$this->insertID,
                                  'orders_products_id' => (int)$order_products_id,
                                  'products_options' => $Qattributes->value('products_options_name'),
                                  'products_options_values' => $Qattributes->value('products_options_values_name'),
                                  'options_values_price' => (float)$Qattributes->value('options_values_price'),
                                  'price_prefix' => $Qattributes->value('price_prefix'),
                                  'products_attributes_reference' => $Qattributes->value('products_attributes_reference')
                                  ];

                $this->app->db->save('orders_products_attributes', $sql_data_array);

                if ((DOWNLOAD_ENABLED == 'true') && $Qattributes->hasValue('products_attributes_filename') && !empty($Qattributes->value('products_attributes_filename'))) {
                  $sql_data_array = ['orders_id' => (int)$this->insertID,
                                    'orders_products_id' => (int)$order_products_id,
                                    'orders_products_filename' => $Qattributes->value('products_attributes_filename'),
                                    'download_maxdays' => (int)$Qattributes->value('products_attributes_maxdays'),
                                    'download_count' => (int)$Qattributes->value('products_attributes_maxcount')
                                    ];

                  $this->app->db->save('orders_products_download', $sql_data_array);
                }
              }
            }
          }

          $_SESSION['cart_Desjardins_Standard_ID'] = $_SESSION['cartID'] . '-' . $this->insertID;
        }
      }

      return false;
    }


/***********************************************************
* process_button
***********************************************************/
    public function process_button() {
      $CLICSHOPPING_Customer = Registry::get('Customer');
      $CLICSHOPPING_Currencies = Registry::get('Currencies');
      $CLICSHOPPING_Order = Registry::get('Order');
      $CLICSHOPPING_OrderTotal = Registry::get('OrderTotal');
      $CLICSHOPPING_Db = Registry::get('Db');

// remove shipping tax in total tax value
/*
      if ( isset($_SESSION['shipping']['cost'])) {
        $total_tax -= ($CLICSHOPPING_Order->info['shipping_cost'] - $_SESSION['shipping']['cost']);
      }
*/
      $_SESSION['session_id'] = session_id();

//generation de la reference dans la table desjardins_reference pour assurer l'unicite

      $sql_data_array = ['order_id'=> $this->insertID,
                         'text_libre' => session_id(),
                         'customers_id' => $CLICSHOPPING_Customer->getID()
                        ];

      $this->app->db->save('desjardins_reference', $sql_data_array);

      $new_id = $this->app->db->lastInsertId();

      $reference = str_pad($new_id, 12, "0", STR_PAD_LEFT);

      $sql_data_array = ['ref_number' => $reference];

      $this->app->db->save('desjardins_reference', $sql_data_array, ['ref_id' => $new_id]);

      $reference = str_pad($new_id, 12, "0", STR_PAD_LEFT);

      $sOptions = '';

// Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max
      $sReference = $reference;

// Amount : format  "xxxxx.yy" (no spaces)
// $sMontant = 1.01;
      $sMontant = number_format($CLICSHOPPING_Order->info['total'] * $CLICSHOPPING_Currencies->getValue($CLICSHOPPING_Order->info['currency']), $CLICSHOPPING_Currencies->currencies[$CLICSHOPPING_Order->info['currency']]['decimal_places'], '.', '');

// Currency : ISO 4217 compliant
// $sDevise  = "EUR";
      $sDevise  = $CLICSHOPPING_Order->info['currency'];

// free texte : a bigger reference, session context for the return on the merchant website
      $sTexteLibre = session_id();

// transaction date : format d/m/y:h:m:s
      $sDate = date('d/m/Y:H:i:s');

// Language of the company code
      $sLangue = 'FR';

// customer email
      $QcustomerEmail = $this->app->db->prepare('select customers_email_address
                                                 from :table_customers
                                                 where customers_id = :customers_id
                                                ');
      $QcustomerEmail->bindInt(':customers_id', $CLICSHOPPING_Customer->getID());
      $QcustomerEmail->execute();

      $sEmail = $QcustomerEmail->value('customers_email_address');

// options
    $sOptions = '';

// between 2 and 4
//$sNbrEch = "4";
    $sNbrEch = '';

// date echeance 1 - format dd/mm/yyyy
//$sDateEcheance1 = date("d/m/Y");
    $sDateEcheance1 = '';

// montant echeance 1 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance1 = "0.26" . $sDevise;
    $sMontantEcheance1 = '';

// date echeance 2 - format dd/mm/yyyy
//$sDateEcheance2 = date("d/m/Y", mktime(0, 0, 0, date("m") +1 , date("d"), date("Y")));
    $sDateEcheance2 = '';

// montant echeance 2 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance2 = "0.25" . $sDevise;
    $sMontantEcheance2 = '';

// date echeance 3 - format dd/mm/yyyy
//$sDateEcheance3 = date("d/m/Y", mktime(0, 0, 0, date("m") +2 , date("d"), date("Y")));
    $sDateEcheance3 = '';

// montant echeance 3 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance3 = "0.25" . $sDevise;
    $sMontantEcheance3 = '';

// date echeance 4 - format dd/mm/yyyy
//$sDateEcheance4 = date("d/m/Y", mktime(0, 0, 0, date("m") +3 , date("d"), date("Y")));
    $sDateEcheance4 = '';

// montant echeance 4 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance4 = "0.25" . $sDevise;
    $sMontantEcheance4 = '';

    Registry::set('TPE', new TPE($sLangue));
    $oTpe = Registry::get('TPE');

    Registry::set('HMAC', new HMAC($oTpe));
    $oHmac = Registry::get('HMAC');


// Control String for support
    $CtlHmac = sprintf(MONETICOPAIEMENT_CTLHMAC, $oTpe->sVersion, $oTpe->sNumero, $oHmac->computeHmac(sprintf(MONETICOPAIEMENT_CTLHMACSTR, $oTpe->sVersion, $oTpe->sNumero)));

  // Data to certify
    $phase1go_fields = sprintf(MONETICOPAIEMENT_PHASE1GO_FIELDS, $oTpe->sNumero,
                                                                $sDate,
                                                                $sMontant,
                                                                $sDevise,
                                                                $sReference,
                                                                $sTexteLibre,
                                                                $oTpe->sVersion,
                                                                $oTpe->sLangue,
                                                                $oTpe->sCodeSociete,
                                                                $sEmail,
                                                                $sNbrEch,
                                                                $sDateEcheance1,
                                                                $sMontantEcheance1,
                                                                $sDateEcheance2,
                                                                $sMontantEcheance2,
                                                                $sDateEcheance3,
                                                                $sMontantEcheance3,
                                                                $sDateEcheance4,
                                                                $sMontantEcheance4,
                                                                $sOptions
                                );


// MAC computation
    $sMAC = $oHmac->computeHmac($phase1go_fields);

    $process_button_string = HTML::hiddenField('MAC', static::HtmlEncodeDesjardins($sMAC)) . "\n" .
                              HTML::hiddenField('version', static::HtmlEncodeDesjardins($oTpe->sVersion)) . "\n" .
                              HTML::hiddenField('TPE', static::HtmlEncodeDesjardins($oTpe->sNumero)) . "\n" .
                              HTML::hiddenField('date', static::HtmlEncodeDesjardins($sDate)) . "\n" .
                              HTML::hiddenField('montant', static::HtmlEncodeDesjardins($sMontant . $sDevise)) . "\n" .
                              HTML::hiddenField('reference', static::HtmlEncodeDesjardins($sReference)) . "\n" .
                              HTML::hiddenField('lgue', static::HtmlEncodeDesjardins($oTpe->sLangue)) . "\n" .
                              HTML::hiddenField('societe', static::HtmlEncodeDesjardins($oTpe->sCodeSociete)) . "\n" .
                              HTML::hiddenField('url_retour', static::htmlEncodeDesjardins(CLICSHOPPING::link(null, 'Checkout&Billing')) ) . "\n" .
                              HTML::hiddenField('url_retour_ok', static::htmlEncodeDesjardins(CLICSHOPPING::link(null,'order&desjardins&success&ho', false, false)) ) . "\n" .
//      HTML::hiddenField('url_retour_ok', CLICSHOPPING::link(null,'Checkout&Success', false, false)) . "\n" .
                              HTML::hiddenField('url_retour_err', static::htmlEncodeDesjardins(CLICSHOPPING::link(null, 'Checkout&Billing')) ) . "\n" .
                              HTML::hiddenField('texte-libre', static::HtmlEncodeDesjardins($sTexteLibre)) . "\n" .
                              HTML::hiddenField('mail', $sEmail) . "\n" .
                              HTML::hiddenField('bouton', static::HtmlEncodeDesjardins(CLICSHOPPING_APP_DESJARDINS_HO_BUTTON)) . "\n" ;

    if (CLICSHOPPING_APP_DESJARDINS_HO_SERVER == 'Test' && CLICSHOPPING_APP_DESJARDINS_HO_SIMULATION == 'True') {
?>
<b>TEST MODE - DATA BANK FORM:</b>

<pre>
&lt;form <span class="name">action</span>="<span class="value"><?php echo $this->form_action_url ;?>"</span> method="post" id="PaymentRequest"&gt;
&lt;input type="hidden" name="<span class="name">MAC</span>"              value="<span class="value"><?php echo static::HtmlEncodeDesjardins($sMAC); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Version</span>"          value="<span class="value"><?php echo static::HtmlEncodeDesjardins($oTpe->sVersion); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">TPE</span>"              value="<span class="value"><?php echo static::HtmlEncodeDesjardins($oTpe->sNumero); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Date</span>"             value="<span class="value"><?php echo static::HtmlEncodeDesjardins($sDate); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Amount</span>"           value="<span class="value"><?php echo static::HtmlEncodeDesjardins($sMontant . $sDevise); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Reference</span>"        value="<span class="value"><?php echo static::HtmlEncodeDesjardins($sReference); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Language</span>"         value="<span class="value"><?php echo static::HtmlEncodeDesjardins($oTpe->sLangue); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Company</span>"          value="<span class="value"><?php echo static::HtmlEncodeDesjardins($oTpe->sCodeSociete); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Url_retour</span>"       value="<span class="value"><?php echo static::HtmlEncodeDesjardins(CLICSHOPPING::link(null, 'Checkout&Billing')); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Url_retour_ok</span>"    value="<span class="value"><?php echo static::HtmlEncodeDesjardins(CLICSHOPPING::link(null, 'order&desjardins&success&ho', false, false)); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Url_retour_err</span>"   value="<span class="value"><?php echo static::HtmlEncodeDesjardins(CLICSHOPPING::link(null, 'Checkout&Billing')); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">texte-libre - ClicShopping Session</span>"       value="<span class="value"><?php echo static::HtmlEncodeDesjardins($sTexteLibre); ?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">Mail</span>"             value="<span class="value"><?php echo $sEmail;?></span>" /&gt;
&lt;!-- Uniquement pour le Paiement fractionné / Only for installment payment --&gt;
&lt;input type="hidden" name="<span class="name">nbrech</span>"           value="<span class="value"><?php echo $sNbrEch;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech1</span>"         value="<span class="value"><?php echo $sDateEcheance1;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech1</span>"      value="<span class="value"><?php echo $sMontantEcheance1;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech2</span>"         value="<span class="value"><?php echo $sDateEcheance2;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech2</span>"      value="<span class="value"><?php echo $sMontantEcheance2;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech3</span>"         value="<span class="value"><?php echo $sDateEcheance3;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech3</span>"      value="<span class="value"><?php echo $sMontantEcheance3;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech4</span>"         value="<span class="value"><?php echo $sDateEcheance4;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech4</span>"      value="<span class="value"><?php echo $sMontantEcheance4;?></span>" /&gt;
&lt;!-- --&gt;
&lt;input type="submit" name="<span class="name">Button</span>"           value="<span class="value"><?php echo static::HtmlEncodeDesjardins(CLICSHOPPING_APP_DESJARDINS_HO_BUTTON);?></span>" /&gt;
&lt;/form&gt;
</pre>

<?php
    }

    return $process_button_string;
  }


/***********************************************************
 before_process
***********************************************************/

    public function before_process() {
      $CLICSHOPPING_Customer = Registry::get('Customer');
      $CLICSHOPPING_Db = Registry::get('Db');
      $CLICSHOPPING_Currencies = Registry::get('Currencies');
      $CLICSHOPPING_Mail = Registry::get('Mail');
      $CLICSHOPPING_Prod = Registry::get('Prod');
      $CLICSHOPPING_Order = Registry::get('Order');
      $CLICSHOPPING_Hooks = Registry::get('Hooks');
      $CLICSHOPPING_ProductsAttributes = Registry::get('ProductsAttributes');
      $CLICSHOPPING_Template = Registry::get('Template');

      $new_order_status = DEFAULT_ORDERS_STATUS_ID;

      if ( CLICSHOPPING_APP_DESJARDINS_HO_ORDER_STATUS_ID > 0) {
        $new_order_status = CLICSHOPPING_APP_DESJARDINS_HO_ORDER_STATUS_ID;
      }

      $this->app->db->save('orders', ['orders_status' => (int)$new_order_status,
                              'last_modified' => 'now()',
                              ],
                              ['orders_id' => (int)$this->order_id]
                       );

      $sql_data_array = ['orders_id' => (int)$this->order_id,
                        'orders_status_id' => (int)$new_order_status,
                        'orders_status_invoice_id' => (int)$CLICSHOPPING_Order->info['order_status_invoice'],
                        'admin_user_name' => '',
                        'date_added' => 'now()',
                        'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                        'comments' => $CLICSHOPPING_Order->info['comments']
                        ];

      $this->app->db->save('orders_status_history', $sql_data_array);


//kgt - discount coupons
      if( isset($_SESSION['coupon']) && $CLICSHOPPING_Order->info['coupon'] != '' ) {
        $sql_data_array = ['coupons_id' => $CLICSHOPPING_Order->info['coupon'],
                           'orders_id' => (int)$this->order_id
                          ];
        $CLICSHOPPING_Db->save('discount_coupons_to_orders', $sql_data_array);
      }

// initialized for the email confirmation
      $products_ordered = '';

      for ($i=0, $n=count($CLICSHOPPING_Order->products); $i<$n; $i++) {
        if (STOCK_LIMITED == 'true') {
          if (DOWNLOAD_ENABLED == 'true') {
            $stock_query_sql = 'select p.products_quantity,
                                       pad.products_attributes_filename
                                from :table_products p
                                left join :table_products_attributes pa  on p.products_id = pa.products_id
                                left join :table_products_attributes_download pad on pa.products_attributes_id = pad.products_attributes_id
                                where p.products_id = :products_id';

// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
            $products_attributes = (isset($CLICSHOPPING_Order->products[$i]['attributes'])) ? $CLICSHOPPING_Order->products[$i]['attributes'] : '';

            if (is_array($products_attributes)) {
              $stock_query_sql .= ' and pa.options_id = :options_id
                                   and pa.options_values_id = :options_values_id
                                ';
            }

            $Qstock = $this->app->db->prepare($stock_query_sql);
            $Qstock->bindInt(':products_id', $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id']));

            if (is_array($products_attributes)) {
              $Qstock->bindInt(':options_id', $products_attributes[0]['option_id']);
              $Qstock->bindInt(':options_values_id', $products_attributes[0]['value_id']);
            }

            $Qstock->execute();
          } else {
            $Qstock = $this->app->db->prepare('select products_quantity,
                                                      products_quantity_alert
                                                from :table_products
                                                where products_id = :products_id
                                              ');

            $Qstock->bindInt(':products_id',  $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id']));
            $Qstock->execute();
          }

          if ($Qstock->fetch() !== false) {
// do not decrement quantities if products_attributes_filename exists
            if ((DOWNLOAD_ENABLED != 'true') || !is_null($Qstock->value('products_attributes_filename'))) {
// select the good qty in B2B ti decrease the stock. See shopping_cart top display out stock or not
              if ($CLICSHOPPING_Customer->getCustomersGroupID() != '0') {

                $QproductsQuantityCustomersGroup = $this->app->db->prepare('select products_quantity_fixed_group
                                                                            from :table_products_groups
                                                                            where products_id = :products_id
                                                                            and customers_group_id =  :customers_group_id
                                                                           ');
                $QproductsQuantityCustomersGroup->bindInt(':products_id',  $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id']));
                $QproductsQuantityCustomersGroup->bindInt(':customers_group_id', (int)$CLICSHOPPING_Customer->getCustomersGroupID());
                $QproductsQuantityCustomersGroup->execute();

                $products_quantity_customers_group = $QproductsQuantityCustomersGroup->fetch();

// do the exact qty in public function the customer group and product
                $products_quantity_customers_group[$i] = $products_quantity_customers_group['products_quantity_fixed_group'];
              } else {
                $products_quantity_customers_group[$i] = 1;
              }
              $stock_left = $Qstock->valueInt('products_quantity') - ($CLICSHOPPING_Order->products[$i]['qty'] * $products_quantity_customers_group[$i]);
            } else {
              $stock_left = $Qstock->valueInt('products_quantity');
              $stock_products_quantity_alert = $Qstock->valueInt('products_quantity_alert');
            }

// alert an email if the product stock is < stock reorder level
// Alert by mail if a product is 0 or < 0

            if (STOCK_ALERT_PRODUCT_REORDER_LEVEL == 'true') {
              if ((STOCK_ALLOW_CHECKOUT == 'false') && (STOCK_CHECK == 'true')) {
                $warning_stock = STOCK_REORDER_LEVEL;
                $current_stock = $stock_left;

// alert email if stock product alert < warning stock
                if (($stock_products_quantity_alert <= $warning_stock) && ($stock_products_quantity_alert != '0')) {
                  $email_text_subject_stock = stripslashes(CLICSHOPPING::getDef('module_payment_paypal_standard_text_subject_alert_stock'));
                  $email_text_subject_stock = html_entity_decode($email_text_subject_stock);

                  $reorder_stock_email = stripslashes(CLICSHOPPING::getDef('module_payment_paypal_standard_text_reorder_level_text_alert_stock'));
                  $reorder_stock_email = html_entity_decode($reorder_stock_email);
                  $reorder_stock_email .= "\n"  . CLICSHOPPING::getDef('module_payment_paypal_standard_text_date_alert') . ' ' . strftime(CLICSHOPPING::getDef('date_format_long')) .  "\n" .   CLICSHOPPING::getDef('email_text_model') . ' ' . $CLICSHOPPING_Order->products[$i]['model']  .  "\n" . CLICSHOPPING::getDef('email_text_products_name') . $CLICSHOPPING_Order->products[$i]['name']  .  "\n" . CLICSHOPPING::getDef('email_text_products_id') . ' ' .  $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id'])  .  "\n" . '<strong>' . CLICSHOPPING::getDef('email_text_products_url') . '</strong>' . HTTP::getShopUrlDomain() . 'index.php?Products&Description&products_id=' . $CLICSHOPPING_Order->products[$i]['id'] . "\n" . '<strong>' . CLICSHOPPING::getDef('email_text_products_alert_stock') . ' ' . $stock_products_quantity_alert  .'</strong>';

                   $CLICSHOPPING_Mail->clicMail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_text_subject_stock, $reorder_stock_email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
               }

                if ($current_stock <= $warning_stock) {
                  $email_text_subject_stock = stripslashes(CLICSHOPPING::getDef('email_text_subject_stock', ['store_name' => STORE_NAME]));
                  $email_text_subject_stock = html_entity_decode($email_text_subject_stock);

                  $reorder_stock_email = stripslashes(CLICSHOPPING::getDef('module_payment_paypal_standard_text_reorder_level_text_alert_stock'));
                  $reorder_stock_email = html_entity_decode($reorder_stock_email);
                  $reorder_stock_email .= "\n"  .  CLICSHOPPING::getDef('module_payment_paypal_standard_text_date_alert') . ' ' . strftime(CLICSHOPPING::getDef('date_format_long')) .  "\n" .    CLICSHOPPING::getDef('email_text_model') . ' ' . $CLICSHOPPING_Order->products[$i]['model']  .  "\n" . CLICSHOPPING::getDef('email_text_products_name') . $CLICSHOPPING_Order->products[$i]['name']  .  "\n" . CLICSHOPPING::getDef('email_text_products_id') . ' ' .  $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id'])  .  "\n" . '<strong>' .  CLICSHOPPING::getDef('email_text_products_url') . '</strong>' . HTTP::getShopUrlDomain() . 'index.php?Products&Description&products_id=' . $CLICSHOPPING_Order->products[$i]['id'] . "\n" . '<strong>' . CLICSHOPPING::getDef('email_text_products_alert_stock') . ' ' . $current_stock  .'</strong>';

                   $CLICSHOPPING_Mail->clicMail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_text_subject_stock, $reorder_stock_email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                }
              }
            }

            $this->app->db->save('products', ['products_quantity' => (int)$stock_left],
                                             ['products_id' =>  (int)$CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id'])]
                                );

            if ($stock_left != $Qstock->valueInt('products_quantity')) {
              $this->app->db->save('products', ['products_quantity' => $stock_left], ['products_id' => $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id'])]);
            }

            if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
             $this->app->db->save('products', ['products_status' => '0'],
                                              ['products_id' => (int)$CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id'])]
                                  );
            }

// Alert by mail product exhausted if a product is 0 or < 0
            if (STOCK_ALERT_PRODUCT_SOLD_OUT == 'true') {
              if (($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') && (STOCK_CHECK == 'true')) {
                $email_text_subject_stock = stripslashes(CLICSHOPPING::getDef('email_text_subject_stock', ['store_name' => STORE_NAME]));
                $email_text_subject_stock = html_entity_decode($email_text_subject_stock);
                $email_product_exhausted_stock = stripslashes(CLICSHOPPING::getDef('email_text_stock_exuasted'));
                $email_product_exhausted_stock = html_entity_decode($email_product_exhausted_stock);
                $email_product_exhausted_stock .=  "\n"  . CLICSHOPPING::getDef('module_payment_paypal_standard_text_date_alert') . ' ' . strftime(CLICSHOPPING::getDef('date_format_long')) .  "\n" .  CLICSHOPPING::getDef('email_text_model') . ' ' . $CLICSHOPPING_Order->products[$i]['model']  .  "\n" . CLICSHOPPING::getDef('email_text_products_name') . $CLICSHOPPING_Order->products[$i]['name']  .  "\n" . CLICSHOPPING::getDef('email_text_products_id') . ' ' . $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id']) .  "\n";

                $CLICSHOPPING_Mail->clicMail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_text_subject_stock, $email_product_exhausted_stock, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
              }
            } // end stock alert
          }
        }

// Update products_ordered (for bestsellers list)
        $Qupdate = $CLICSHOPPING_Db->prepare('update :table_products
                                              set products_ordered = products_ordered + :products_ordered
                                              where products_id = :products_id
                                            ');
        $Qupdate->bindInt(':products_ordered', $CLICSHOPPING_Order->products[$i]['qty']);
        $Qupdate->bindInt(':products_id', $CLICSHOPPING_Prod::getProductID($CLICSHOPPING_Order->products[$i]['id']));
        $Qupdate->execute();

        $products_ordered_attributes = '';

        if (isset($CLICSHOPPING_Order->products[$i]['attributes'])) {
          for ($j=0, $n2=count($CLICSHOPPING_Order->products[$i]['attributes']); $j<$n2; $j++) {

            $Qattributes = $CLICSHOPPING_ProductsAttributes->getAttributesDownloaded($CLICSHOPPING_Order->products[$i]['id'], $CLICSHOPPING_Order->products[$i]['attributes'][$j]['option_id'], $CLICSHOPPING_Order->products[$i]['attributes'][$j]['value_id'], $this->app->lang->getId());

            $products_ordered_attributes .= "\n\t" . $Qattributes->value('products_options_name') . ' ' . $Qattributes->value('products_options_values_name');
          }
        }

//------insert customer choosen option eof ----
        $products_ordered .= $CLICSHOPPING_Order->products[$i]['qty'] . ' x ' . $CLICSHOPPING_Order->products[$i]['name'] . ' (' . $CLICSHOPPING_Order->products[$i]['model'] . ') = ' . $CLICSHOPPING_Currencies->displayPrice($CLICSHOPPING_Order->products[$i]['final_price'], $CLICSHOPPING_Order->products[$i]['tax'], $CLICSHOPPING_Order->products[$i]['qty']) . $products_ordered_attributes . "\n";
      }

      $source_folder = CLICSHOPPING::getConfig('dir_root', 'Shop') . 'includes/Module/Hooks/Shop/CheckoutProcess/';

      $files_get = $CLICSHOPPING_Template->getSpecificFiles($source_folder, 'CheckoutProcess*');

      foreach ($files_get as $value) {
        if (!empty($value['name'])) {
          $CLICSHOPPING_Hooks->call('CheckoutProcess', $value['name']);
        }
      }

// load the after_process public function from the payment modules
      $this->after_process();
    }

    public function after_process() {
      $CLICSHOPPING_ShoppingCart = Registry::get('ShoppingCart');

      $CLICSHOPPING_ShoppingCart->reset(true);

// unregister session variables used during checkout
      unset($_SESSION['sendto']);
      unset($_SESSION['billto']);
      unset($_SESSION['shipping']);
      unset($_SESSION['payment']);
      unset($_SESSION['comments']);
      unset($_SESSION['coupon']);
      unset($_SESSION['order']);
      unset($_SESSION['order_total']);
      unset($_SESSION['ClicShoppingCart']);
      unset($_SESSION['cart_Desjardins_Standard_ID']);
    }

    public function get_error() {
      return false;
    }

    public function check() {
      return defined('CLICSHOPPING_APP_DESJARDINS_HO_STATUS') && (trim(CLICSHOPPING_APP_DESJARDINS_HO_STATUS) != '');
    }

    public function install() {
      $this->app->redirect('Configure&Install&module=HO');
    }

    public function remove() {
      $this->app->redirect('Configure&Uninstall&module=HO');
    }

    public function keys() {
      return array('CLICSHOPPING_APP_DESJARDINS_HO_SORT_ORDER');
    }

// Description: Encode special characters under HTML format
     public static function HtmlEncodeDesjardins($data) {
      $SAFE_OUT_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890._-";
      $encoded_data = '';
      $result = '';
      for ($i=0; $i<strlen($data); $i++) {
        if (strchr($SAFE_OUT_CHARS, $data{$i})) {
          $result .= $data{$i};
        }  else if (($var = bin2hex(substr($data,$i,1))) <= "7F") {
          $result .= "&#x" . $var . ";";
        } else {
          $result .= $data{$i};
        }
      }
      return $result;
    }
  }
