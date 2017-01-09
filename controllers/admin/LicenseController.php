<?php

class LicenseController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product_license';
        $this->className = 'ProductLicense';
        $this->lang = false;
        $this->requiredDatabase = true;
        $this->identifier = 'id_license';
        $this->_orderBy = 'id_license';
        $this->_orderWay = 'DESC';

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();
        $this->context->controller = $this;


        $this->_select = 'CONCAT(pl.name, " (#", a.id_product, ")") AS product_name';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = a.id_product AND pl.id_lang = '.(int)$this->context->language->id.')';
        $this->_use_found_rows = false;

        $this->fields_list = array(
            'id_license' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'product_name' => array(
                'title' => $this->l('Product'),
                'type' => 'select',
                'list' => $this->getProductsList(true),
                'filter_key' => 'a!id_product',
                'filter_type' => 'int',
                'order_key' => 'product_name'
            ),
            'id_order' => array(
                'title' => $this->l('Order ID'),
                'class' => 'fixed-width-xs'
            ),
            'key' => array(
                'title' => $this->l('Key'),
            ),
            'active' => array(
                'title' => $this->l('Enabled'),
                'active' => 'status',
                'filter_key' => 'a!active',
                'align' => 'center',
                'type' => 'bool',
                'orderby' => false,
                'class' => 'fixed-width-sm'
            )
        );

        parent::__construct();
    }

    private function getProductsList($forList = true)
    {
        $products = Product::getSimpleProducts($this->context->language->id);
        $productsArray = array();
        foreach ($products as $product) {
            if ($forList) {
                $productsArray[$product['id_product']] = $product['name'].' (#'.$product['id_product'].')';
            }
            else {
                $product['product_name'] = $product['name'].' (#'.$product['id_product'].')';
                $productsArray[] = $product;
            }
        }

        return $productsArray;
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_license'] = array(
                'href' => self::$currentIndex.'&addproduct_license&token='.$this->token,
                'desc' => $this->l('Add new license', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderForm()
    {
        $license = false;
        $imageUrl = false;
        if (!empty($this->id_object)) {
            $license = new ProductLicense($this->id_object);
            $imageUrl = $license->getHtmlImage();
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Licenses'),
                'icon' => 'icon-globe'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Product'),
                    'name' => 'id_product',
                    'required' => true,
                    'options' => array(
                        'query' => self::getProductsList(false),
                        'id' => 'id_product',
                        'name' => 'product_name',
                    ),
                    'hint' => $this->l('Select product')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('License Key'),
                    'name' => 'key',
                    'maxlength' => 255,
                    'required' => true,
                    'hint' => $this->l('License key')
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('License Image'),
                    'name' => 'filename',
                    'display_image' => true,
                    'image' => $imageUrl ? $imageUrl : false,
                    'delete_url' => self::$currentIndex.'&'.$this->identifier.'='.$this->object->id.'&token='.$this->token.'&deleteImage=1',
                    'hint' => $this->l('License Image'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'active',
                    'required' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" />'
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" />'
                        )
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit($this->table.'Orderby') || Tools::isSubmit($this->table.'Orderway')) {
            $this->filter = true;
        }

        $license = false;
        if (!empty($this->id_object)) {
            $license = new ProductLicense($this->id_object);
        }

        if (isset($_FILES['filename']) && $_FILES['filename']['size'] > 0) {
            if ($license) {
                $license->deleteImage();
            }

            $licenseFilename = ProductLicense::getNewFilename();
            $helper = new HelperUploader('filename');
            $helper->setPostMaxSize(Tools::getOctets(ini_get('upload_max_filesize')))
                ->setSavePath(_PS_DOWNLOAD_DIR_)
                ->upload($_FILES['filename'], $licenseFilename);

            $_POST['display_filename'] = $licenseFilename;
        }

        if (Tools::getValue('deleteImage') && $license) {
            $license->deleteImage();
        }

        if (Tools::isSubmit('delete'.$this->table) && $license) {
            $license->deleteImage();
        }


        if (!count($this->errors)) {
            parent::postProcess();
        }
    }

}
