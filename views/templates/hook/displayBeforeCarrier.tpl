{*
* 2023-2025 AIPROJE
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
*
* @author    AIPROJE <info@aiproje.com>
* @copyright 2023-2025 AIPROJE
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{* Bu şablon onepagecheckoutps modülü için özel bir görünüm sağlar *}
{if isset($customLabel) && ($customLabel.custom_price_html || $customLabel.custom_description)}
<div class="aiproje-custom-shipping-info onepagecheckout-custom-info" data-carrier-id="{if isset($carrier.id_carrier)}{$carrier.id_carrier|intval}{/if}">
    {if $customLabel.custom_price_html}
        <div class="aiproje-custom-price">{$customLabel.custom_price_html nofilter}</div>
    {/if}
    
    {if $customLabel.custom_description}
        <div class="aiproje-custom-description">{$customLabel.custom_description nofilter}</div>
    {/if}
</div>
{/if}