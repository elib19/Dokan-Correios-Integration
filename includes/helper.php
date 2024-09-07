<?php

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
