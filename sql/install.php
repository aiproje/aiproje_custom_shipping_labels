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

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels` (
    `id_custom_label` int(11) NOT NULL AUTO_INCREMENT,
    `id_carrier` int(11) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_custom_label`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang` (
    `id_custom_label` int(11) NOT NULL,
    `id_lang` int(11) NOT NULL,
    `custom_price_html` text,
    `custom_description` text,
    PRIMARY KEY (`id_custom_label`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;