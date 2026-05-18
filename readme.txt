=== Checkout GVNTRCK ===
Contributors: giovaniturek
Tags: woocommerce, checkout, conversion, one-page, shortcode
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.4
License: GPLv2 or later

Checkout personalizado de alta conversão para WooCommerce. Renderiza um card de checkout via shortcode [checkout-gvntrck], totalmente compatível com gateways de pagamento.

== Description ==

Checkout GVNTRCK substitui a tela de checkout padrão do WooCommerce por um card de alta conversão renderizado em qualquer página através do shortcode `[checkout-gvntrck]`. O restante da página de vendas pode ser construído livremente no Elementor (ou qualquer outro builder).

= Características =

* Shortcode único: `[checkout-gvntrck]`
* Layout responsivo, focado em conversão (Identificação → Pagamento → Total → Botão → Selos)
* Abas Cartão / PIX / Boleto mapeadas para qualquer gateway de pagamento ativo do WooCommerce
* 100% compatível com gateways como Stripe, MercadoPago, PagSeguro, Asaas, Yampi, Pagarme, etc., pois usa o pipeline nativo `WC_AJAX::checkout`
* Tela de admin com 3 abas: Geral, Campos (criar/editar/remover), Layout & Textos
* Página de obrigado configurável
* HPOS-compatible

== Installation ==

1. Faça upload da pasta `checkout-gvntrck` para `/wp-content/plugins/`.
2. Ative o plugin no menu Plugins do WordPress.
3. Acesse "Checkout GVNTRCK" no menu lateral e configure:
   * Produto do checkout
   * URL da página de obrigado (opcional)
   * Mapeamento de gateways para cada aba
   * Campos de identificação
   * Layout, cores e textos
4. Insira o shortcode `[checkout-gvntrck]` na página desejada.

== Changelog ==

= 1.0.0 =
* Lançamento inicial.
