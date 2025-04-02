<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Aiproje_Custom_Shipping_Labels extends Module
{
    public function __construct()
    {
        $this->name = 'aiproje_custom_shipping_labels';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'AIPROJE';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Recipient Paid Shipping Labels');
        $this->description = $this->l('Customize shipping labels and prices display for recipient-paid shipping methods.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $result = parent::install() &&
            $this->createTables() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayCarrierList') &&
            $this->registerHook('displayAfterCarrier') &&
            $this->registerHook('actionCarrierProcess') &&
            // onepagecheckoutps modülü için ek hook'lar
            $this->registerHook('displayBeforeCarrier') &&
            $this->registerHook('actionOnepagecheckoutpsUpdateCarriersList');
            
        if ($result) {
            $this->clearModuleCache();
        }
        
        return $result;
    }

    public function hookActionCarrierProcess($params)
    {
        // Kargo işlemleri sırasında çalışacak kod buraya gelecek
        return true;
    }
    
    /**
     * onepagecheckoutps modülü için özel hook
     */
    public function hookActionOnepagecheckoutpsUpdateCarriersList($params)
    {
        if (!isset($params['carrier_list']) || !is_array($params['carrier_list'])) {
            return;
        }
        
        // Debug bilgisi ekle
        PrestaShopLogger::addLog('Aiproje Custom Shipping Labels: hookActionOnepagecheckoutpsUpdateCarriersList called', 1);
        
        $carrierList = $params['carrier_list'];
        $modifiedCarriers = [];
        
        foreach ($carrierList as $key => $carrier) {
            if (!isset($carrier['id_carrier'])) {
                $modifiedCarriers[$key] = $carrier;
                continue;
            }
            
            $customLabel = $this->getCustomLabelByCarrierId($carrier['id_carrier']);
            
            // Debug bilgisi
            if ($customLabel) {
                PrestaShopLogger::addLog(
                    "Carrier ID: {$carrier['id_carrier']}, " .
                    "Active: {$customLabel['active']}, " .
                    "Price HTML: " . (isset($customLabel['custom_price_html']) ? substr($customLabel['custom_price_html'], 0, 50) : 'null') . ", " .
                    "Description: " . (isset($customLabel['custom_description']) ? substr($customLabel['custom_description'], 0, 50) : 'null'),
                    1
                );
            }
            
            if ($customLabel && !empty($customLabel['custom_price_html']) && $customLabel['active']) {
                // Kargo fiyatını özelleştir
                $carrier['price_with_tax'] = $customLabel['custom_price_html'];
                $carrier['price_without_tax'] = $customLabel['custom_price_html'];
                $carrier['price'] = $customLabel['custom_price_html'];
                
                // Ek alanlar
                if (isset($carrier['formatted_price'])) {
                    $carrier['formatted_price'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['formatted_price_tax_exc'])) {
                    $carrier['formatted_price_tax_exc'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['formatted_price_tax_inc'])) {
                    $carrier['formatted_price_tax_inc'] = $customLabel['custom_price_html'];
                }
                
                // Onepagecheckoutps modülü için özel alanlar
                if (isset($carrier['price_with_tax_formatted'])) {
                    $carrier['price_with_tax_formatted'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['price_without_tax_formatted'])) {
                    $carrier['price_without_tax_formatted'] = $customLabel['custom_price_html'];
                }
                
                // Ücretsiz kargo gösterimini engelle
                if (isset($carrier['is_free']) && $carrier['is_free']) {
                    $carrier['is_free'] = false;
                }
                
                // Onepagecheckoutps modülü için ek alanlar
                if (isset($carrier['displayPrice'])) {
                    $carrier['displayPrice'] = $customLabel['custom_price_html'];
                }
                
                // Onepagecheckoutps modülünün delivery_options.tpl şablonunda kullanılan alanlar
                if (isset($carrier['delivery_option_price'])) {
                    $carrier['delivery_option_price'] = $customLabel['custom_price_html'];
                }
                
                // Doğrudan HTML içeriğini değiştirmek için
                if (isset($carrier['price_text'])) {
                    $carrier['price_text'] = $customLabel['custom_price_html'];
                }
                
                // Onepagecheckoutps modülünün diğer olası alanları
                if (isset($carrier['display_price'])) {
                    $carrier['display_price'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['display_price_tax_exc'])) {
                    $carrier['display_price_tax_exc'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['display_price_tax_inc'])) {
                    $carrier['display_price_tax_inc'] = $customLabel['custom_price_html'];
                }
            }
            
            if ($customLabel && !empty($customLabel['custom_description']) && $customLabel['active']) {
                // Kargo açıklamasını özelleştir
                $carrier['delay'] = $customLabel['custom_description'];
            }
            
            $modifiedCarriers[$key] = $carrier;
        }
        
        // Debug bilgisi
        PrestaShopLogger::addLog('Modified carriers: ' . json_encode(array_keys($modifiedCarriers)), 1);
        
        return ['carrier_list' => $modifiedCarriers];
    }
    
    /**
     * onepagecheckoutps modülü için ek hook
     */
    public function hookDisplayBeforeCarrier($params)
    {
        // Bu hook onepagecheckoutps modülünde kullanılabilir
        return '';
    }

    public function uninstall()
    {
        $result = parent::uninstall() && $this->dropTables();
        
        if ($result) {
            $this->clearModuleCache();
        }
        
        return $result;
    }
    
    /**
     * Modül kurulumunda veya kaldırılmasında önbelleği temizle
     */
    private function clearModuleCache()
    {
        if (method_exists('Tools', 'clearCache')) {
            Tools::clearCache();
        }
        if (method_exists('Tools', 'clearCompile')) {
            Tools::clearCompile();
        }
        return true;
    }

    private function createTables()
    {
        $sql = [];
        
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
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function dropTables()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang`';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function hookDisplayHeader()
    {
        // CSS dosyasını yükle
        // Sipariş sayfası veya onepagecheckoutps modülü için CSS yükle
        if ($this->context->controller->php_self == 'order' || 
            $this->context->controller->module == 'onepagecheckoutps' || 
            Tools::getValue('controller') == 'order') {
            
            // Debug bilgisi ekle
            PrestaShopLogger::addLog('Aiproje Custom Shipping Labels: Loading CSS', 1);
            
            $this->context->controller->registerStylesheet(
                'aiproje-custom-shipping-labels',
                'modules/'.$this->name.'/views/css/front.css',
                ['media' => 'all', 'priority' => 150]
            );
            
            // JavaScript ekle - onepagecheckoutps modülü için özel işlem
            // Tüm kargo seçeneklerini al
            $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, ALL_CARRIERS);
            $customLabels = [];
            
            foreach ($carriers as $carrier) {
                $customLabel = $this->getCustomLabelByCarrierId($carrier['id_carrier']);
                if ($customLabel && !empty($customLabel['custom_price_html']) && $customLabel['active']) {
                    $customLabels[$carrier['id_carrier']] = [
                        'price_html' => $customLabel['custom_price_html'],
                        'description' => isset($customLabel['custom_description']) ? $customLabel['custom_description'] : ''
                    ];
                }
            }
            
            // JavaScript'e veri aktar
            Media::addJsDef([
                'aiproje_custom_labels' => $customLabels
            ]);
            
            // JavaScript dosyasını yükle
            $this->context->controller->registerJavascript(
                'aiproje-custom-shipping-labels-js',
                'modules/'.$this->name.'/views/js/front.js',
                ['position' => 'bottom', 'priority' => 200]
            );
            
            // Debug bilgisi ekle
            PrestaShopLogger::addLog('Aiproje Custom Shipping Labels: JavaScript loaded with ' . count($customLabels) . ' custom labels', 1);
        }
        return;
    }
    
    public function hookDisplayCarrierList($params)
    {
        // Kargo seçeneklerini özelleştir
        if (!isset($params['carriers']) || !is_array($params['carriers'])) {
            return;
        }
        
        // Debug bilgisi ekle
        PrestaShopLogger::addLog('Aiproje Custom Shipping Labels: hookDisplayCarrierList called', 1);
        
        $modifiedCarriers = [];
        foreach ($params['carriers'] as $carrier) {
            if (!isset($carrier['id_carrier'])) {
                $modifiedCarriers[] = $carrier;
                continue;
            }
            
            $customLabel = $this->getCustomLabelByCarrierId($carrier['id_carrier']);
            
            // Debug bilgisi
            if ($customLabel) {
                PrestaShopLogger::addLog(
                    "Carrier ID: {$carrier['id_carrier']}, " .
                    "Active: {$customLabel['active']}, " .
                    "Price HTML: " . (isset($customLabel['custom_price_html']) ? substr($customLabel['custom_price_html'], 0, 50) : 'null') . ", " .
                    "Description: " . (isset($customLabel['custom_description']) ? substr($customLabel['custom_description'], 0, 50) : 'null'),
                    1
                );
            }
            
            if ($customLabel && !empty($customLabel['custom_price_html']) && $customLabel['active']) {
                // Kargo fiyatını özelleştir
                // Prestashop 8.x'de kargo fiyatı gösterimi için bu alanlar kullanılıyor
                $carrier['price_with_tax'] = $customLabel['custom_price_html'];
                $carrier['price_without_tax'] = $customLabel['custom_price_html'];
                $carrier['price'] = $customLabel['custom_price_html'];
                
                // Ek olarak, bazı temalar için bu alanları da ekleyelim
                if (isset($carrier['formatted_price'])) {
                    $carrier['formatted_price'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['formatted_price_tax_exc'])) {
                    $carrier['formatted_price_tax_exc'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['formatted_price_tax_inc'])) {
                    $carrier['formatted_price_tax_inc'] = $customLabel['custom_price_html'];
                }
                
                // Prestashop 8.1.7 için ek alanlar
                if (isset($carrier['total_price_with_tax'])) {
                    $carrier['total_price_with_tax'] = $customLabel['custom_price_html'];
                }
                if (isset($carrier['total_price_without_tax'])) {
                    $carrier['total_price_without_tax'] = $customLabel['custom_price_html'];
                }
                
                // Ücretsiz kargo gösterimini engelle
                if (isset($carrier['is_free']) && $carrier['is_free']) {
                    $carrier['is_free'] = false;
                }
            }
            
            if ($customLabel && !empty($customLabel['custom_description']) && $customLabel['active']) {
                // Kargo açıklamasını özelleştir
                $carrier['delay'] = $customLabel['custom_description'];
            }
            
            $modifiedCarriers[] = $carrier;
        }
        
        // Değiştirilmiş kargo listesini döndür
        return ['carriers' => $modifiedCarriers];
    }
    
    public function hookDisplayAfterCarrier($params)
    {
        // Kargo seçeneğinden sonra özel içerik ekle
        if (!isset($params['carrier']) || !isset($params['carrier']['id_carrier'])) {
            return;
        }
        
        $carrierId = (int)$params['carrier']['id_carrier'];
        $customLabel = $this->getCustomLabelByCarrierId($carrierId);
        
        // Eğer özel etiket yoksa veya aktif değilse veya içerik yoksa gösterme
        if (!$customLabel || !$customLabel['active'] || (!$customLabel['custom_price_html'] && !$customLabel['custom_description'])) {
            return;
        }
        
        $this->context->smarty->assign([
            'customLabel' => $customLabel
        ]);
        
        return $this->display(__FILE__, 'views/templates/hook/displayAfterCarrier.tpl');
    }

    // Bu metot artık kullanılmıyor, hook'lar ile değiştirildi

    private function getCustomLabelByCarrierId($carrierId)
    {
        $id_lang = $this->context->language->id;
        $db = Db::getInstance();
        
        // Debug bilgisi ekle
        PrestaShopLogger::addLog("getCustomLabelByCarrierId called for carrier ID: {$carrierId}", 1);
        
        // Önce mevcut dil için veriyi al
        $sql = 'SELECT l.*, c.active, c.id_custom_label 
                FROM `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels` c
                LEFT JOIN `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang` l 
                ON c.id_custom_label = l.id_custom_label
                WHERE c.id_carrier = ' . (int)$carrierId . ' 
                AND l.id_lang = ' . (int)$id_lang;
        
        $result = $db->getRow($sql);
        PrestaShopLogger::addLog("SQL query for current language: {$sql}", 1);
        PrestaShopLogger::addLog("Result for current language: " . json_encode($result), 1);
        
        // Eğer mevcut dilde veri yoksa veya eksikse, varsayılan dil için kontrol et
        if (!$result || (empty($result['custom_price_html']) && empty($result['custom_description']))) {
            // Önce kargo etiketi kaydının var olup olmadığını kontrol et
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels` 
                    WHERE id_carrier = ' . (int)$carrierId;
            $labelData = $db->getRow($sql);
            PrestaShopLogger::addLog("SQL query for label data: {$sql}", 1);
            PrestaShopLogger::addLog("Label data: " . json_encode($labelData), 1);
            
            if ($labelData) {
                // Varsayılan dil için veriyi al (en düşük ID'li dil)
                $sql = 'SELECT l.*, ' . (int)$labelData['active'] . ' as active 
                        FROM `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang` l 
                        WHERE l.id_custom_label = ' . (int)$labelData['id_custom_label'] . ' 
                        ORDER BY l.id_lang ASC 
                        LIMIT 1';
                $defaultLangData = $db->getRow($sql);
                PrestaShopLogger::addLog("SQL query for default language: {$sql}", 1);
                PrestaShopLogger::addLog("Default language data: " . json_encode($defaultLangData), 1);
                
                if ($defaultLangData) {
                    // Varsayılan dil verisini kullan
                    $result = array_merge($defaultLangData, ['active' => $labelData['active']]);
                    PrestaShopLogger::addLog("Using default language data", 1);
                } else {
                    // Hiçbir dilde veri yoksa, sadece active durumunu döndür
                    $result = ['active' => $labelData['active'], 'id_custom_label' => $labelData['id_custom_label']];
                    PrestaShopLogger::addLog("No language data found, using only active status", 1);
                }
            } else {
                PrestaShopLogger::addLog("No label data found for carrier ID: {$carrierId}", 1);
            }
        }
        
        return $result;
    }

    public function getContent()
    {
        $output = '';
        
        // Admin CSS dosyasını yükle
        $this->context->controller->addCSS($this->_path.'views/css/admin.css', 'all');
        
        if (Tools::isSubmit('submitAiprojeShippingLabels')) {
            if ($this->postProcess()) {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError($this->l('An error occurred while saving settings'));
            }
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, ALL_CARRIERS);
        $carrierOptions = [];
        foreach ($carriers as $carrier) {
            $carrierOptions[] = [
                'id_carrier' => $carrier['id_carrier'],
                'name' => $carrier['name']
            ];
        }

        // Ana form başlığı ve açıklaması
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Configure custom shipping labels for recipient-paid shipping methods. You can customize the price display and description for each carrier.'),
                'input' => [],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];
        
        // Yardımcı açıklama ekle
        $fields_form['form']['input'][] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<div class="alert alert-info">' . 
                $this->l('For recipient-paid shipping methods, set the carrier price to 0 in PrestaShop and use this module to display a custom message instead.') . 
                '</div>',
        ];

        // Her kargo seçeneği için ayrı bir bölüm oluştur
        foreach ($carrierOptions as $carrier) {
            // Kargo seçeneği başlığı
            $fields_form['form']['input'][] = [
                'type' => 'html',
                'name' => 'carrier_title_' . (int)$carrier['id_carrier'],
                'html_content' => '<div class="panel panel-aiproje-shipping">' .
                    '<div class="panel-heading">' . $carrier['name'] . '</div>' .
                    '<div class="panel-body">',
            ];
            
            // Aktif/Pasif düğmesi
            $fields_form['form']['input'][] = [
                'type' => 'switch',
                'label' => sprintf($this->l('Enable for %s'), $carrier['name']),
                'name' => 'CUSTOM_LABEL_ACTIVE_' . (int)$carrier['id_carrier'],
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                    ],
                ],
                'desc' => $this->l('Enable or disable custom labels for this carrier.'),
            ];

            // Her dil için alanlar
            foreach (Language::getLanguages(true) as $lang) {
                $fields_form['form']['input'][] = [
                    'type' => 'textarea',
                    'label' => sprintf($this->l('Price HTML for %s (%s)'), $carrier['name'], $lang['name']),
                    'name' => 'CUSTOM_PRICE_HTML_' . (int)$carrier['id_carrier'] . '_' . (int)$lang['id_lang'],
                    'lang' => false,
                    'cols' => 40,
                    'rows' => 3,
                    'desc' => $this->l('Enter HTML code to display instead of the price. Example: <strong>Alıcı Ödemeli</strong> or <span style="color:red">Alıcı Tarafından Ödenir</span>'),
                ];

                $fields_form['form']['input'][] = [
                    'type' => 'textarea',
                    'label' => sprintf($this->l('Description for %s (%s)'), $carrier['name'], $lang['name']),
                    'name' => 'CUSTOM_DESC_' . (int)$carrier['id_carrier'] . '_' . (int)$lang['id_lang'],
                    'lang' => false,
                    'cols' => 40,
                    'rows' => 3,
                    'desc' => $this->l('Enter custom description for this shipping method. Leave empty to use default carrier description.'),
                ];
            }
            
            // Kargo seçeneği bölümünü kapat
            $fields_form['form']['input'][] = [
                'type' => 'html',
                'name' => 'carrier_end_' . (int)$carrier['id_carrier'],
                'html_content' => '</div></div>',
            ];
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAiprojeShippingLabels';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues($carrierOptions),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    protected function getConfigFieldsValues($carriers)
    {
        $fields = [];
        foreach ($carriers as $carrier) {
            $customLabel = $this->getCustomLabelData($carrier['id_carrier']);
            
            // Aktif/Pasif durumunu ayarla
            $fields['CUSTOM_LABEL_ACTIVE_' . (int)$carrier['id_carrier']] = $customLabel ? (int)$customLabel['active'] : 0;
            
            foreach (Language::getLanguages(true) as $lang) {
                // Fiyat HTML değerini al
                $priceHtml = '';
                if ($customLabel) {
                    $priceHtml = $this->getCustomLabelLangData($customLabel['id_custom_label'], (int)$lang['id_lang'], 'custom_price_html');
                    // Null değer kontrolü
                    if ($priceHtml === null) {
                        $priceHtml = '';
                    }
                }
                $fields['CUSTOM_PRICE_HTML_' . (int)$carrier['id_carrier'] . '_' . (int)$lang['id_lang']] = $priceHtml;
                
                // Açıklama değerini al
                $description = '';
                if ($customLabel) {
                    $description = $this->getCustomLabelLangData($customLabel['id_custom_label'], (int)$lang['id_lang'], 'custom_description');
                    // Null değer kontrolü
                    if ($description === null) {
                        $description = '';
                    }
                }
                $fields['CUSTOM_DESC_' . (int)$carrier['id_carrier'] . '_' . (int)$lang['id_lang']] = $description;
                
                // Debug bilgisi
                PrestaShopLogger::addLog(
                    "Config field values - Carrier ID: {$carrier['id_carrier']}, " .
                    "Lang ID: {$lang['id_lang']}, " .
                    "Price HTML: {$priceHtml}, " .
                    "Description: {$description}", 
                    1
                );
            }
        }
        return $fields;
    }

    private function getCustomLabelData($carrierId)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels` 
            WHERE `id_carrier` = ' . (int)$carrierId
        );
    }

    private function getCustomLabelLangData($labelId, $langId, $field)
    {
        return Db::getInstance()->getValue(
            'SELECT `' . $field . '` FROM `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang` 
            WHERE `id_custom_label` = ' . (int)$labelId . ' AND `id_lang` = ' . (int)$langId
        );
    }

    protected function postProcess()
    {
        try {
            $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, ALL_CARRIERS);
            if (!is_array($carriers) || empty($carriers)) {
                throw new Exception($this->l('No carriers found'));
            }

            $db = Db::getInstance();
            $db->execute('START TRANSACTION');
            
            // Debug bilgisi ekle
            PrestaShopLogger::addLog('Aiproje Custom Shipping Labels: Form submission started', 1);
            
            // Form verilerini logla
            PrestaShopLogger::addLog('Form data: ' . json_encode($_POST), 1);

            foreach ($carriers as $carrier) {
                $carrierId = (int)$carrier['id_carrier'];
                if (!$carrierId) {
                    continue;
                }

                // Aktif/Pasif durumunu al - "_1" ekini dikkate al
                $activeFieldName = 'CUSTOM_LABEL_ACTIVE_' . $carrierId;
                $active = Tools::getValue($activeFieldName, 0);
                // Eğer değer 0 ise, "_1" ekiyle tekrar dene
                if ($active == 0) {
                    $active = Tools::getValue($activeFieldName . '_1', 0);
                }
                $active = ($active == 1) ? 1 : 0;
                
                // Debug bilgisi
                PrestaShopLogger::addLog("Carrier ID: {$carrierId}, Active: {$active}", 1);

                // Mevcut kaydı kontrol et veya yeni kayıt oluştur
                $customLabel = $this->getCustomLabelData($carrierId);
                $labelId = null;

                if (!$customLabel) {
                    // Yeni kayıt ekle
                    $result = $db->insert(
                        'aiproje_custom_shipping_labels',
                        [
                            'id_carrier' => $carrierId,
                            'active' => $active
                        ],
                        true,
                        true,
                        Db::INSERT
                    );
                    
                    if ($result) {
                        $labelId = (int)$db->Insert_ID();
                        PrestaShopLogger::addLog("New label created with ID: {$labelId}", 1);
                    } else {
                        throw new Exception($this->l('Failed to insert custom label record'));
                    }
                } else {
                    // Mevcut kaydı güncelle
                    $labelId = (int)$customLabel['id_custom_label'];
                    if (!$db->update(
                        'aiproje_custom_shipping_labels',
                        ['active' => $active],
                        'id_custom_label = ' . (int)$labelId,
                        0,
                        true,
                        true
                    )) {
                        throw new Exception($this->l('Failed to update custom label record'));
                    }
                    PrestaShopLogger::addLog("Updated label ID: {$labelId} with active: {$active}", 1);
                }

                if ($labelId) {
                    // Dil verilerini güncelle
                    $languages = Language::getLanguages(true);
                    foreach ($languages as $lang) {
                        $langId = (int)$lang['id_lang'];
                        if (!$langId) {
                            continue;
                        }
        
                        // Fiyat HTML alanını al - "_1" ekini dikkate al
                        $priceFieldName = 'CUSTOM_PRICE_HTML_' . $carrierId . '_' . $langId;
                        $priceHtml = Tools::getValue($priceFieldName, '');
                        // Eğer değer boşsa, "_1" ekiyle tekrar dene
                        if (empty($priceHtml)) {
                            $priceHtml = Tools::getValue($priceFieldName . '_1', '');
                        }
                        
                        // Açıklama alanını al - "_1" ekini dikkate al
                        $descFieldName = 'CUSTOM_DESC_' . $carrierId . '_' . $langId;
                        $description = Tools::getValue($descFieldName, '');
                        // Eğer değer boşsa, "_1" ekiyle tekrar dene
                        if (empty($description)) {
                            $description = Tools::getValue($descFieldName . '_1', '');
                        }
                        
                        // Debug bilgisi
                        PrestaShopLogger::addLog("Lang ID: {$langId}, Price Field: {$priceFieldName}, Value: {$priceHtml}", 1);
                        PrestaShopLogger::addLog("Lang ID: {$langId}, Desc Field: {$descFieldName}, Value: {$description}", 1);

                        // Veritabanı için veriyi hazırla
                        $data = [
                            'id_custom_label' => (int)$labelId,
                            'id_lang' => (int)$langId,
                            'custom_price_html' => pSQL($priceHtml, true),
                            'custom_description' => pSQL($description, true)
                        ];

                        // Dil kaydının var olup olmadığını kontrol et
                        $exists = $db->getValue(
                            'SELECT 1 FROM `' . _DB_PREFIX_ . 'aiproje_custom_shipping_labels_lang` '
                            . 'WHERE `id_custom_label` = ' . (int)$labelId . ' AND `id_lang` = ' . (int)$langId
                        );

                        if ($exists) {
                            // Mevcut kaydı güncelle
                            if (!$db->update(
                                'aiproje_custom_shipping_labels_lang',
                                $data,
                                'id_custom_label = ' . (int)$labelId . ' AND id_lang = ' . (int)$langId,
                                0,
                                true,
                                true
                            )) {
                                throw new Exception($this->l('Failed to update language data'));
                            }
                            PrestaShopLogger::addLog("Updated language data for label ID: {$labelId}, lang ID: {$langId}", 1);
                        } else {
                            // Yeni kayıt ekle
                            if (!$db->insert(
                                'aiproje_custom_shipping_labels_lang',
                                $data,
                                true,
                                true,
                                Db::INSERT
                            )) {
                                throw new Exception($this->l('Failed to insert language data'));
                            }
                            PrestaShopLogger::addLog("Inserted language data for label ID: {$labelId}, lang ID: {$langId}", 1);
                        }
                    }
                }
            }
            
            // İşlem başarılı, önbelleği temizle
            $db->execute('COMMIT');
            $this->clearModuleCache();
            PrestaShopLogger::addLog('Aiproje Custom Shipping Labels: Form submission completed successfully', 1);
            return true;
        } catch (Exception $e) {
            $db->execute('ROLLBACK');
            $this->context->controller->errors[] = $e->getMessage();
            PrestaShopLogger::addLog('Aiproje Custom Shipping Labels Error: ' . $e->getMessage(), 3);
            return false;
        }
    }
}