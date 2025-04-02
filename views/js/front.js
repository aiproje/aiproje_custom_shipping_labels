/**
 * 2023-2025 AIPROJE
 *
 * Alıcı Ödemeli Kargo Modülü
 *
 * @author    AIPROJE <info@aiproje.com>
 * @copyright 2023-2025 AIPROJE
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

document.addEventListener('DOMContentLoaded', function() {
    // onepagecheckoutps modülü için özel işlem
    if (typeof aiproje_custom_labels !== 'undefined') {
        // Kargo seçeneklerini güncelle
        updateShippingLabels();
        
        // AJAX yüklemelerinden sonra tekrar çalıştır
        if (typeof opc_onepage !== 'undefined') {
            if (typeof opc_onepage.option_selected !== 'undefined') {
                var originalFunction = opc_onepage.option_selected;
                opc_onepage.option_selected = function(params) {
                    var result = originalFunction.apply(this, arguments);
                    setTimeout(updateShippingLabels, 500);
                    return result;
                };
            }
            
            // Sayfa yüklendiğinde ve kargo seçenekleri değiştiğinde çalıştır
            document.addEventListener('opc-update-carrier', function() {
                setTimeout(updateShippingLabels, 500);
            });
        }
        
        // Düzenli olarak kontrol et (bazı AJAX yüklemeleri için)
        setInterval(updateShippingLabels, 2000);
    }
});

/**
 * Kargo etiketlerini güncelle
 */
function updateShippingLabels() {
    // Tüm kargo seçeneklerini bul
    var deliveryOptions = document.querySelectorAll('.delivery-option, .delivery_option');
    
    // Debug bilgisi
    console.log('Aiproje Custom Shipping Labels: Found ' + deliveryOptions.length + ' delivery options');
    console.log('Custom labels:', aiproje_custom_labels);
    
    deliveryOptions.forEach(function(option) {
        // Kargo ID'sini bul
        var carrierId = findCarrierId(option);
        if (!carrierId || !aiproje_custom_labels[carrierId]) {
            return;
        }
        
        console.log('Processing carrier ID:', carrierId);
        
        // Tüm olası fiyat gösterim elementlerini bul
        var priceSelectors = [
            '.delivery_option_price', 
            '.carrier_price', 
            '.delivery_option_price span', 
            '.carrier_price span',
            '.delivery-content-top .delivery_option_price',
            '.carrier-content-top .delivery_option_price',
            '.delivery-content-top .delivery_option_price span',
            '.carrier-content-top .delivery_option_price span',
            '.delivery-detail .delivery_option_price span',
            '.delivery-detail .delivery_option_price',
            '.carrier-item-content .delivery_option_price',
            '.carrier-item-content .delivery_option_price span'
        ];
        
        // Tüm seçicileri dene
        priceSelectors.forEach(function(selector) {
            var elements = option.querySelectorAll(selector);
            elements.forEach(function(element) {
                // Eğer "Ücretsiz" yazıyorsa değiştir
                var text = element.textContent.trim();
                if (text === 'Ücretsiz' || 
                    text === 'Free' || 
                    text === '0,00 ₺' ||
                    text === '0.00 €' ||
                    text === '0 ₺' ||
                    text === '0 €') {
                    console.log('Replacing price text:', text, 'with custom HTML');
                    element.innerHTML = aiproje_custom_labels[carrierId].price_html;
                }
            });
        });
        
        // Doğrudan parent elementi de kontrol et
        var priceContainers = option.querySelectorAll('.delivery_option_price, .carrier_price');
        priceContainers.forEach(function(container) {
            if (container.textContent.trim() === 'Ücretsiz' || 
                container.textContent.trim() === 'Free' || 
                container.textContent.trim() === '0,00 ₺' ||
                container.textContent.trim() === '0.00 €' ||
                container.textContent.trim() === '0 ₺' ||
                container.textContent.trim() === '0 €') {
                container.innerHTML = aiproje_custom_labels[carrierId].price_html;
            }
        });
        
        // Açıklama gösterimini bul ve değiştir (varsa)
        if (aiproje_custom_labels[carrierId].description) {
            var delayElements = option.querySelectorAll('.delivery_option_delay, .carrier_delay');
            delayElements.forEach(function(delayElement) {
                delayElement.innerHTML = aiproje_custom_labels[carrierId].description;
            });
        }
    });
}

/**
 * Kargo seçeneğinden kargo ID'sini bul
 */
function findCarrierId(option) {
    // Radio butonundan ID'yi bul
    var radioInput = option.querySelector('input[type="radio"]');
    if (radioInput && radioInput.value) {
        // "1," formatındaki değerden ID'yi çıkar
        var match = radioInput.value.match(/(\d+),/);
        if (match && match[1]) {
            return match[1];
        }
    }
    
    // Alternatif: data-id-carrier özniteliğinden bul
    if (option.dataset.idCarrier) {
        return option.dataset.idCarrier;
    }
    
    // Alternatif: class adından bul (delivery_option_XX formatı)
    var classes = option.className.split(' ');
    for (var i = 0; i < classes.length; i++) {
        var match = classes[i].match(/delivery_option_(\d+)/);
        if (match && match[1]) {
            return match[1];
        }
    }
    
    // Alternatif: ID'den bul (delivery_option_XX formatı)
    var match = option.id.match(/delivery_option_(\d+)/);
    if (match && match[1]) {
        return match[1];
    }
    
    return null;
}