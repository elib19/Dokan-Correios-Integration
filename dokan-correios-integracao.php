<?php
/*
Plugin Name: Integração Dokan Correios
Description: Integra o Dokan Pro com o WooCommerce Correios, utilizando o CEP do vendedor como origem.
Version: 1.0
Author: Eli Silva
Author URI: brasilnarede.online
*/

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Inclui os arquivos necessários
require_once plugin_dir_path(__FILE__) . 'includes/activation.php';
require_once plugin_dir_path(__FILE__) . 'includes/dokan-correios.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper.php';

// Funções de ativação e desativação
register_activation_hook(__FILE__, 'dci_activate_plugin');
register_deactivation_hook(__FILE__, 'dci_deactivate_plugin');
