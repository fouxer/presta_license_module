<?php

class ProductLicense extends ObjectModel
{
    public $id_license;
    public $id_product;
    public $id_order;
    public $key;
    public $active;
    public $display_filename;
    public $filename;

    public static $definition = array(
        'table' => 'product_license',
        'primary' => 'id_license',
        'multilang' => false,
        'fields' => array(
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'key' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 255),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'display_filename' => array('type' => self::TYPE_STRING, 'size' => 255),
            'filename' => array('type' => self::TYPE_STRING, 'size' => 255),
        )
    );

    public function delete()
    {
        if (!empty($this->id_order)) {
            return false;
        }

        $res = Db::getInstance()->execute('
            DELETE FROM `'._DB_PREFIX_.'product_license`
            WHERE `id_license` = '.(int)$this->id
        );

        $res &= parent::delete();
        return $res;
    }

    public static function getLicenseCountByOrder($id_order, $id_product)
    {
        return Db::getInstance()->getValue('
        SELECT COUNT(id_license)
        FROM '._DB_PREFIX_.'product_license
        WHERE id_order=' . (int)$id_order.' AND id_product=' . (int)$id_product, true, false);
    }

    public static function getFreeLicenseForProduct($id_product)
    {
        return Db::getInstance()->getRow('
        SELECT id_license
        FROM '._DB_PREFIX_.'product_license
        WHERE id_product=' . (int)$id_product. ' AND active = 1 AND id_order = 0
        ORDER BY id_license ASC', true, false);
    }

    public static function getOrderByDisplayFilename($display_filename)
    {
        return Db::getInstance()->getValue('
        SELECT id_order
        FROM '._DB_PREFIX_.'product_license
        WHERE display_filename="' . pSQL($display_filename). '" AND active = 1', true, false);
    }

    public static function getKeysForOrder($id_order)
    {
        $licenses = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT id_license, id_product
            FROM '._DB_PREFIX_.'product_license
            WHERE id_order = '. (int)$id_order .' AND active = 1
            ORDER BY id_product ASC'
        );

        if (empty($licenses)) {
            return false;
        }

        $keys = array();
        foreach ($licenses as $li) {
            $license = new ProductLicense($li['id_license']);
            $keys[] = array(
                'id_product' => $li['id_product'],
                'key' => $license->key,
                'htmlImage' => $license->getHtmlImage(),
            );
        }

        return $keys;
    }

    public static function addLicenseInOrder($id_order, $id_product, $productQty=1)
    {
        $existCount = self::getLicenseCountByOrder($id_order, $id_product);
        if ($exist >= $productQty) {
            return false;
        }

        for ($i=1; $i<=($productQty-$exist); $i++) {
            $license = self::getFreeLicenseForProduct($id_product);
            if (!empty($license)) {
                Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'product_license SET id_order = '. (int)$id_order .' WHERE id_license = ' . $license['id_license']);
            }
        }

        return true;
    }

    public static function getNewFilename()
    {
        do {
            $filename = sha1(microtime());
        } while (file_exists(_PS_DOWNLOAD_DIR_.$filename));
        return $filename;
    }

    public function getImageLink()
    {
        if (!empty($this->display_filename)) {
            return _PS_BASE_URL_.__PS_BASE_URI__.'module/license/GetImage?image_key=' . $this->display_filename;
        }
        else {
            return false;
        }
    }

    public function getHtmlImage()
    {
        $link = $this->getImageLink();
        if ($link) {
            return '<img src="'.$link.'" />';
        }
        else {
            return false;
        }
    }

    public function deleteImage($force_delete = false)
    {
        if ($this->checkFile()) {
            unlink(_PS_DOWNLOAD_DIR_.$this->display_filename);
        }

        $this->filename = '';
        $this->display_filename = '';
        $this->save();

        return true;
    }

    public function checkFile()
    {
        if (!$this->display_filename) {
            return false;
        }
        return file_exists(_PS_DOWNLOAD_DIR_.$this->display_filename);
    }

}
