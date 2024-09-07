<?php
/**
 * Funções relacionadas à integração com os Correios para o Dokan.
 */

/**
 * Adiciona o submenu de Correios ao menu de envio do Dokan.
 */
function dci_add_correios_submenu() {
    add_submenu_page(
        'dokan', // Slug do menu principal (Dokan)
        'Correios', // Título da página
        'Correios', // Título do submenu
        'manage_woocommerce', // Capacidade necessária
        'dci-correios', // Slug do submenu
        'dci_correios_settings_page' // Função que exibe o conteúdo da página
    );
}
add_action('admin_menu', 'dci_add_correios_submenu');

/**
 * Renderiza o conteúdo da página do submenu de Correios.
 */
function dci_correios_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurações de Correios</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('dci_correios_options_group');
            do_settings_sections('dci-correios');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Registra as configurações da página de Correios.
 */
function dci_register_correios_settings() {
    register_setting('dci_correios_options_group', 'dci_correios_settings');
    
    add_settings_section(
        'dci_correios_main_section',
        'Configurações Principais',
        'dci_correios_section_text',
        'dci-correios'
    );
    
    add_settings_field(
        'dci_correios_shipping_type',
        'Tipo de Frete',
        'dci_correios_shipping_type_callback',
        'dci-correios',
        'dci_correios_main_section'
    );

    add_settings_field(
        'dci_correios_default_shipping_type',
        'Tipo de Frete Padrão para Todos os Vendedores',
        'dci_correios_default_shipping_type_callback',
        'dci-correios',
        'dci_correios_main_section'
    );
}
add_action('admin_init', 'dci_register_correios_settings');

/**
 * Descreve a seção de configurações de Correios.
 */
function dci_correios_section_text() {
    echo '<p>Configure as opções de frete dos Correios para o Dokan aqui.</p>';
}

/**
 * Exibe o campo de seleção para o tipo de frete.
 */
function dci_correios_shipping_type_callback() {
    $options = get_option('dci_correios_settings');
    $shipping_type = isset($options['shipping_type']) ? $options['shipping_type'] : 'pac';

    ?>
    <select name="dci_correios_settings[shipping_type]">
        <option value="pac" <?php selected($shipping_type, 'pac'); ?>>PAC</option>
        <option value="sedex" <?php selected($shipping_type, 'sedex'); ?>>SEDEX</option>
        <!-- Adicione mais opções de frete conforme necessário -->
    </select>
    <?php
}

/**
 * Exibe o campo de seleção para o tipo de frete padrão para todos os vendedores.
 */
function dci_correios_default_shipping_type_callback() {
    $default_shipping_type = get_option('dci_default_shipping_type', 'pac');

    ?>
    <select name="dci_default_shipping_type">
        <option value="pac" <?php selected($default_shipping_type, 'pac'); ?>>PAC</option>
        <option value="sedex" <?php selected($default_shipping_type, 'sedex'); ?>>SEDEX</option>
        <!-- Adicione mais opções de frete conforme necessário -->
    </select>
    <?php
}

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

    if ($order_id && $tracking_code) {
        update_post_meta($order_id, '_tracking_code', $tracking_code);
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }

    wp_die('Código de rastreamento inválido.');
}
add_action('admin_post_dci_update_tracking_code', 'dci_update_tracking_code');

/**
 * Exibe relatórios de ganhos e custos de frete.
 */
function dci_show_shipping_cost_report() {
    if (current_user_can('manage_woocommerce') || current_user_can('dokan_manage_seller_dashboard')) {
        global $wpdb;

        $query = "
            SELECT
                p.ID AS order_id,
                pm.meta_value AS shipping_cost,
                (SELECT SUM(meta_value) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_total' AND post_id = p.ID) AS total_order_value
            FROM
                {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
            WHERE
                p.post_type = 'shop_order'
                AND pm.meta_key = '_shipping_cost'
                AND p.post_status IN ('wc-completed', 'wc-processing')
        ";

        $results = $wpdb->get_results($query);

        echo '<h2>Relatório de Custos de Frete</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID do Pedido</th><th>Custo de Frete</th><th>Valor Total do Pedido</th></tr></thead>';
        echo '<tbody>';

        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->order_id) . '</td>';
            echo '<td>' . esc_html($row->shipping_cost) . '</td>';
            echo '<td>' . esc_html($row->total_order_value) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}
