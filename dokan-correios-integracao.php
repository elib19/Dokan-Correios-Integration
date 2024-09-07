<?php
/*
Plugin Name: Integração Dokan Correios
Description: Integra o Dokan Pro com o WooCommerce Correios, utilizando o CEP do vendedor como origem.
Version: 1.1
Author: Eli Silva
Author URI: https://brasilnarede.online
*/

if (!defined('ABSPATH')) {
    exit; // Evita acesso direto
}

// Inclui arquivos necessários
require_once plugin_dir_path(__FILE__) . 'includes/activation.php';
require_once plugin_dir_path(__FILE__) . 'includes/dokan-correios.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/uninstall.php';
