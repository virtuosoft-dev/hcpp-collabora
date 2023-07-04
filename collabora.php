<?php
/**
 * Extend the HestiaCP Pluginable object with our Collabora object for
 * allocating a shared Collabora Server for user accounts.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-collabora
 * 
 */

if ( ! class_exists( 'Collabora') ) {
    class Collabora {
        /**
         * Constructor, listen for add, update, or remove users
         */
        public function __construct() {
            global $hcpp;
            $hcpp->collabora = $this;
            $hcpp->add_action( 'csrf_verified', [ $this, 'csrf_verified' ] );
            $hcpp->add_action( 'invoke_plugin', [ $this, 'collabora_support' ] );
            $hcpp->add_action( 'render_page', [ $this, 'render_page' ] );
        }

        /**
         * Check if Collabora Server is enabled for this user's domain
         */
        public function collabora_support( $args ) {
            if ( $args[0] != 'collabora_support' ) return $args;
            global $hcpp;
            $options = json_decode( $args[1], true );
            $conf_folder = $options['conf_folder'];
            $enabled = $options['enabled'];
            $domain = $options['domain'];
            
            // Write nginx configuration files
            if ($enabled) {
                touch( '/usr/local/hestia/data/hcpp/collabora_domains/' . $domain );
                $content = file_get_contents( __DIR__ . '/conf-web/nginx.conf_coolwsd' );
                file_put_contents( $conf_folder . '/nginx.conf_coolwsd', $content );
                $content = file_get_contents( __DIR__ . '/conf-web/nginx.ssl.conf_coolwsd' );
                file_put_contents( $conf_folder . '/nginx.ssl.conf_coolwsd', $content );

            }else{
                unlink( '/usr/local/hestia/data/hcpp/collabora_domains/' . $domain);
                unlink( $conf_folder . '/nginx.conf_coolwsd' );
                unlink( $conf_folder . '/nginx.ssl.conf_coolwsd' );
            }
            return true;
        }

        // Intercept form submission to record Collabora subfolder option
        public function csrf_verified() {
            if ( isset( $_REQUEST['v_domain'] ) ) {
                global $hcpp;
                $enabled = false;
                if ( isset( $_REQUEST['collabora_support'] ) && $_REQUEST['collabora_support'] == 'on' )  {
                    $enabled = true;
                }
                $args = [ 
                    'enabled' => $enabled,
                    'domain' => $_REQUEST['v_domain'],
                    'conf_folder' => str_replace( '/web/', '/conf/web/', $_REQUEST['v_ftp_pre_path']) 
                ];
                $hcpp->run( 'invoke-plugin collabora_support ' . escapeshellarg( json_encode( $args ) ) );
            }
        }

        /**
         * Inject Collabora Server option under Advanced Options
         */
        public function render_page( $args ) {
            if ( $args['page'] == 'edit_web' ) {
                global $hcpp;
                $content = $args['content'];
                $domain = '';
                if ( isset( $_REQUEST['domain'] ) ) {
                    $domain = $_REQUEST['domain'];
                }
                $checked = '';
                if ( file_exists( '/usr/local/hestia/data/hcpp/collabora_domains/' . $domain ) ) {
                    $checked = 'checked="true"';
                }
                $code = '<div class="form-check u-mb10">
                    <input class="form-check-input" type="checkbox" name="collabora_support" id="collabora_support" ' . $checked . '>
                    <label for="collabora_support">Enable Collabora Server in subfolder <small>(' . $domain . '/coolwsd)</small></label>
                </div>';
                $adv_div = '<div x-cloak x-show="showAdvanced">';
                $before = $hcpp->getLeftMost( $content, $adv_div ) . $adv_div;
                $content = $hcpp->delLeftMost( $content, $adv_div );
                $content = $before . $code . $content;
                $args['content'] = $content;
            }
            return $args;
        }

    }
    new Collabora();
} 