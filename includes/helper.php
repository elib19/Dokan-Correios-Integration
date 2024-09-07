<?php
// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtém o CEP do vendedor com base no ID do vendedor.
 *
 * @param int $seller_id O ID do vendedor.
 * @return string|null O CEP do vendedor, ou null se não encontrado.
 */
function get_seller_cep($seller_id) {
    $address = get_user_meta($seller_id, 'dokan_profile_settings', true);
    return $address['address']['zip'] ?? null;
}

/**
 * Adiciona campo de CEP nas configurações do perfil do vendedor.
 *
 * @param array $settings As configurações do perfil do vendedor.
 * @return array As configurações do perfil do vendedor com o campo de CEP adicionado.
 */
function dci_add_cep_field_to_settings($settings) {
    $settings['address']['zip'] = isset($settings['address']['zip']) ? $settings['address']['zip'] : '';
    return $settings;
}
add_filter('dokan_seller_profile_settings', 'dci_add_cep_field_to_settings');

/**
 * Salva o CEP do vendedor ao salvar as configurações da loja.
 *
 * @param int $user_id O ID do vendedor.
 * @param array $settings As configurações do perfil do vendedor.
 */
function dci_save_cep_field($user_id, $settings) {
    if (isset($settings['address']['zip'])) {
        update_user_meta($user_id, 'dokan_profile_settings', $settings);
    }
}
add_action('dokan_store_profile_saved', 'dci_save_cep_field', 10, 2);

/**
 * Adiciona opções de frete aos vendedores.
 */
function dci_add_shipping_methods_settings($settings) {
    $settings['shipping_methods'] = isset($settings['shipping_methods']) ? $settings['shipping_methods'] : ['SEDEX', 'PAC'];
    return $settings;
}
add_filter('dokan_seller_profile_settings', 'dci_add_shipping_methods_settings');

/**
 * Salva os métodos de frete configurados pelo vendedor.
 */
function dci_save_shipping_methods($user_id, $settings) {
    if (isset($settings['shipping_methods'])) {
        update_user_meta($user_id, 'dokan_shipping_methods', $settings['shipping_methods']);
    }
}
add_action('dokan_store_profile_saved', 'dci_save_shipping_methods', 10, 2);

/**
 * Exibe relatório de ganhos e custos de frete para vendedores e administradores.
 */
/**
 * Exibe relatório de ganhos e custos de frete para vendedores e administradores.
 */
function dci_show_shipping_cost_report() {
    // Verifica se o usuário atual tem permissões adequadas
    if (!current_user_can('manage_woocommerce') && !current_user_can('dokan_manage_seller_dashboard')) {
        return;
    }

    // Obtém o ID do usuário atual
    $current_user_id = get_current_user_id();

    // Determina se o usuário é administrador
    $is_admin = current_user_can('manage_woocommerce');

    // Define os argumentos para consulta de pedidos
    $args = [
        'limit' => -1,
        'status' => ['completed', 'processing', 'shipped'], // Adicione outros status se necessário
    ];

    // Se o usuário não for administrador, filtra apenas os pedidos do vendedor
    if (!$is_admin) {
        $args['meta_key'] = '_dokan_order_owner';
        $args['meta_value'] = $current_user_id;
    }

    // Obtém os pedidos com base nos argumentos
    $orders = wc_get_orders($args);

    // Inicializa variáveis para cálculo
    $total_shipping_charged = 0;
    $total_shipping_cost = 0;

    // Monta a tabela de relatório
    echo '<h2>Relatório de Custos de Frete</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Pedido</th>';
    echo '<th>Data</th>';
    echo '<th>Cliente</th>';
    echo '<th>Frete Cobrado</th>';
    echo '<th>Custo de Frete</th>';
    echo '<th>Lucro de Frete</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    // Itera sobre cada pedido para calcular ganhos e custos
    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $order_date = $order->get_date_created()->format('d/m/Y');
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        // Valor de frete cobrado do cliente
        $shipping_charged = $order->get_shipping_total();

        // Custo de frete registrado pelo vendedor (pode ser adicionado via meta personalizado, ex.: '_shipping_cost')
        $shipping_cost = (float) get_post_meta($order_id, '_shipping_cost', true);

        // Calcula o lucro de frete
        $shipping_profit = $shipping_charged - $shipping_cost;

        // Adiciona aos totais
        $total_shipping_charged += $shipping_charged;
        $total_shipping_cost += $shipping_cost;

        // Exibe os detalhes na tabela
        echo '<tr>';
        echo '<td>#' . $order_id . '</td>';
        echo '<td>' . esc_html($order_date) . '</td>';
        echo '<td>' . esc_html($customer_name) . '</td>';
        echo '<td>' . wc_price($shipping_charged) . '</td>';
        echo '<td>' . wc_price($shipping_cost) . '</td>';
        echo '<td>' . wc_price($shipping_profit) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '<tfoot>';
    echo '<tr>';
    echo '<th colspan="3">Totais</th>';
    echo '<th>' . wc_price($total_shipping_charged) . '</th>';
    echo '<th>' . wc_price($total_shipping_cost) . '</th>';
    echo '<th>' . wc_price($total_shipping_charged - $total_shipping_cost) . '</th>';
    echo '</tr>';
    echo '</tfoot>';
    echo '</table>';
}
add_action('dokan_dashboard_content', 'dci_show_shipping_cost_report');
