<?php   

if (!defined('_PS_VERSION_'))
    exit;

include_once _PS_MODULE_DIR_.'license/ProductLicense.php';

class License extends Module
{

    public function __construct()
    {
        $this->name = 'license';
        $this->tab = 'smart_shopping';
        $this->version = '1.0.0';
        $this->author = 'Alex Suvorov from Ironshop.fr';
        $this->need_instance = 0;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->l('Selling licenses module');
        $this->description = $this->l('Allow to sell different licenses for one product.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        
        if (!Configuration::get('LICENSE_NAME')) {
            $this->warning = $this->l('No name provided.'); 
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }


        if (!parent::install()) {
            return false;
        }

        $res = $this->createTable();
        $res &= $this->registerHook('actionOrderStatusPostUpdate');
        $res &= $this->registerHook('displayOrderDetail');

        return (bool)$res;
    }

    protected function createTable()
    {
        /* Slides */
        $res = (bool)Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_license` (
                `id_license` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_product` int(10) unsigned NOT NULL,
                `id_order` int(10) unsigned NOT NULL,
                `key` text NOT NULL,
                `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                `display_filename` varchar(255) DEFAULT NULL,
                `filename` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id_license`),
                KEY `id_product` (`id_product`),
                KEY `id_order` (`id_order`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
        ');

        return $res;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $res = $this->deleteTable();

        return (bool)$res;
    }

    protected function deleteTable()
    {
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'product_license`;
        ');
    }

    public function hookactionOrderStatusPostUpdate($params)
    {
        $id_order = (int)$params['id_order'];
        $new_os = $params['newOrderStatus'];

        $order = new Order($id_order);

        $orderProducts = $order->getProducts();
        if ($orderProducts && $new_os && $new_os->logable) {
            foreach ($orderProducts as $key => $product) {
                ProductLicense::addLicenseInOrder($id_order, $product['product_id'], $product['product_quantity']);
            }
        }   
    }

    public function hookdisplayOrderDetail($params)
    {
        if (!($params['order'] instanceof Order)) {
            return;
        }

        $order = $params['order'];

        $licenseKeys = ProductLicense::getKeysForOrder($order->id);
        
        if (empty($licenseKeys)) {
            return;
        }

        $orderProducts = $order->getProducts();

        if ($orderProducts) {
            foreach ($orderProducts as $key => $product) {
                foreach ($licenseKeys as $k=>$key) {
                    if ($key['id_product'] == $product['product_id']) {
                        $licenseKeys[$k]['product'] = $product['product_name'];
                    }
                }
            }
        }
        
        $this->smarty->assign('licenseKeys', $licenseKeys);
        return $this->display(__FILE__, 'license_keys.tpl');
    }

}
