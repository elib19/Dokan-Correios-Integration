<?php

// Evita acesso direto ao arquivo
if (!defined('ABSPATH') || !defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remover opções do plugin do banco de dados
delete_option('dci_plugin_installed');
delete_option('dci_plugin_version');
delete_option('dci_custom_shipping_log');

// Opcional: Remover tabelas criadas no banco de dados
global $wpdb;
$table_name = $wpdb->prefix . 'dci_shipping_log';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
