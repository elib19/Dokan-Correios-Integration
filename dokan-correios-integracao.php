<?php
/*
Plugin Name: Integração Dokan Correios
Description: Integra o Dokan Pro com o WooCommerce Correios, utilizando o CEP do vendedor como origem.
Version: 1.0
Author: Eli Silva
Author URI: https://brasilnarede.online
*/

// Inclui o arquivo de helpers
require_once plugin_dir_path(__FILE__) . 'includes/helper.php';

// Altera o CEP de origem do WooCommerce Correios para o CEP do vendedor
function dci_muda_cep_origem($cep_origem, $metodo_entrega, $woocommerce_shipping_method_id, $carrinho) {
    $seller_id = get_post_meta($carrinho['cart_id'], '_dokan_order_owner', true);
    $seller_cep = get_seller_cep($seller_id);

    if (!empty($seller_cep)) {
        return $seller_cep;
    }

    return $cep_origem;
}
add_filter('woocommerce_correios_origin_postcode', 'dci_muda_cep_origem', 10, 4);

// Exibe o relatório de custos de frete no painel do vendedor
function dci_show_shipping_cost_report() {
    if (!current_user_can('manage_woocommerce') && !current_user_can('dokan_manage_seller_dashboard')) {
        return;
    }

    try {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_woocommerce');

        $args = [
            'limit' => -1,
            'status' => ['completed', 'processing', 'shipped'],
        ];

        if (!$is_admin) {
            $args['meta_key'] = '_dokan_order_owner';
            $args['meta_value'] = $current_user_id;
        }

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            echo '<p>Nenhum pedido encontrado.</p>';
            return;
        }

        $total_shipping_charged = 0;
        $total_shipping_cost = 0;

        echo '<h2>Relatório de Custos de Frete</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Pedido</th><th>Data</th><th>Cliente</th><th>Frete Cobrado</th><th>Custo de Frete</th><th>Lucro de Frete</th></tr></thead>';
        echo '<tbody>';

        foreach ($orders as $order) {
            if (!$order instanceof WC_Order) {
                continue;
            }

            $order_id = $order->get_id();
            $order_date = $order->get_date_created() ? $order->get_date_created()->format('d/m/Y') : 'N/A';
            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $shipping_charged = $order->get_shipping_total();
            $shipping_cost = (float) get_post_meta($order_id, '_shipping_cost', true) ?: 0;
            $shipping_profit = $shipping_charged - $shipping_cost;

            $total_shipping_charged += $shipping_charged;
            $total_shipping_cost += $shipping_cost;

            echo '<tr>';
            echo '<td>#' . esc_html($order_id) . '</td>';
            echo '<td>' . esc_html($order_date) . '</td>';
            echo '<td>' . esc_html($customer_name) . '</td>';
            echo '<td>' . wc_price($shipping_charged) . '</td>';
            echo '<td>' . wc_price($shipping_cost) . '</td>';
            echo '<td>' . wc_price($shipping_profit) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '<tfoot><tr><th colspan="3">Totais</th><th>' . wc_price($total_shipping_charged) . '</th><th>' . wc_price($total_shipping_cost) . '</th><th>' . wc_price($total_shipping_charged - $total_shipping_cost) . '</th></tr></tfoot>';
        echo '</table>';
    } catch (Exception $e) {
        error_log('Erro na função dci_show_shipping_cost_report: ' . $e->getMessage());
        echo '<p>Ocorreu um erro ao gerar o relatório. Consulte o log de erros para mais detalhes.</p>';
    }
}
add_action('dokan_dashboard_content', 'dci_show_shipping_cost_report');
