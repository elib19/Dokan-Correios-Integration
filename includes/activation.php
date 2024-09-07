<?php

// Função para ativar o plugin
function dci_activate_plugin() {
    // Verifica se o WooCommerce está ativo
    if (!is_plugin_active('woocommerce/woocommerce.php') && current_user_can('activate_plugins')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'Este plugin requer o WooCommerce para funcionar. Por favor, ative o WooCommerce primeiro.',
            'Plugin Requerido',
            array('back_link' => true)
        );
    }

    // Verifica se o Dokan está ativo
    if (!is_plugin_active('dokan-lite/dokan.php') && current_user_can('activate_plugins')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'Este plugin requer o Dokan para funcionar. Por favor, ative o Dokan primeiro.',
            'Plugin Requerido',
            array('back_link' => true)
        );
    }

    // Cria uma opção de configuração no banco de dados para marcar que o plugin foi ativado
    if (!get_option('dci_plugin_installed')) {
        add_option('dci_plugin_installed', true);
        add_option('dci_plugin_version', '1.0');
        add_option('dci_custom_shipping_log', 'enabled');
    }

    // Cria a tabela de log de fretes no banco de dados
    global $wpdb;
    $table_name = $wpdb->prefix . 'dci_shipping_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vendor_id mediumint(9) NOT NULL,
        shipping_data text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'dci_activate_plugin');

// Função para desativar o plugin
function dci_deactivate_plugin() {
    delete_option('dci_plugin_installed');
    delete_option('dci_plugin_version');
    delete_option('dci_custom_shipping_log');
}
register_deactivation_hook(__FILE__, 'dci_deactivate_plugin');
