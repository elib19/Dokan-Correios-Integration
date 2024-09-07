<?php
// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Inclui o arquivo de helpers
require_once plugin_dir_path(__FILE__) . 'helper.php';

/**
 * Altera o CEP de origem do WooCommerce Correios para o CEP do vendedor.
 *
 * @param string $cep_origem O CEP de origem original.
 * @param string $metodo_entrega O método de entrega (correios_pac, correios_sedex, etc).
 * @param string $woocommerce_shipping_method_id O ID do método de entrega.
 * @param array $carrinho Os itens do carrinho.
 * @return string O CEP de origem atualizado.
 */
function dci_muda_cep_origem($cep_origem, $metodo_entrega, $woocommerce_shipping_method_id, $carrinho) {
    // Obtém o ID do vendedor principal do pedido
    $seller_id = get_post_meta($carrinho['cart_id'], '_dokan_order_owner', true);

    // Obtém o CEP do vendedor
    $seller_cep = get_seller_cep($seller_id);

    if (!empty($seller_cep)) {
        return $seller_cep;
    }

    return $cep_origem;
}
add_filter('woocommerce_correios_origin_postcode', 'dci_muda_cep_origem', 10, 4);

/**
 * Separa o carrinho do cliente em pacotes por vendedor com base no CEP de origem.
 *
 * @param array $packages Os pacotes originais.
 * @return array Os pacotes separados por vendedor.
 */
function dci_separate_cart_by_vendor($packages) {
    $new_packages = [];

    foreach (WC()->cart->get_cart() as $item) {
        $vendor_id = get_post_field('post_author', $item['product_id']);
        $vendor_cep = get_seller_cep($vendor_id);

        if (!isset($new_packages[$vendor_id])) {
            $new_packages[$vendor_id] = [
                'contents' => [],
                'contents_cost' => 0,
                'applied_coupons' => WC()->cart->get_applied_coupons(),
                'user' => [
                    'ID' => get_current_user_id(),
                ],
                'vendor_id' => $vendor_id,
                'vendor_cep' => $vendor_cep,
            ];
        }

        $new_packages[$vendor_id]['contents'][] = $item;
        $new_packages[$vendor_id]['contents_cost'] += $item['line_total'];
    }

    return array_values($new_packages);
}
add_filter('woocommerce_cart_shipping_packages', 'dci_separate_cart_by_vendor');

/**
 * Permite que vendedores atualizem o status dos pedidos e adicionem códigos de rastreamento.
 */
function dci_vendor_manage_orders($order_id) {
    $order = wc_get_order($order_id);
    $vendor_id = get_current_user_id();

    if (current_user_can('dokan_manage_order') && dokan_is_seller_enabled($vendor_id)) {
        // Marcar pedido como enviado
        if (isset($_POST['mark_as_shipped'])) {
            $order->update_status('shipped', 'Pedido marcado como enviado pelo vendedor.');
        }

        // Adicionar código de rastreamento
        if (isset($_POST['tracking_number'])) {
            update_post_meta($order_id, '_tracking_number', sanitize_text_field($_POST['tracking_number']));
        }
    }
}
add_action('woocommerce_order_details_after_order_table', 'dci_vendor_manage_orders');
