<?php
/*
Plugin Name: Dokan Correios Integration
Description: Integra o plugin Correios para WooCommerce com o Dokan, calculando o frete com base no endereço de cada vendedor.
Version: 1.0
Author: Eli Silva
License: GPL2
*/

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Inclui o arquivo de ativação/desativação
require_once plugin_dir_path(__FILE__) . 'includes/activation.php';

// Função principal que integra o Dokan com o plugin Correios.
function dci_custom_dokan_vendor_shipping($rates, $package) {
    // Verifica se o Dokan e WooCommerce estão ativos.
    if (!class_exists('WeDevs_Dokan') || !class_exists('WooCommerce')) {
        return $rates;
    }

    // Verifica se há produtos no pacote.
    if (!isset($package['contents']) || empty($package['contents'])) {
        return $rates;
    }

    // Obtém o ID do vendedor do primeiro produto do pacote.
    $product = reset($package['contents']);
    $vendor_id = get_post_field('post_author', $product['product_id']);

    // Obtém o endereço do vendedor.
    $vendor = dokan()->vendor->get($vendor_id);
    $origin_address = $vendor->get_address();

    // Verifica se o endereço do vendedor é válido.
    if (empty($origin_address['postcode'])) {
        return $rates; // Endereço do vendedor não configurado corretamente.
    }

    // Ajusta o pacote para usar o endereço de origem do vendedor.
    $package['destination']['postcode'] = $origin_address['postcode'];
    $package['destination']['city'] = $origin_address['city'];
    $package['destination']['state'] = $origin_address['state'];

    // Recalcula o frete com base no endereço do vendedor.
    $new_rates = WC()->shipping()->calculate_shipping_for_package($package);

    // Substitui as taxas antigas pelas novas.
    return $new_rates['rates'];
}
add_filter('woocommerce_package_rates', 'dci_custom_dokan_vendor_shipping', 10, 2);
