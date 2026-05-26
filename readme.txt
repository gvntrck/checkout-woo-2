=== Checkout GVNTRCK ===
Contributors: giovaniturek
Tags: woocommerce, checkout, conversion, one-page, shortcode
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 1.1.5
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.4
License: GPLv2 or later

Checkout personalizado de alta conversão para WooCommerce. Renderiza um card de checkout via shortcode [checkout-gvntrck] ou [checkout-gvntrck-geral], totalmente compatível com gateways de pagamento.

== Description ==

Checkout GVNTRCK substitui a tela de checkout padrão do WooCommerce por um card de alta conversão renderizado em qualquer página através de shortcode. Use `[checkout-gvntrck]` para uma página de oferta que adiciona um produto automaticamente, ou `[checkout-gvntrck-geral]` para finalizar o carrinho atual de uma loja tradicional.

= Características =

* Shortcode de produto único: `[checkout-gvntrck]`
* Shortcode de produto específico: `[checkout-gvntrck product_id="123"]`
* Shortcode de checkout geral do carrinho: `[checkout-gvntrck-geral]`
* Layout responsivo, focado em conversão (Identificação → Pagamento → Total → Botão → Selos)
* Abas Cartão / PIX / Boleto mapeadas para qualquer gateway de pagamento ativo do WooCommerce
* 100% compatível com gateways como Stripe, MercadoPago, PagSeguro, Asaas, Yampi, Pagarme, etc., pois usa o pipeline nativo `WC_AJAX::checkout`
* Tela de admin com 3 abas: Geral, Campos (criar/editar/remover), Layout & Textos
* Página de obrigado configurável
* Checkout geral com lista de produtos, controle de quantidade, remover item e cupom opcional
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
4. Insira o shortcode `[checkout-gvntrck]`, `[checkout-gvntrck product_id="123"]` ou `[checkout-gvntrck-geral]` na página desejada.

== Changelog ==

= 1.1.5 =
* Injeta divs ocultas `.order-total` e `.product-total` contendo a tag `<bdi>` dentro da seção `#payment` para que o script do PagSeguro consiga ler o total atualizado do carrinho via AJAX.
* Restaura e aprimora os patches de compatibilidade do jQuery no front-end (`$.fn.html()`, `$.fn.val()`, `$.fn.attr()` e `$.fn.data()`) para retornar uma string vazia caso o elemento consultado não exista no DOM, prevenindo erros de JavaScript.

= 1.1.4 =
* Adiciona a classe `.order-total` à linha de total do carrinho para que o script do PagSeguro (`creditcard.js`) consiga extrair o valor da compra no checkout sem quebrar o JavaScript com erro de `undefined`.
* Remove os patches experimentais de jQuery no front-end para evitar efeitos colaterais.

= 1.1.3 =
* Adiciona patches de compatibilidade ao jQuery no front-end (`$.fn.val()`, `$.fn.attr()` e `$.fn.data()`) para que, caso algum gateway tente ler elementos do DOM que ainda não foram inicializados ou carregados, retorne uma string vazia ao invés de `undefined`, prevenindo erros de JavaScript fatais.

= 1.1.2 =
* Injeta inputs hidden para todos os campos padrão de faturamento do WooCommerce que não estiverem ativos no formulário. Isso evita erros fatais de JavaScript de 'undefined' (como '.replace()' no PagSeguro) em scripts de gateways que tentam ler elementos inexistentes no DOM.

= 1.1.1 =
* Força o WooCommerce a reconhecer a página do shortcode como checkout (`woocommerce_is_checkout` filtrado como `true`) para enfileirar scripts de gateways de terceiros.
* Altera os IDs dos campos de identificação para usar as chaves padrão do WooCommerce (ex: `billing_cpf` em vez de `cgv-field-cpf`) para garantir que os scripts de tokenização dos gateways funcionem.

= 1.1.0 =
* Corrige o redirecionamento da página de obrigado (order-received), exibindo a página de agradecimento padrão do WooCommerce (com QR Code do Pix, etc) em vez de uma mensagem de carrinho vazio.

= 1.0.5 =
* Ajusta espaçamento dos avisos do WooCommerce e remove seletor nativo do campo de quantidade.

= 1.0.4 =
* Impede que os fragments do WooCommerce sobrescrevam o resumo customizado do checkout geral.

= 1.0.3 =
* Adiciona checkout geral do carrinho com `[checkout-gvntrck-geral]`.
* Adiciona suporte a `product_id` no shortcode `[checkout-gvntrck]`.

= 1.0.0 =
* Lançamento inicial.
