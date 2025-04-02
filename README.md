# Recipient Paid Shipping Module for PrestaShop 8.1.7 (aiproje_custom_shipping_labels)

## Overview
The Recipient Paid Shipping module allows shop owners to customize the display of shipping fees and descriptions for each carrier option in their PrestaShop store. This is particularly useful for cash-on-delivery or recipient-paid shipping options that might show a zero price in the cart but actually have a fee collected at delivery.

## Features
- Customize shipping fee display with custom HTML content for each carrier
- Support for multiple languages with different content per language
- Optional custom shipping descriptions
- Easy enable/disable option
- Admin panel configuration for all settings
- Compatibility with PrestaShop 8.0 and above

## Installation
1. Download the module package
2. Upload the `aiproje_custom_shipping_labels` folder to your PrestaShop's `/modules` directory
3. Go to "Modules > Module Manager" in your PrestaShop admin panel
4. Find "Recipient Paid Shipping" and click "Install"

## Configuration
1. After installation, click on "Configure" to access the module settings
2. Enable the module by toggling the switch
3. Select the carrier options you want to customize
4. For each carrier and language, add the custom HTML content to display in place of the shipping fee
5. Optionally, add custom shipping descriptions
6. Save your settings

## Usage Examples

### Scenario: Cash on Delivery with Fee
When offering a cash-on-delivery option that appears free in the cart but requires payment at delivery:

1. Set up your carrier with a €0 shipping fee
2. In the module configuration, select this carrier
3. Add custom HTML content like:
```html
<strong style="color: red;">€15 payment on delivery</strong>
```
4. Add a description like: "The delivery person will collect €15 at the time of delivery"

### Scenario: Multiple Languages
If your store supports multiple languages (e.g., English and Turkish):

1. Add appropriate content for each language
2. English: "Payment on delivery: €15"
3. Turkish: "Kapıda ödeme: 15€"

## Technical Details
- Module stores all configuration in the database with the table prefix `aiproje_custom_shipping_labels`
- Compatible with all PrestaShop themes
- Uses JavaScript to modify carrier display on the front-end
- Responsive design works on all devices

## Troubleshooting
- If customized content isn't displaying, ensure the module is activated
- If changes aren't visible after saving, clear your PrestaShop cache
- For multilingual stores, ensure you've configured all languages

## Support
For support requests, please contact: developers@aiproje.com

## License
This module is distributed under the Academic Free License (AFL 3.0).

## Developer Information
Developed by: AIPROJE

© 2023-2025 AIPROJE - All rights reserved