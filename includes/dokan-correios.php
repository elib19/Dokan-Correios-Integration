<?php
/**
 * Funções relacionadas à integração com os Correios para o Dokan.
 */

/**
 * Obtém os métodos de frete dos Correios configurados pelo marketplace.
 *
 * @return array Lista de métodos de frete dos Correios.
 */
function dci_get_correios_shipping_methods() {
    $methods = [];

    // Obtém as instâncias de métodos de envio do WooCommerce
    $shipping_methods = WC()->shipping->get_shipping_methods();

    foreach ($shipping_methods as $method_id => $method) {
        if (strpos($method_id, 'correios_') === 0) {
            $methods[$method_id] = $method->get_title();
        }
    }

    return $methods;
}

/**
 * Obtém o tipo de frete configurado (SEDEX, PAC, etc.) para todos os vendedores ou um específico.
 *
 * @param bool $all_vendors Se verdadeiro, retorna o tipo de frete para todos os vendedores. Caso contrário, retorna o tipo para um vendedor específico.
 * @param int|null $vendor_id O ID do vendedor específico (ou null para todos).
 * @return string Tipo de frete configurado.
 */
function dci_get_shipping_type($all_vendors = false, $vendor_id = null) {
    $shipping_type = 'pac'; // Valor padrão

    if ($all_vendors) {
        // Lógica para obter o tipo de frete configurado para todos os vendedores
        $shipping_type = get_option('dci_default_shipping_type', 'pac');
    } else {
        // Lógica para obter o tipo de frete configurado para um vendedor específico
        $vendor_shipping_type = get_user_meta($vendor_id, 'dci_vendor_shipping_type', true);
        if ($vendor_shipping_type) {
            $shipping_type = $vendor_shipping_type;
        }
    }

    return $shipping_type;
}

/**
 * Atualiza o tipo de frete configurado para um vendedor específico ou para todos os vendedores.
 *
 * @param string $shipping_type Tipo de frete (SEDEX, PAC, etc.).
 * @param bool $all_vendors Se verdadeiro, aplica a todos os vendedores. Caso contrário, aplica ao vendedor específico.
 * @param int|null $vendor_id O ID do vendedor específico (ou null para todos).
 */
function dci_update_shipping_type($shipping_type, $all_vendors = false, $vendor_id = null) {
    if ($all_vendors) {
        update_option('dci_default_shipping_type', $shipping_type);
    } else {
        update_user_meta($vendor_id, 'dci_vendor_shipping_type', $shipping_type);
    }
}

/**
 * Divide o carrinho de compras por vendedor.
 *
 * @param array $cart Cartão do cliente.
 * @return array Carrinhos divididos por vendedor.
 */
function dci_split_cart_by_vendor($cart) {
    $split_cart = [];

    foreach ($cart['cart_contents'] as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $vendor_id = get_post_meta($product_id, '_dokan_vendor_id', true);

        if (!$vendor_id) {
            $vendor_id = 0; // Default para vendedores que não têm ID
        }

        if (!isset($split_cart[$vendor_id])) {
            $split_cart[$vendor_id] = [];
        }

        $split_cart[$vendor_id][$cart_item_key] = $cart_item;
    }

    return $split_cart;
}

/**
 * Atualiza o custo de frete no pedido.
 *
 * @param int $order_id ID do pedido.
 * @param float $shipping_cost Custo de frete.
 */
function dci_update_order_shipping_cost($order_id, $shipping_cost) {
    update_post_meta($order_id, '_shipping_cost', $shipping_cost);
}

/**
 * Adiciona a funcionalidade de rastreamento de pedidos nos painéis dos vendedores.
 */
function dci_add_tracking_to_order_panel() {
    if (!current_user_can('dokan_manage_seller_dashboard')) {
        return;
    }

    echo '<h2>Rastreamento de Pedidos</h2>';

    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['completed', 'processing'],
        'meta_key' => '_dokan_order_owner',
        'meta_value' => get_current_user_id(),
    ]);

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Pedido</th><th>Código de Rastreamento</th><th>Atualizar</th></tr></thead>';
    echo '<tbody>';

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $tracking_code = get_post_meta($order_id, '_tracking_code', true);

        echo '<tr>';
        echo '<td>#' . esc_html($order_id) . '</td>';
        echo '<td>' . esc_html($tracking_code) . '</td>';
        echo '<td><a href="' . esc_url(add_query_arg(['order_id' => $order_id], admin_url('admin-post.php?action=dci_update_tracking_code'))) . '">Atualizar Código de Rastreamento</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
add_action('dokan_dashboard_content', 'dci_add_tracking_to_order_panel');

/**
 * Manipula a atualização do código de rastreamento de pedidos.
 */
function dci_update_tracking_code() {
    if (!isset($_GET['order_id']) || !current_user_can('dokan_manage_seller_dashboard')) {
        wp_die('Acesso não autorizado.');
    }

    $order_id = intval($_GET['order_id']);
    $tracking_code = sanitize_text_field($_POST['tracking_code']);

    update_post_meta($order_id, '_tracking_code', $tracking_code);

    wp_redirect(add_query_arg(['updated' => 'true'], dokan_get_dashboard_page_url()));
    exit;
}
add_action('admin_post_dci_update_tracking_code', 'dci_update_tracking_code');
