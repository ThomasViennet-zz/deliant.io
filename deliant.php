<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
  exit;
}

class Deliant extends Module
{
  protected $config_form = false;

  public function __construct()
  {
    $this->name = 'deliant';
    $this->tab = 'analytics_stats';
    $this->version = '1.0.0';
    $this->author = 'deliant';
    $this->need_instance = 0;

    /**
    * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
    */
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('deliant');
    $this->description = $this->l('Understand my data simply.');

    $this->confirmUninstall = $this->l('Are you sure you want to uninstall Deliant ?');

    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
  }

  /**
  * Don't forget to create update methods if needed:
  * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
  */
  public function install()
  {
    Configuration::updateValue('DELIANT_LIVE_MODE', false);

    include(dirname(__FILE__).'/sql/install.php');

    return parent::install() &&
    $this->registerHook('header') &&
    $this->registerHook('backOfficeHeader') &&
    $this->registerHook('displayHeader') &&
    $this->registerHook('registerGDPRConsent') &&
    $this->registerHook('registerGDPRConsent') &&
    $this->registerHook('actionDeleteGDPRCustomer') &&
    $this->registerHook('actionExportGDPRData');
  }

  public function uninstall()
  {
    Configuration::deleteByName('DELIANT_LIVE_MODE');

    include(dirname(__FILE__).'/sql/uninstall.php');

    return parent::uninstall();
  }

  public function hookActionDeleteGDPRCustomer($customer)
  {
    // if (!empty($customer['email']) && Validate::isEmail($customer['email'])) {
    //     $sql = "DELETE FROM "._DB_PREFIX_."popnewsletter_subcribers WHERE email = '".pSQL($customer['email'])."'";
    //     if (Db::getInstance()->execute($sql)) {
    //         return json_encode(true);
    //     }
    //     return json_encode($this->l('Newsletter Popup : Unable to delete customer using email.'));
    // }
  }

  public function hookActionExportGDPRData($customer)
  {
    // if (!Tools::isEmpty($customer['email']) && Validate::isEmail($customer['email'])) {
    //     $sql = "SELECT * FROM "._DB_PREFIX_."popnewsletter_subcribers WHERE email = '".pSQL($customer['email'])."'";
    //     if ($res = Db::getInstance()->ExecuteS($sql)) {
    //         return json_encode($res);
    //     }
    //     return json_encode($this->l('Newsletter Popup : Unable to export customer using email.'));
    // }
  }

  public function hookDisplayHeader()
  {
    $this->context = Context::getContext();
    $id_customer = $this->context->customer->id;

    $utm_source = Tools::getValue('utm_source');
    $utm_medium = Tools::getValue('utm_medium');
    $utm_campaign = Tools::getValue('utm_campaign');
    $utm_term = Tools::getValue('utm_term');
    $utm_content = Tools::getValue('utm_content');

    if (!empty($utm_source) or !empty($utm_medium) or !empty($utm_campaign) or !empty($utm_term) or !empty($utm_content))
    {
      $date = date("Y-m-d H:i:s");
      $UTM_NOW = array('id_customer'=> $id_customer, 'utm_source'=>$utm_source, 'utm_medium'=>$utm_medium, 'utm_campaign'=>$utm_campaign, 'utm_content'=>$utm_content, 'utm_term'=>$utm_term, 'date'=>$date);

      if (!empty($id_customer))
      {
        Db::getInstance()->insert('deliant', $UTM_NOW);
      } else {

        if ($this->context->cookie->__isset('deliant'))
        {
          $cookieDeliant = $this->context->cookie->__get('deliant');
          $UTM = unserialize($cookieDeliant);
          array_push($UTM, $UTM_NOW);

          // $this->context->cookie->__set('deliant', serialize($UTM), time()+36000);
          // $this->context->cookie->write();

          setcookie("deliant", serialize($UTM), time()+36000);
        } else {
          $UTM = array($UTM_NOW);

          // $this->context->cookie->__set('deliant', serialize($UTM), time()+36000);
          // $this->context->cookie->write();

          setcookie("deliant", serialize($UTM), time()+36000);
        }
      }
    }

    if (!empty($id_customer) and isset($_COOKIE['deliant']))
    {
      $cookieDeliant = $this->context->cookie->__get('deliant');
      $UTM = unserialize($cookieDeliant);

      foreach ($UTM as $key => &$value) {
        $value['id_customer'] = $id_customer;
        Db::getInstance()->insert('deliant', $value);
      }
      setcookie("deliant", '', -1);
      unset($cookieDeliant);
    }
  }
}
