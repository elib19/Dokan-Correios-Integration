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
    // Obtém as configurações de perfil do vendedor a partir dos meta dados do usuário
    $address = get_user_meta($seller_id, 'dokan_profile_settings', true);

    // Verifica se o endereço e o CEP estão configurados corretamente
    return $address['address']['zip'] ?? null;
}
