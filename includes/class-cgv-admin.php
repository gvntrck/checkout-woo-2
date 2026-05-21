<?php
/**
 * Admin page.
 *
 * @package CheckoutGVNTRCK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGV_Admin {

    const SLUG       = 'checkout-gvntrck';
    const CAPABILITY = 'manage_woocommerce';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_post_cgv_save_settings', [ __CLASS__, 'save_settings' ] );
        add_action( 'admin_post_cgv_save_fields', [ __CLASS__, 'save_fields' ] );
        add_action( 'admin_post_cgv_save_layout', [ __CLASS__, 'save_layout' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_filter( 'plugin_action_links_' . CGV_BASENAME, [ __CLASS__, 'plugin_action_links' ] );
    }

    public static function plugin_action_links( $links ) {
        $url = admin_url( 'admin.php?page=' . self::SLUG );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Configurações', 'checkout-gvntrck' ) . '</a>' );
        return $links;
    }

    public static function register_menu() {
        add_menu_page(
            __( 'Checkout GVNTRCK', 'checkout-gvntrck' ),
            __( 'Checkout GVNTRCK', 'checkout-gvntrck' ),
            self::CAPABILITY,
            self::SLUG,
            [ __CLASS__, 'render_page' ],
            'dashicons-cart',
            58
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( false === strpos( (string) $hook, self::SLUG ) ) {
            return;
        }
        wp_enqueue_style( 'cgv-admin', CGV_URL . 'assets/css/admin.css', [], CGV_VERSION );
        wp_enqueue_script( 'cgv-admin', CGV_URL . 'assets/js/admin.js', [ 'jquery' ], CGV_VERSION, true );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    public static function render_page() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return;
        }
        $tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
        $base_url = admin_url( 'admin.php?page=' . self::SLUG );

        $tabs = [
            'general' => __( 'Geral', 'checkout-gvntrck' ),
            'fields'  => __( 'Campos', 'checkout-gvntrck' ),
            'layout'  => __( 'Layout & Textos', 'checkout-gvntrck' ),
        ];
        if ( ! isset( $tabs[ $tab ] ) ) {
            $tab = 'general';
        }
        ?>
        <div class="wrap cgv-admin-wrap">
            <h1><?php esc_html_e( 'Checkout GVNTRCK', 'checkout-gvntrck' ); ?></h1>

            <div class="cgv-shortcode-hint">
                <strong><?php esc_html_e( 'Shortcodes:', 'checkout-gvntrck' ); ?></strong>
                <code>[checkout-gvntrck]</code>
                <code>[checkout-gvntrck product_id="123"]</code>
                <code>[checkout-gvntrck-geral]</code>
            </div>

            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tabs as $key => $label ) : ?>
                    <a
                        class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
                        href="<?php echo esc_url( add_query_arg( 'tab', $key, $base_url ) ); ?>"
                    ><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </h2>

            <?php settings_errors( 'cgv_messages' ); ?>

            <?php
            switch ( $tab ) {
                case 'fields':
                    self::render_fields_tab();
                    break;
                case 'layout':
                    self::render_layout_tab();
                    break;
                default:
                    self::render_general_tab();
            }
            ?>
        </div>
        <?php
    }

    /* -----------------------------------------------------------------
     * Tab: General
     * --------------------------------------------------------------- */
    protected static function render_general_tab() {
        $s = CGV_Plugin::get_settings();
        $gateways = WC()->payment_gateways()->payment_gateways();
        $gateway_options = [ '' => __( '— Selecione um gateway —', 'checkout-gvntrck' ) ];
        foreach ( $gateways as $gw ) {
            $enabled_label = ( 'yes' === $gw->enabled ) ? '' : ' (' . __( 'desativado', 'checkout-gvntrck' ) . ')';
            $gateway_options[ $gw->id ] = $gw->get_method_title() . $enabled_label;
        }

        $products = wc_get_products( [
            'status'  => 'publish',
            'limit'   => 200,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        ] );
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cgv-form-admin">
            <?php wp_nonce_field( 'cgv_save_settings' ); ?>
            <input type="hidden" name="action" value="cgv_save_settings" />

            <h2><?php esc_html_e( 'Produto e Redirecionamento', 'checkout-gvntrck' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="product_id"><?php esc_html_e( 'Produto do Checkout', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <select name="product_id" id="product_id">
                            <option value="0"><?php esc_html_e( '— Nenhum —', 'checkout-gvntrck' ); ?></option>
                            <?php foreach ( $products as $p ) : ?>
                                <option value="<?php echo esc_attr( $p->get_id() ); ?>" <?php selected( (int) $s['product_id'], $p->get_id() ); ?>>
                                    #<?php echo esc_html( $p->get_id() ); ?> — <?php echo esc_html( $p->get_name() ); ?>
                                    (<?php echo wp_kses_post( wc_price( (float) $p->get_price() ) ); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Produto que será adicionado ao carrinho automaticamente quando a página do shortcode for acessada.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="thank_you_url"><?php esc_html_e( 'URL da página de obrigado', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="url" id="thank_you_url" name="thank_you_url" class="regular-text"
                               value="<?php echo esc_attr( $s['thank_you_url'] ); ?>"
                               placeholder="https://seusite.com/obrigado" />
                        <p class="description"><?php esc_html_e( 'Após o pagamento bem-sucedido o cliente será redirecionado para esta URL. Deixe em branco para usar a página de obrigado padrão do WooCommerce. As variáveis order_id e key são adicionadas automaticamente.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="empty_cart_home_url"><?php esc_html_e( 'URL do botão de carrinho vazio', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="url" id="empty_cart_home_url" name="empty_cart_home_url" class="regular-text"
                               value="<?php echo esc_attr( $s['empty_cart_home_url'] ); ?>"
                               placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
                        <p class="description"><?php esc_html_e( 'Usada no shortcode geral quando o carrinho estiver vazio.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Cupom no checkout geral', 'checkout-gvntrck' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="show_coupon_field" value="1" <?php checked( ! empty( $s['show_coupon_field'] ) ); ?> />
                            <?php esc_html_e( 'Exibir campo de cupom em [checkout-gvntrck-geral]', 'checkout-gvntrck' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Abas de Pagamento', 'checkout-gvntrck' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Mapeie cada aba do checkout (Cartão / PIX / Boleto) para um gateway de pagamento ativo do WooCommerce. Abas sem gateway válido são ocultadas automaticamente.', 'checkout-gvntrck' ); ?></p>
            <table class="form-table">
                <?php
                foreach ( [ 'card' => 'Cartão', 'pix' => 'PIX', 'boleto' => 'Boleto' ] as $key => $default_label ) :
                    $enabled_key = 'tab_' . $key . '_enabled';
                    $label_key   = 'tab_' . $key . '_label';
                    $gw_key      = 'gateway_' . $key;
                    ?>
                    <tr>
                        <th><?php echo esc_html( $default_label ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $enabled_key ); ?>" value="1" <?php checked( ! empty( $s[ $enabled_key ] ) ); ?> />
                                <?php esc_html_e( 'Exibir esta aba', 'checkout-gvntrck' ); ?>
                            </label>
                            <br /><br />
                            <label>
                                <?php esc_html_e( 'Rótulo:', 'checkout-gvntrck' ); ?>
                                <input type="text" name="<?php echo esc_attr( $label_key ); ?>" value="<?php echo esc_attr( $s[ $label_key ] ); ?>" />
                            </label>
                            &nbsp;&nbsp;
                            <label>
                                <?php esc_html_e( 'Gateway:', 'checkout-gvntrck' ); ?>
                                <select name="<?php echo esc_attr( $gw_key ); ?>">
                                    <?php foreach ( $gateway_options as $gw_id => $gw_label ) : ?>
                                        <option value="<?php echo esc_attr( $gw_id ); ?>" <?php selected( $s[ $gw_key ], $gw_id ); ?>>
                                            <?php echo esc_html( $gw_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button( __( 'Salvar configurações', 'checkout-gvntrck' ) ); ?>
        </form>
        <?php
    }

    public static function save_settings() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Permissão negada.', 'checkout-gvntrck' ) );
        }
        check_admin_referer( 'cgv_save_settings' );

        $current = CGV_Plugin::get_settings();

        $current['product_id']    = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        $current['thank_you_url'] = isset( $_POST['thank_you_url'] ) ? esc_url_raw( wp_unslash( $_POST['thank_you_url'] ) ) : '';
        $current['empty_cart_home_url'] = isset( $_POST['empty_cart_home_url'] ) ? esc_url_raw( wp_unslash( $_POST['empty_cart_home_url'] ) ) : home_url( '/' );
        $current['show_coupon_field'] = ! empty( $_POST['show_coupon_field'] ) ? 1 : 0;

        foreach ( [ 'card', 'pix', 'boleto' ] as $k ) {
            $current[ 'tab_' . $k . '_enabled' ] = ! empty( $_POST[ 'tab_' . $k . '_enabled' ] ) ? 1 : 0;
            $current[ 'tab_' . $k . '_label' ]   = isset( $_POST[ 'tab_' . $k . '_label' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'tab_' . $k . '_label' ] ) ) : '';
            $current[ 'gateway_' . $k ]          = isset( $_POST[ 'gateway_' . $k ] ) ? sanitize_key( wp_unslash( $_POST[ 'gateway_' . $k ] ) ) : '';
        }

        update_option( 'cgv_settings', $current );

        add_settings_error( 'cgv_messages', 'cgv_saved', __( 'Configurações salvas.', 'checkout-gvntrck' ), 'updated' );
        set_transient( 'settings_errors', get_settings_errors(), 30 );

        wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'tab' => 'general', 'updated' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /* -----------------------------------------------------------------
     * Tab: Fields
     * --------------------------------------------------------------- */
    protected static function render_fields_tab() {
        $fields = CGV_Fields::get_fields();
        $types  = CGV_Fields::allowed_types();
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cgv-form-admin cgv-fields-admin">
            <?php wp_nonce_field( 'cgv_save_fields' ); ?>
            <input type="hidden" name="action" value="cgv_save_fields" />

            <p class="description">
                <?php esc_html_e( 'Gerencie os campos exibidos na seção "Identificação" do card. O campo "full_name" (Nome Completo) é especial: ele será automaticamente dividido em billing_first_name e billing_last_name.', 'checkout-gvntrck' ); ?>
            </p>

            <table class="widefat cgv-fields-table">
                <thead>
                    <tr>
                        <th style="width:24px"></th>
                        <th><?php esc_html_e( 'ID', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Rótulo', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Tipo', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Placeholder', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Billing Key', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Opções', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Largura', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Obrigatório', 'checkout-gvntrck' ); ?></th>
                        <th><?php esc_html_e( 'Ativo', 'checkout-gvntrck' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cgv-fields-rows">
                <?php foreach ( $fields as $i => $f ) : ?>
                    <?php self::render_field_row( $i, $f, $types ); ?>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="cgv-add-field"><?php esc_html_e( '+ Adicionar campo', 'checkout-gvntrck' ); ?></button>
            </p>

            <script type="text/template" id="cgv-field-tpl">
                <?php self::render_field_row( '__INDEX__', [
                    'id' => '', 'label' => '', 'type' => 'text', 'placeholder' => '',
                    'billing_key' => '', 'options' => [], 'span' => 'full', 'required' => 0, 'enabled' => 1,
                ], $types ); ?>
            </script>

            <?php submit_button( __( 'Salvar campos', 'checkout-gvntrck' ) ); ?>
        </form>
        <?php
    }

    protected static function render_field_row( $index, $f, $types ) {
        $f = wp_parse_args( $f, [
            'id' => '', 'label' => '', 'type' => 'text', 'placeholder' => '',
            'billing_key' => '', 'options' => [], 'span' => 'full', 'required' => 0, 'enabled' => 1,
        ] );
        ?>
        <tr class="cgv-field-row">
            <td class="cgv-handle">≡</td>
            <td><input type="text" name="fields[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $f['id'] ); ?>" class="regular-text" /></td>
            <td><input type="text" name="fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $f['label'] ); ?>" class="regular-text" /></td>
            <td>
                <select name="fields[<?php echo esc_attr( $index ); ?>][type]">
                    <?php foreach ( $types as $type => $label ) : ?>
                        <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $f['type'], $type ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="text" name="fields[<?php echo esc_attr( $index ); ?>][placeholder]" value="<?php echo esc_attr( $f['placeholder'] ); ?>" /></td>
            <td><input type="text" name="fields[<?php echo esc_attr( $index ); ?>][billing_key]" value="<?php echo esc_attr( $f['billing_key'] ); ?>" /></td>
            <td>
                <textarea
                    name="fields[<?php echo esc_attr( $index ); ?>][options]"
                    rows="3"
                    placeholder="<?php esc_attr_e( 'valor=Rótulo', 'checkout-gvntrck' ); ?>"
                ><?php echo esc_textarea( CGV_Fields::format_options( $f['options'] ?? [] ) ); ?></textarea>
            </td>
            <td>
                <select name="fields[<?php echo esc_attr( $index ); ?>][span]">
                    <option value="full" <?php selected( $f['span'], 'full' ); ?>><?php esc_html_e( 'Inteira', 'checkout-gvntrck' ); ?></option>
                    <option value="half" <?php selected( $f['span'], 'half' ); ?>><?php esc_html_e( 'Metade', 'checkout-gvntrck' ); ?></option>
                </select>
            </td>
            <td><input type="checkbox" name="fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( ! empty( $f['required'] ) ); ?> /></td>
            <td><input type="checkbox" name="fields[<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( ! empty( $f['enabled'] ) ); ?> /></td>
            <td><button type="button" class="button-link cgv-remove-field" aria-label="<?php esc_attr_e( 'Remover', 'checkout-gvntrck' ); ?>">✕</button></td>
        </tr>
        <?php
    }

    public static function save_fields() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Permissão negada.', 'checkout-gvntrck' ) );
        }
        check_admin_referer( 'cgv_save_fields' );

        $raw = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [];
        $clean = CGV_Fields::sanitize_fields( $raw );
        update_option( 'cgv_fields', $clean );

        add_settings_error( 'cgv_messages', 'cgv_saved', __( 'Campos atualizados.', 'checkout-gvntrck' ), 'updated' );
        wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'tab' => 'fields', 'updated' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /* -----------------------------------------------------------------
     * Tab: Layout & Texts
     * --------------------------------------------------------------- */
    protected static function render_layout_tab() {
        $s = CGV_Plugin::get_settings();
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cgv-form-admin">
            <?php wp_nonce_field( 'cgv_save_layout' ); ?>
            <input type="hidden" name="action" value="cgv_save_layout" />

            <h2><?php esc_html_e( 'Layout do Checkout', 'checkout-gvntrck' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Layout em duas colunas (Split)', 'checkout-gvntrck' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="split_layout" value="1" <?php checked( ! empty( $s['split_layout'] ) ); ?> />
                            <?php esc_html_e( 'Ativar por padrão', 'checkout-gvntrck' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Se ativado, divide o checkout em duas colunas (resumo fixo à esquerda e formulário à direita). Pode ser sobrescrito individualmente via atributo do shortcode.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="checkout_logo"><?php esc_html_e( 'Logo do Checkout (URL)', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="url" id="checkout_logo" name="checkout_logo" class="regular-text" value="<?php echo esc_url( $s['checkout_logo'] ); ?>" />
                        <p class="description"><?php esc_html_e( 'Insira a URL de um logo personalizado para exibir no topo do resumo esquerdo. Deixe em branco para usar o logo padrão do WordPress ou o nome do site.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="checkout_logo_width"><?php esc_html_e( 'Largura do Logo (px)', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="number" id="checkout_logo_width" name="checkout_logo_width" class="small-text" min="0" value="<?php echo esc_attr( $s['checkout_logo_width'] ); ?>" />
                        <p class="description"><?php esc_html_e( 'Deixe em branco para definir como automático.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="checkout_logo_height"><?php esc_html_e( 'Altura do Logo (px)', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="number" id="checkout_logo_height" name="checkout_logo_height" class="small-text" min="0" value="<?php echo esc_attr( $s['checkout_logo_height'] ); ?>" />
                        <p class="description"><?php esc_html_e( 'Deixe em branco para definir como automático (padrão: 48px se nenhum tamanho for especificado).', 'checkout-gvntrck' ); ?></p>
                        <p class="description"><strong><?php esc_html_e( 'Dica: Preencha apenas um dos campos (largura ou altura) para redimensionar o logo mantendo sua proporção original.', 'checkout-gvntrck' ); ?></strong></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Cores', 'checkout-gvntrck' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Cor primária (textos / abas ativas)', 'checkout-gvntrck' ); ?></label></th>
                    <td><input type="text" class="cgv-color" name="primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Cor de destaque (botão)', 'checkout-gvntrck' ); ?></label></th>
                    <td><input type="text" class="cgv-color" name="accent_color" value="<?php echo esc_attr( $s['accent_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Fundo do card', 'checkout-gvntrck' ); ?></label></th>
                    <td><input type="text" class="cgv-color" name="card_bg_color" value="<?php echo esc_attr( $s['card_bg_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Animação pulsante no botão', 'checkout-gvntrck' ); ?></label></th>
                    <td><label><input type="checkbox" name="enable_pulse" value="1" <?php checked( ! empty( $s['enable_pulse'] ) ); ?> /> <?php esc_html_e( 'Ativar', 'checkout-gvntrck' ); ?></label></td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Largura do Card', 'checkout-gvntrck' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Defina a largura máxima (em pixels) do card de checkout. O card sempre se adapta a telas menores. Use 0 para remover o limite.', 'checkout-gvntrck' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="card_max_width_single"><?php esc_html_e( 'Largura máxima — [checkout-gvntrck]', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="number" id="card_max_width_single" name="card_max_width_single" min="0" step="1"
                               value="<?php echo esc_attr( (int) $s['card_max_width_single'] ); ?>" class="small-text" />
                        <span>px</span>
                        <p class="description"><?php esc_html_e( 'Largura usada no checkout de produto único. Padrão: 480px.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="card_max_width_general"><?php esc_html_e( 'Largura máxima — [checkout-gvntrck-geral]', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="number" id="card_max_width_general" name="card_max_width_general" min="0" step="1"
                               value="<?php echo esc_attr( (int) $s['card_max_width_general'] ); ?>" class="small-text" />
                        <span>px</span>
                        <p class="description"><?php esc_html_e( 'Largura usada no checkout geral do carrinho. Padrão: 680px.', 'checkout-gvntrck' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Borda e Sombra do Card', 'checkout-gvntrck' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure borda, raio dos cantos e sombra de forma independente para cada shortcode.', 'checkout-gvntrck' ); ?></p>

            <h3><?php esc_html_e( '[checkout-gvntrck] (produto único)', 'checkout-gvntrck' ); ?></h3>
            <?php self::render_card_style_rows( $s, 'single' ); ?>

            <h3><?php esc_html_e( '[checkout-gvntrck-geral] (carrinho)', 'checkout-gvntrck' ); ?></h3>
            <?php self::render_card_style_rows( $s, 'general' ); ?>

            <h2><?php esc_html_e( 'Títulos e Ícones', 'checkout-gvntrck' ); ?></h2>
            <p class="description">
                <?php
                printf(
                    /* translators: %s is a link */
                    esc_html__( 'Os ícones usam o nome de %s.', 'checkout-gvntrck' ),
                    '<a href="https://fonts.google.com/icons" target="_blank" rel="noopener">Material Symbols</a>'
                );
                ?>
            </p>
            <table class="form-table">
                <?php
                $rows = [
                    'header_title'   => [ __( 'Título do card (opcional)', 'checkout-gvntrck' ), 'header_icon' ],
                    'ident_title'    => [ __( 'Título — Identificação', 'checkout-gvntrck' ), 'ident_icon' ],
                    'payment_title'  => [ __( 'Título — Pagamento', 'checkout-gvntrck' ), 'payment_icon' ],
                ];
                foreach ( $rows as $key => $row ) :
                    list( $label, $icon_key ) = $row;
                    ?>
                    <tr>
                        <th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                        <td>
                            <input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $s[ $key ] ); ?>" class="regular-text" />
                            <label style="margin-left:8px"><?php esc_html_e( 'Ícone:', 'checkout-gvntrck' ); ?>
                                <input type="text" name="<?php echo esc_attr( $icon_key ); ?>" value="<?php echo esc_attr( $s[ $icon_key ] ); ?>" />
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2><?php esc_html_e( 'Resumo e Botão', 'checkout-gvntrck' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="summary_label"><?php esc_html_e( 'Rótulo do valor do produto', 'checkout-gvntrck' ); ?></label></th>
                    <td><input type="text" id="summary_label" name="summary_label" value="<?php echo esc_attr( $s['summary_label'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="total_label"><?php esc_html_e( 'Rótulo do total', 'checkout-gvntrck' ); ?></label></th>
                    <td><input type="text" id="total_label" name="total_label" value="<?php echo esc_attr( $s['total_label'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="button_text"><?php esc_html_e( 'Texto do botão', 'checkout-gvntrck' ); ?></label></th>
                    <td>
                        <input type="text" id="button_text" name="button_text" value="<?php echo esc_attr( $s['button_text'] ); ?>" class="regular-text" />
                        <label style="margin-left:8px"><?php esc_html_e( 'Ícone:', 'checkout-gvntrck' ); ?>
                            <input type="text" name="button_icon" value="<?php echo esc_attr( $s['button_icon'] ); ?>" />
                        </label>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Selos de Confiança', 'checkout-gvntrck' ); ?></h2>
            <table class="form-table">
                <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
                    <tr>
                        <th><?php printf( esc_html__( 'Selo %d', 'checkout-gvntrck' ), $i ); ?></th>
                        <td>
                            <label><?php esc_html_e( 'Ícone:', 'checkout-gvntrck' ); ?>
                                <input type="text" name="badge_<?php echo (int) $i; ?>_icon" value="<?php echo esc_attr( $s[ "badge_{$i}_icon" ] ); ?>" />
                            </label>
                            &nbsp;
                            <label><?php esc_html_e( 'Texto:', 'checkout-gvntrck' ); ?>
                                <input type="text" name="badge_<?php echo (int) $i; ?>_text" value="<?php echo esc_attr( $s[ "badge_{$i}_text" ] ); ?>" class="regular-text" />
                            </label>
                        </td>
                    </tr>
                <?php endfor; ?>
            </table>

            <?php submit_button( __( 'Salvar layout & textos', 'checkout-gvntrck' ) ); ?>
        </form>
        <?php
    }

    /**
     * Renderiza os campos de borda/sombra para um modo específico (single|general).
     */
    protected static function render_card_style_rows( $s, $mode ) {
        $border_enabled = 'card_border_enabled_' . $mode;
        $border_width   = 'card_border_width_' . $mode;
        $border_color   = 'card_border_color_' . $mode;
        $radius         = 'card_radius_' . $mode;
        $shadow_enabled = 'card_shadow_enabled_' . $mode;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Borda', 'checkout-gvntrck' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( $border_enabled ); ?>" value="1" <?php checked( ! empty( $s[ $border_enabled ] ) ); ?> />
                        <?php esc_html_e( 'Exibir borda', 'checkout-gvntrck' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo esc_attr( $border_width ); ?>"><?php esc_html_e( 'Espessura da borda', 'checkout-gvntrck' ); ?></label></th>
                <td>
                    <input type="number" id="<?php echo esc_attr( $border_width ); ?>" name="<?php echo esc_attr( $border_width ); ?>" min="0" step="1"
                           value="<?php echo esc_attr( (int) $s[ $border_width ] ); ?>" class="small-text" />
                    <span>px</span>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Cor da borda', 'checkout-gvntrck' ); ?></label></th>
                <td>
                    <input type="text" class="cgv-color" name="<?php echo esc_attr( $border_color ); ?>" value="<?php echo esc_attr( $s[ $border_color ] ); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo esc_attr( $radius ); ?>"><?php esc_html_e( 'Raio dos cantos', 'checkout-gvntrck' ); ?></label></th>
                <td>
                    <input type="number" id="<?php echo esc_attr( $radius ); ?>" name="<?php echo esc_attr( $radius ); ?>" min="0" step="1"
                           value="<?php echo esc_attr( (int) $s[ $radius ] ); ?>" class="small-text" />
                    <span>px</span>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Sombra', 'checkout-gvntrck' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( $shadow_enabled ); ?>" value="1" <?php checked( ! empty( $s[ $shadow_enabled ] ) ); ?> />
                        <?php esc_html_e( 'Exibir sombra', 'checkout-gvntrck' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function save_layout() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Permissão negada.', 'checkout-gvntrck' ) );
        }
        check_admin_referer( 'cgv_save_layout' );

        $current = CGV_Plugin::get_settings();

        $text_fields = [
            'header_title', 'header_icon', 'ident_title', 'ident_icon',
            'payment_title', 'payment_icon', 'button_text', 'button_icon',
            'summary_label', 'total_label',
            'badge_1_icon', 'badge_1_text', 'badge_2_icon', 'badge_2_text',
            'badge_3_icon', 'badge_3_text',
        ];
        foreach ( $text_fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                $current[ $f ] = sanitize_text_field( wp_unslash( $_POST[ $f ] ) );
            }
        }

        foreach ( [ 'primary_color', 'accent_color', 'card_bg_color' ] as $c ) {
            if ( isset( $_POST[ $c ] ) ) {
                $val = sanitize_hex_color( wp_unslash( $_POST[ $c ] ) );
                if ( $val ) {
                    $current[ $c ] = $val;
                }
            }
        }

        $current['enable_pulse'] = ! empty( $_POST['enable_pulse'] ) ? 1 : 0;
        $current['split_layout'] = ! empty( $_POST['split_layout'] ) ? 1 : 0;
        $current['checkout_logo'] = isset( $_POST['checkout_logo'] ) ? esc_url_raw( wp_unslash( $_POST['checkout_logo'] ) ) : '';
        foreach ( [ 'checkout_logo_width', 'checkout_logo_height' ] as $logo_dim ) {
            if ( isset( $_POST[ $logo_dim ] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST[ $logo_dim ] ) );
                $current[ $logo_dim ] = $val !== '' ? max( 0, absint( $val ) ) : '';
            }
        }

        foreach ( [ 'card_max_width_single', 'card_max_width_general' ] as $w ) {
            if ( isset( $_POST[ $w ] ) ) {
                $current[ $w ] = max( 0, absint( wp_unslash( $_POST[ $w ] ) ) );
            }
        }

        foreach ( [ 'single', 'general' ] as $mode ) {
            $current[ 'card_border_enabled_' . $mode ] = ! empty( $_POST[ 'card_border_enabled_' . $mode ] ) ? 1 : 0;
            $current[ 'card_shadow_enabled_' . $mode ] = ! empty( $_POST[ 'card_shadow_enabled_' . $mode ] ) ? 1 : 0;

            foreach ( [ 'card_border_width_', 'card_radius_' ] as $prefix ) {
                $key = $prefix . $mode;
                if ( isset( $_POST[ $key ] ) ) {
                    $current[ $key ] = max( 0, absint( wp_unslash( $_POST[ $key ] ) ) );
                }
            }

            $color_key = 'card_border_color_' . $mode;
            if ( isset( $_POST[ $color_key ] ) ) {
                $val = sanitize_hex_color( wp_unslash( $_POST[ $color_key ] ) );
                if ( $val ) {
                    $current[ $color_key ] = $val;
                }
            }
        }

        update_option( 'cgv_settings', $current );
        add_settings_error( 'cgv_messages', 'cgv_saved', __( 'Layout salvo.', 'checkout-gvntrck' ), 'updated' );
        wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'tab' => 'layout', 'updated' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
