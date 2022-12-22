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

  namespace ClicShopping\Apps\Payment\Desjardins\Sites\Shop\Pages\HO;

  header("Pragma: no-cache");
  header("Content-type: text/plain");

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;

  use ClicShopping\Apps\Payment\Desjardins\API\TPE;
  use ClicShopping\Apps\Payment\Desjardins\API\HMAC;

  use ClicShopping\Apps\Payment\Desjardins\Module\Payment\HO as PaymentModuleHO;

  use ClicShopping\Apps\Configuration\TemplateEmail\Classes\Shop\TemplateEmail;

  define ("MONETICOPAIEMENT_CTLHMAC","V4.0.sha1.php--[CtlHmac%s%s]-%s");
  define ("MONETICOPAIEMENT_CTLHMACSTR", "CtlHmac%s%s");
  define ("MONETICOPAIEMENT_PHASE2BACK_RECEIPT","version=2\ncdr=%s");
  define ("MONETICOPAIEMENT_PHASE2BACK_MACOK","0");
  define ("MONETICOPAIEMENT_PHASE2BACK_MACNOTOK","1\n");
  define ("MONETICOPAIEMENT_PHASE2BACK_FIELDS", "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*");
  define ("MONETICOPAIEMENT_PHASE1GO_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s");

  class HO extends \ClicShopping\OM\PagesAbstract {
    protected $file = null;
    protected $use_site_template = false;
    protected $pm;
    protected $lang;

    protected function init()  {
      $CLICSHOPPING_Currencies = Registry::get('Currencies');
      $CLICSHOPPING_Hooks = Registry::get('Hooks');
      $CLICSHOPPING_Customer = Registry::get('Customer');
      $CLICSHOPPING_ShoppingCart = Registry::get('ShoppingCart');
      $CLICSHOPPING_Mail = Registry::get('Mail');
      $CLICSHOPPING_Session = Registry::get('Session');
      $CLICSHOPPING_Currencies = Registry::get('Currencies');
      $CLICSHOPPING_Prod = Registry::get('Prod');
      $CLICSHOPPING_Order = Registry::get('Order');

      $this->lang = Registry::get('Language');

      $this->pm = new PaymentModuleHO();

      $this->lang->loadDefinitions('Shop/checkout_process');

      if (!$this->pm->check() || !$this->pm->enabled) {
        CLICSHOPPING::redirect(null, 'Cart');
      }

     if (!defined('CLICSHOPPING_APP_DESJARDINS_HO_STATUS')) {
        return false;
      }

      $this->lang->loadDefinitions('Shop/checkout_process');

// Begin Main : Retrieve Variables posted by Monetico Paiement Server
      $MoneticoPaiement_bruteVars = static::getMethode();

      Registry::set('TPE', new TPE());
      $oTpe = Registry::get('TPE');

      Registry::set('HMAC', new HMAC($oTpe));
      $oHmac = Registry::get('HMAC');

      $QreferenceDesjardins = $this->pm->app->db->prepare('select text_libre,
                                                                  order_id,
                                                                  customers_id
                                                           from :table_desjardins_reference
                                                           where ref_number = :ref_number
                                                          ');
      $QreferenceDesjardins->bindValue('ref_number', $MoneticoPaiement_bruteVars['reference']);
      $QreferenceDesjardins->execute();

      $order_id = $QreferenceDesjardins->valueInt('order_id');
      $customer_id = $QreferenceDesjardins->valueInt('customers_id');
      $session = $QreferenceDesjardins->value('text_libre');

      if ($CLICSHOPPING_Session->exists($session)) {
        $serialstring = $CLICSHOPPING_Session->read($session);
      }

      $result = $oTpe->getUnserializeData($serialstring);

      if (CLICSHOPPING_APP_DESJARDINS_HO_DEBUG_RECEIPT == 'True') {
        var_dump(' <br /> --- Monecio Information ---- <br />');
        var_dump($MoneticoPaiement_bruteVars);
        var_dump(' <br /> --- Session information ---- <br />');
        var_dump($result);
      }

     if (!is_null($MoneticoPaiement_bruteVars['reference'])) {
        $QorderStatus = $this->pm->app->db->get('orders', 'orders_status', ['orders_id' => (int)$order_id,
                                                                          'customers_id' => (int)$customer_id
                                                                         ]
                                               );

        $Qorders= $this->pm->app->db->prepare('select customers_name,
                                                      customers_email_address,
                                                      delivery_street_address,
                                                      delivery_suburb,
                                                      delivery_city,
                                                      delivery_state,
                                                      delivery_country,
                                                      delivery_address_format_id,
                                                      billing_street_address,
                                                      billing_suburb,
                                                      billing_city,
                                                      billing_state,
                                                      billing_country,
                                                      billing_address_format_id,
                                                      payment_method,
                                                      currency,
                                                      currency_value
                                              from :table_orders
                                              where orders_id = :orders_id
                                             ');
        $Qorders->bindInt('orders_id', $order_id);
        $Qorders->execute();

        $new_order_status = DEFAULT_ORDERS_STATUS_ID;

        if (CLICSHOPPING_APP_DESJARDINS_HO_ORDER_STATUS_ID > 0) {
          $new_order_status = CLICSHOPPING_APP_DESJARDINS_HO_ORDER_STATUS_ID;
        }

        if ($QorderStatus->fetch()) {
          if ($QorderStatus->valueInt('orders_status') == CLICSHOPPING_APP_DESJARDINS_HO_PREPARE_ORDER_STATUS_ID) {
            $this->pm->app->db->save('orders', ['orders_status' => (int)$new_order_status,
                                                'last_modified' => 'now()'
                                               ], [
                                                'orders_id' => (int)$order_id
                                               ]
                                    );

            $sql_data_array = ['orders_id' => (int)$order_id,
                              'orders_status_id' => (int)$new_order_status,
                              'orders_status_invoice_id' => (int)$QorderStatus->valueInt('order_status_invoice'),
                              'admin_user_name' => '',
                              'date_added' => 'now()',
                              'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                              'comments' => ''
                             ];

            $this->pm->app->db->save('orders_status_history', $sql_data_array);
          }

          $CLICSHOPPING_Customer = Registry::get('Customer');
          $CLICSHOPPING_Prod = Registry::get('Prod');
          $CLICSHOPPING_Hooks = Registry::get('Hooks');

          $CLICSHOPPING_Hooks->call('Orders', 'PreActionProcess');

          $Qproducts = $this->pm->app->db->prepare('select products_id,
                                                           products_quantity
                                                    from :table_orders_products
                                                    where orders_id = :orders_id
                                                   ');
          $Qproducts->bindInt(':orders_id', $order_id);
          $Qproducts->execute();

        while ( $Qproducts->fetch()) {
// Stock Update
          if (STOCK_LIMITED == 'true') {
            if (DOWNLOAD_ENABLED == 'true') {

              $stock_query_sql = 'select p.products_quantity,
                                         pad.products_attributes_filename
                                  from :table_products p
                                  left join :table_products_attributes pa  on p.products_id = pa.products_id
                                  left join :table_products_attributes_download pad on pa.products_attributes_id = pad.products_attributes_id
                                  where p.products_id = :products_id
                                  ';

              $products_attributes = (isset($this->products['attributes'])) ? $this->products['attributes'] : '';

              if (is_array($products_attributes)) {
                $stock_query_sql .= ' and pa.options_id = :options_id
                                      and pa.options_values_id = :options_values_id
                                    ';
              }

              $Qstock = $this->pm->app->db->prepare($stock_query_sql);

              $Qstock->bindInt(':products_id', $CLICSHOPPING_Prod::getProductID($Qproducts->valueInt('products_id')));

              if (is_array($products_attributes)) {
                $Qstock->bindInt(':options_id', $products_attributes['option_id']);
                $Qstock->bindInt(':options_values_id', $products_attributes['value_id']);
              }

              $Qstock->execute();
            } else {
              $Qstock = $this->pm->app->db->prepare('select products_quantity,
                                                            products_quantity_alert
                                                    from :table_products
                                                    where products_id = :products_id
                                                    ');

              $Qstock->bindInt(':products_id', $CLICSHOPPING_Prod::getProductID($Qproducts->valueInt('products_id')));
              $Qstock->execute();
            }

            if ($Qstock->fetch() !== false) {
// do not decrement quantities if products_attributes_filename exists
              if ((DOWNLOAD_ENABLED != 'true') || !is_null($Qstock->value('products_attributes_filename'))) {
// select the good qty in B2B ti decrease the stock. See shopping_cart top display out stock or not
                if ($CLICSHOPPING_Customer->getCustomersGroupID() != 0) {

                  $QproductsQuantityCustomersGroup = $this->pm->app->db->prepare('select products_quantity_fixed_group
                                                                                  from :table_products_groups
                                                                                  where products_id = :products_id
                                                                                  and customers_group_id =  :customers_group_id
                                                                                 ');
                  $QproductsQuantityCustomersGroup->bindInt(':products_id', $CLICSHOPPING_Prod::getProductID($Qproducts->valueInt('products_id')));
                  $QproductsQuantityCustomersGroup->bindInt(':customers_group_id', (int)$CLICSHOPPING_Customer->getCustomersGroupID());
                  $QproductsQuantityCustomersGroup->execute();

                  $products_quantity_customers_group = $QproductsQuantityCustomersGroup->fetch();

// do the exact qty in function the customer group and product
                  $products_quantity_customers_group = $products_quantity_customers_group['products_quantity_fixed_group'];
                } else {
                  $products_quantity_customers_group = 1;
                }

                $stock_left = $Qstock->valueInt('products_quantity') - ($Qproducts->valueInt('products_quantity') * $products_quantity_customers_group);

              } else {
                $stock_left = $Qstock->valueInt('products_quantity');
              }

              if ($stock_left != $Qstock->valueInt('products_quantity')) {
                $this->pm->app->db->save('products', ['products_quantity' => (int)$stock_left],
                  ['products_id' => $CLICSHOPPING_Prod::getProductID((int)$Qproducts->valueInt('products_id'))
                  ]
                );
              }

              if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
                $this->pm->app->db->save('products', ['products_status' => 0],
                                                     ['products_id' => $CLICSHOPPING_Prod::getProductID((int)$Qproducts->valueInt('products_id'))]
                                        );
              }

// alert an email if the product stock is < stock reorder level
// Alert by mail if a product is 0 or < 0
              $CLICSHOPPING_Order->sendEmailAlertStockWarning($order_id);
// Email alert when a product is exahuted
              $CLICSHOPPING_Order->sendEmailAlertProductsExhausted($order_id);

            }
          }

// Update products_ordered (for bestsellers list)
          $Qupdate = $this->pm->app->db->prepare('update :table_products
                                                 set products_ordered = products_ordered + :products_ordered
                                                 where products_id = :products_id
                                                 ');

          $Qupdate->bindInt(':products_ordered', $Qproducts->valueInt('products_quantity'));
          $Qupdate->bindInt(':products_id', $Qproducts->valueInt('products_id'));
          $Qupdate->execute();
        } // end while
      }


//*******************************
// email
//*******************************

// lets start with the email confirmation
        $email_order = STORE_NAME . "\n\n";
        $email_order .= CLICSHOPPING::getDef('email_text_order_number', ['store_name' => STORE_NAME]) . ' ' . $order_id . "\n";
        $email_order .= CLICSHOPPING::getDef('email_text_invoice_url') . ' ' . CLICSHOPPING::link(null, 'Account&HistoryInfo&order_id=' . (int)$order_id) . "\n";
        $email_order .= CLICSHOPPING::getDef('email_text_date_ordered') . ' ' . strftime(CLICSHOPPING::getDef('date_format_long')) . "\n\n";

        if ($Qorders->value('comments')) {
          $email_order .= HTML::output($Qorders->value('comments')) . "\n\n";
        }

       $email_order .= CLICSHOPPING::getDef('email_separator')  . "\n\n";
       $email_order .= CLICSHOPPING::getDef('email_text_total') . ' ' . $_REQUEST['montant'] . "\n\n";
       $email_order .= CLICSHOPPING::getDef('email_separator') . "\n\n";

        if ($CLICSHOPPING_Order->content_type != 'virtual') {
          $delivery_address = $Qorders->value('delivery_name') . "\n" .
                               $Qorders->value('delivery_company') . "\n" .
                               $Qorders->value('delivery_street_address') . "\n" .
                               $Qorders->value('delivery_suburb') . "\n" .
                               $Qorders->value('delivery_city') . "\n" .
                               $Qorders->value('delivery_postcode') . "\n" .
                               $Qorders->value('delivery_state') . "\n" .
                               $Qorders->value('delivery_country');

            $email_order .= "\n" . CLICSHOPPING::getDef('email_text_delivery_address') . "\n";
            $email_order .= $delivery_address . "\n\n";
        }

        $billing_address = $Qorders->value('billing_name') . "\n" .
                          $Qorders->value('billing_company') . "\n" .
                          $Qorders->value('billing_street_address') . "\n" .
                          $Qorders->value('billing_suburb') . "\n" .
                          $Qorders->value('billing_city') . "\n" .
                          $Qorders->value('billing_postcode') . "\n" .
                          $Qorders->value('billing_state') . "\n" .
                          $Qorders->value('billing_country');


          $email_order .= "\n" . CLICSHOPPING::getDef('email_text_billing_address') . "\n";
          $email_order .= $billing_address . "\n\n";

          $email_order .= CLICSHOPPING::getDef('email_text_payment_method') . "\n\n";

          $email_order .= $Qorders->value('payment_method') . "\n\n";

          if ($this->pm->email_footer) {
            $email_order .= $this->pm->email_footer . "\n\n";
          }


        $email_order .= TemplateEmail::getTemplateEmailSignature() . "\n\n";
        $email_order .= TemplateEmail::getTemplateEmailTextFooter() . "\n\n";

        $text_email = $email_order;

        $CLICSHOPPING_Mail->clicMail($Qorders->value('customers_name'), $Qorders->value('customers_email_address'), CLICSHOPPING::getDef('email_text_subject', ['store_name' => STORE_NAME]), $text_email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);


// send emails to other people
// SEND_EXTRA_ORDER_EMAILS_TO does'nt work like this, test<test@test.com>, just with test@test.com
        if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
          $email_text_subject = stripslashes(CLICSHOPPING::getDef('email_text_subject', ['store_name' => STORE_NAME]));
          $email_text_subject = html_entity_decode($email_text_subject);

          $text[] = TemplateEmail::getExtractEmailAddress(SEND_EXTRA_ORDER_EMAILS_TO);

          foreach($text as $key => $email) {
            $CLICSHOPPING_Mail->clicMail('', $email[$key], $email_text_subject, $text_email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
          }
        }

//---------------------------------
// Message Authentication
//---------------------------------

        $phase2back_fields = sprintf(MONETICOPAIEMENT_PHASE2BACK_FIELDS, $oTpe->sNumero,
                                                                          $_REQUEST["date"],
                                                                          $_REQUEST['montant'],
                                                                          $_REQUEST['reference'],
                                                                          $_REQUEST['texte-libre'],
                                                                          $oTpe->sVersion,
                                                                          $_REQUEST['code-retour'],
                                                                          $_REQUEST['cvx'],
                                                                          $_REQUEST['vld'],
                                                                          $_REQUEST['brand'],
                                                                          $_REQUEST['status3ds'],
                                                                          $_REQUEST['numauto'],
                                                                          $_REQUEST['motifrefus'],
                                                                          $_REQUEST['originecb'],
                                                                          $_REQUEST['bincb'],
                                                                          $_REQUEST['hpancb'],
                                                                          $_REQUEST['ipclient'],
                                                                          $_REQUEST['originetr'],
                                                                          $_REQUEST['veres'],
                                                                          $_REQUEST['pares']
                                    );

        if ($oHmac->computeHmac($phase2back_fields) == strtolower($_REQUEST['MAC'])) {

          switch($_REQUEST['code-retour']) {
            case "Annulation" :
              CLICSHOPPING::redirect(null, 'Cart');
            break;

            case "payetest":
              if (CLICSHOPPING_APP_DESJARDINS_HO_SERVER == 'Test') {
                $CLICSHOPPING_Mail->clicMail(STORE_NAME . ': Desjardins Monetico', STORE_OWNER_EMAIL_ADDRESS , 'order tested : ' . $MoneticoPaiement_bruteVars['reference'], 'Simple test. This order has been tested but not saved in the backoffice. Please, change your simulation mode in true"', STORE_NAME, STORE_OWNER_EMAIL_ADDRESS);
              }

              $sql_data_array = ['order_id' => $order_id,
                                 'date' => $_REQUEST['date'],
                                 'TPE' => $_REQUEST['ho?TPE'],
                                 'montant' => $_REQUEST['montant'],
                                 'code_retour' => $_REQUEST['code-retour'],
                                 'retourPLUS' => $_REQUEST['retourPLUS'],
                                 'text_libre' => $_REQUEST['texte-libre'],
                                 'ipclient' => $_REQUEST['ipclient'],
                                 'brand' => $_REQUEST['brand']
                                ];

              $this->pm->app->db->save('desjardins_reference', $sql_data_array, ['ref_number' => $_REQUEST['reference']]);

              $CLICSHOPPING_Hooks->call('Orders','Process');

              $CLICSHOPPING_ShoppingCart->reset(true);

              $this->pm->app->db->delete('customers_basket', ['customers_id' => (int)$customer_id]);

              $this->pm->app->db->delete('customers_basket_attributes', ['customers_id' => (int)$customer_id]);

            break;
            case "paiement":
              $sql_data_array = ['order_id' => $order_id,
                                 'date' => $_REQUEST['date'],
                                 'TPE' => $_REQUEST['ho?TPE'],
                                 'montant' => $_REQUEST['montant'],
                                 'code_retour' => $_REQUEST['code-retour'],
                                 'retourPLUS' => $_REQUEST['retourPLUS'],
                                 'text_libre' => $_REQUEST['texte-libre'],
                                 'ipclient' => $_REQUEST['ipclient'],
                                 'brand' => $_REQUEST['brand']
                                ];

              $this->pm->app->db->save('desjardins_reference', $sql_data_array, ['ref_number' => $_REQUEST['reference']]);

              $CLICSHOPPING_Hooks->call('Orders','Process');

              $CLICSHOPPING_ShoppingCart->reset(true);

              $this->pm->app->db->delete('customers_basket', ['customers_id' => (int)$customer_id]);
              $this->pm->app->db->delete('customers_basket_attributes', ['customers_id' => (int)$customer_id]);
            break;
            case "paiement_pf2":
            case "paiement_pf3":
            case "paiement_pf4":
// Payment has been accepted on the productive server for the part #N
// return code is like paiement_pf[#N]
// put your code here (email sending / Database update)
            break;

            case "Annulation_pf2":
            case "Annulation_pf3":
            case "Annulation_pf4":
// Payment has been refused on the productive server for the part #N
// return code is like Annulation_pf[#N]
// put your code here (email sending / Database update)
            break;
          }

          $receipt = MONETICOPAIEMENT_PHASE2BACK_MACOK;
        } else {
  // your code if the HMAC doesn't match
          $receipt = MONETICOPAIEMENT_PHASE2BACK_MACNOTOK . $phase2back_fields;
        } // end
      }
// Send receipt to DESJARDINS server
      printf (MONETICOPAIEMENT_PHASE2BACK_RECEIPT, $receipt);


// Bug
// The session is not remove on the basket
// Temporary solution : kill the customer session
      $QsessionDelete = $this->pm->app->db->prepare('delete value
                                                      from :table_sessions
                                                      where sesskey = :sesskey
                                                      ');

      $QsessionDelete->bindValue(':sesskey', $session);

      CLICSHOPPING::redirect(null, 'Checkout&Success');
   }

// ----------------------------------------------------------------------------
// function getMethod
//
// IN:
// OUT: Données soumises par GET ou POST / Data sent by GET or POST
// description: Renvoie le tableau des données / Send back the data array
// ----------------------------------------------------------------------------

    public static function getMethode() {

     if ($_SERVER["REQUEST_METHOD"] == 'GET') return $_GET;
     if ($_SERVER["REQUEST_METHOD"] == 'POST') return $_POST;

     die ('Invalid REQUEST_METHOD (not GET, not POST).');
   }
 }
