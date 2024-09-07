<?php

/**
 * Altera o CEP de origem do WooCommerce Correios para o CEP do vendedor.
 *
 * @param string $cep_origem O CEP de origem original.
 * @param string $metodo_entrega O método de entrega (correios_pac, correios_sedex, etc).
 * @param string $woocommerce_shipping_method_id O ID do método de entrega.
 * @param array $carrinho Os itens do carrinho.
 * @return string O CEP de origem atualizado.
 */
function dci_muda_cep_origem($cep_origem, $metodo_entrega, $woocommerce_shipping_method_id, $carrinho) {
    // Obtém o ID do vendedor principal do pedido
    $seller_id = get_post_meta($carrinho['cart_id'], '_dokan_order_owner', true);

    // Obtém o CEP do vendedor
    $seller_cep = get_seller_cep($seller_id);

    if (!empty($seller_cep)) {
        return $seller_cep;
    }

    return $cep_origem;
}
add_filter('woocommerce_correios_origin_postcode', 'dci_muda_cep_origem', 10, 4);
