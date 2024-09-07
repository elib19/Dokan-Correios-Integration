<?php
// Evita acesso direto ao arquivo
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove as opções do banco de dados
delete_option('dci_plugin_installed');
delete_option('dci_plugin_version');
delete_option('dci_custom_shipping_log');

// Exclui a tabela de logs de frete (se necessário)
global $wpdb;
$table_name = $wpdb->prefix . 'dci_shipping_log';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
