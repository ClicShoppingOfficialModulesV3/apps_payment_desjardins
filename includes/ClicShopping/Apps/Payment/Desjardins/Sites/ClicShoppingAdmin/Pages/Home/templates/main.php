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

use ClicShopping\OM\Registry;
  use ClicShopping\OM\HTTP;

  $CLICSHOPPING_Desjardins = Registry::get('Desjardins');

  require_once(__DIR__ . '/template_top.php');
?>
  <div class="contentBody">
    <div class="separator"></div>
      <div class="row">
        <div class="col-md-12">
          <div class="card card-block headerCard">
            <div class="col-md-12">
              <?php echo $CLICSHOPPING_Desjardins->getDef('text_intro');  ?>
              <?php echo $CLICSHOPPING_Desjardins->getDef('return_url', ['return_desjardins_url' => HTTP::getShopUrlDomain() . 'index.php?order&desjardins&success&ho']);  ?>
            </div>
          </div>
        </div>
    </div>
  </div>
<?php
  require_once(__DIR__ . '/template_bottom.php');
