<?php
/**
 * Funções auxiliares para o plugin de integração Dokan Correios.
 */

// Obtém o CEP do vendedor com base no ID do vendedor
function get_seller_cep($seller_id) {
    $address = get_user_meta($seller_id, 'dokan_profile_settings', true);
    return $address['address']['zip'] ?? null;
}
