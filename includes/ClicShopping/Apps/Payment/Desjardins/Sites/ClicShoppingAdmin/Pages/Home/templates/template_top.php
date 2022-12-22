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

  use ClicShopping\OM\HTML;
  use ClicShopping\OM\HTTP;
  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;

  $CLICSHOPPING_MessageStack = Registry::get('MessageStack');
  $CLICSHOPPING_Desjardins = Registry::get('Desjardins');

  $CLICSHOPPING_Page = Registry::get('Site')->getPage();

  if ($CLICSHOPPING_MessageStack->exists('Desjardins')) {
    echo $CLICSHOPPING_MessageStack->get('Desjardins');
  }
?>

<div class="contentBody">
<div class="row">
  <div class="col-md-12">
    <div class="card card-block headerCard">
      <div class="row">
        <span class="col-md-1 logoHeading"><?php echo HTML::image($CLICSHOPPING_Template->getImageDirectory() . 'categories/modules_modules_checkout_payment.gif', $CLICSHOPPING_Desjardins->getDef('desjardins'), '40', '40'); ?></span>
        <span class="col-md-4 pageHeading"><?php echo '&nbsp;' . $CLICSHOPPING_Desjardins->getDef('desjardins')  . ' v' . $CLICSHOPPING_Desjardins->getVersion(); ?></span>
        <span class="col-md-7 text-end">
          <span class="text-end" style="padding-left:5px;"><?php echo  '<a href="' . $CLICSHOPPING_Desjardins->link('Info') . '">' . $CLICSHOPPING_Desjardins->getDef('app_link_info') . '</a> <a href="' . $CLICSHOPPING_Desjardins->link('Privacy') . '">' . $CLICSHOPPING_Desjardins->getDef('app_link_privacy') . '</a>'; ?></span>
        </span>
      </div>
    </div>
  </div>
</div>
<div class="separator"></div>

  <div class="row">
    <div class="col-md-12">
      <div class="card card-block headerCard">
        <div class="row">
          <span class="col-md-2">
            <?php echo HTML::button($CLICSHOPPING_Desjardins->getDef('button_configure'), null, $CLICSHOPPING_Desjardins->link('Configure'), 'warning'); ?>
          </span>
          <span class="col-md-2">
             <?php echo HTML::button($CLICSHOPPING_Desjardins->getDef('button_log'), null, $CLICSHOPPING_Desjardins->link('Log'), 'danger'); ?>
          </span>
          <span class="col-md-8 text-end">
            <?php echo HTML::button($CLICSHOPPING_Desjardins->getDef('button_sort_order'), null, CLICSHOPPING::link(null, 'A&Configuration\Modules&Modules&set=payment'),  'primary'); ?>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="separator"></div>
  <div class="alert alert-info" role="alert">
    <?php echo $CLICSHOPPING_Desjardins->getDef('return_url', ['return_desjardins_url' => HTTP::getShopUrlDomain() . 'index.php?order&desjardins&success&ho']);  ?>
  </div>
