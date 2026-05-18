<?php
/**
 * Field manager.
 *
 * @package CheckoutGVNTRCK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGV_Fields {

    const DEFAULT_FIELDS_VERSION = '2026-05-18-br-billing-fields';

    public static function init() {}

    /**
     * Default fields shipped with the plugin.
     */
    public static function default_fields() {
        return [
            [
                'id'           => 'full_name',
                'label'        => 'Nome Completo',
                'type'         => 'text',
                'placeholder'  => 'Seu nome',
                'billing_key'  => 'billing_full_name',
                'required'     => 1,
                'enabled'      => 1,
                'span'         => 'full',
                'autocomplete' => 'name',
            ],
            [
                'id'           => 'email',
                'label'        => 'E-mail',
                'type'         => 'email',
                'placeholder'  => 'seu@email.com',
                'billing_key'  => 'billing_email',
                'required'     => 1,
                'enabled'      => 1,
                'span'         => 'full',
                'autocomplete' => 'email',
            ],
            [
                'id'           => 'persontype',
                'label'        => 'Tipo de Pessoa',
                'type'         => 'select',
                'placeholder'  => '',
                'billing_key'  => 'billing_persontype',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'full',
                'autocomplete' => '',
                'options'      => [
                    '1' => 'Pessoa Física',
                    '2' => 'Pessoa Jurídica',
                ],
            ],
            [
                'id'           => 'cpf',
                'label'        => 'CPF',
                'type'         => 'tel',
                'placeholder'  => '000.000.000-00',
                'billing_key'  => 'billing_cpf',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
            ],
            [
                'id'           => 'rg',
                'label'        => 'RG',
                'type'         => 'text',
                'placeholder'  => 'RG',
                'billing_key'  => 'billing_rg',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
            ],
            [
                'id'           => 'cnpj',
                'label'        => 'CNPJ',
                'type'         => 'tel',
                'placeholder'  => '00.000.000/0000-00',
                'billing_key'  => 'billing_cnpj',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
            ],
            [
                'id'           => 'ie',
                'label'        => 'Inscrição Estadual',
                'type'         => 'text',
                'placeholder'  => 'Inscrição Estadual',
                'billing_key'  => 'billing_ie',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
            ],
            [
                'id'           => 'birthdate',
                'label'        => 'Data de Nascimento',
                'type'         => 'text',
                'placeholder'  => '00/00/0000',
                'billing_key'  => 'billing_birthdate',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
            ],
            [
                'id'           => 'gender',
                'label'        => 'Gênero',
                'type'         => 'select',
                'placeholder'  => '',
                'billing_key'  => 'billing_gender',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
                'options'      => [
                    ''          => 'Select',
                    'nao_dizer' => 'Prefiro não dizer',
                    'feminino'  => 'Feminino',
                    'masculino' => 'Masculino',
                    'outro'     => 'Outro',
                ],
            ],
            [
                'id'           => 'number',
                'label'        => 'Número',
                'type'         => 'text',
                'placeholder'  => 'Número',
                'billing_key'  => 'billing_number',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => '',
            ],
            [
                'id'           => 'neighborhood',
                'label'        => 'Bairro',
                'type'         => 'text',
                'placeholder'  => 'Bairro',
                'billing_key'  => 'billing_neighborhood',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'half',
                'autocomplete' => 'address-level3',
            ],
            [
                'id'           => 'cellphone',
                'label'        => 'Celular',
                'type'         => 'tel',
                'placeholder'  => '(00) 00000-0000',
                'billing_key'  => 'billing_cellphone',
                'required'     => 0,
                'enabled'      => 1,
                'span'         => 'full',
                'autocomplete' => 'tel',
            ],
        ];
    }

    /**
     * Add newly shipped default fields without overwriting user edits.
     */
    public static function maybe_seed_default_fields() {
        $fields = get_option( 'cgv_fields', false );
        if ( false === $fields || ! is_array( $fields ) ) {
            update_option( 'cgv_fields', self::default_fields() );
            update_option( 'cgv_fields_default_version', self::DEFAULT_FIELDS_VERSION );
            return;
        }

        if ( self::DEFAULT_FIELDS_VERSION === get_option( 'cgv_fields_default_version', '' ) ) {
            return;
        }

        $merged = self::merge_default_fields( $fields );
        if ( $merged !== $fields ) {
            update_option( 'cgv_fields', $merged );
        }
        update_option( 'cgv_fields_default_version', self::DEFAULT_FIELDS_VERSION );
    }

    /**
     * Get configured fields.
     */
    public static function get_fields() {
        $fields = get_option( 'cgv_fields', null );
        if ( null === $fields || ! is_array( $fields ) ) {
            return self::default_fields();
        }
        return $fields;
    }

    /**
     * Allowed field types.
     */
    public static function allowed_types() {
        return [
            'text'     => 'Texto',
            'email'    => 'E-mail',
            'tel'      => 'Telefone',
            'number'   => 'Número',
            'password' => 'Senha',
            'select'   => 'Select/Dropdown',
        ];
    }

    /**
     * Sanitize a fields array (used in admin save).
     */
    public static function sanitize_fields( $raw ) {
        if ( ! is_array( $raw ) ) {
            return [];
        }
        $clean = [];
        $allowed_types = array_keys( self::allowed_types() );
        foreach ( $raw as $f ) {
            if ( ! is_array( $f ) ) {
                continue;
            }
            $id = sanitize_key( $f['id'] ?? '' );
            if ( '' === $id ) {
                continue;
            }
            $type = isset( $f['type'] ) && in_array( $f['type'], $allowed_types, true ) ? $f['type'] : 'text';
            $billing_key = sanitize_key( $f['billing_key'] ?? '' );
            if ( '' === $billing_key ) {
                $billing_key = 'billing_' . $id;
            }
            $clean[] = [
                'id'           => $id,
                'label'        => sanitize_text_field( $f['label'] ?? '' ),
                'type'         => $type,
                'placeholder'  => sanitize_text_field( $f['placeholder'] ?? '' ),
                'billing_key'  => $billing_key,
                'required'     => ! empty( $f['required'] ) ? 1 : 0,
                'enabled'      => ! empty( $f['enabled'] ) ? 1 : 0,
                'span'         => ( isset( $f['span'] ) && 'half' === $f['span'] ) ? 'half' : 'full',
                'autocomplete' => sanitize_text_field( $f['autocomplete'] ?? '' ),
                'options'      => self::sanitize_options( $f['options'] ?? [] ),
            ];
        }
        return $clean;
    }

    /**
     * Merge default fields by ID, preserving configured rows.
     */
    protected static function merge_default_fields( $fields ) {
        $known_ids = [];
        foreach ( $fields as $field ) {
            if ( is_array( $field ) && ! empty( $field['id'] ) ) {
                $known_ids[] = sanitize_key( $field['id'] );
            }
        }

        foreach ( self::default_fields() as $field ) {
            if ( ! in_array( $field['id'], $known_ids, true ) ) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Sanitize select options from an array or textarea value.
     */
    public static function sanitize_options( $raw ) {
        if ( is_string( $raw ) ) {
            $lines = preg_split( '/\r\n|\r|\n/', $raw );
            $raw   = [];
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( '' === $line ) {
                    continue;
                }
                if ( false !== strpos( $line, '=' ) ) {
                    list( $value, $label ) = array_map( 'trim', explode( '=', $line, 2 ) );
                } else {
                    $value = sanitize_title( $line );
                    $label = $line;
                }
                $raw[ $value ] = $label;
            }
        }

        if ( ! is_array( $raw ) ) {
            return [];
        }

        $options = [];
        foreach ( $raw as $value => $label ) {
            $options[ sanitize_text_field( (string) $value ) ] = sanitize_text_field( (string) $label );
        }

        return $options;
    }

    /**
     * Convert select options to the admin textarea format.
     */
    public static function format_options( $options ) {
        $options = self::sanitize_options( $options );
        $lines   = [];
        foreach ( $options as $value => $label ) {
            $lines[] = $value . '=' . $label;
        }
        return implode( "\n", $lines );
    }
}
