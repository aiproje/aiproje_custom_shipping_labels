<?php
/**
 * 2023-2025 AIPROJE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    AIPROJE <info@aiproje.com>
 * @copyright 2023-2025 AIPROJE
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  PrestaShop module
 * @package   RecipientPaidShipping
 */

$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;