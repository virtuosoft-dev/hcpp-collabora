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
            $hcpp->add_action( 'hcpp_csrf_verified', [ $this, 'hcpp_csrf_verified' ] );
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'collabora_support' ] );
            $hcpp->add_action( 'hcpp_render_body', [ $this, 'hcpp_render_body' ] );
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

                // Add Listen 127.0.0.1
                $this->addInterfaceToListen( $conf_folder . '/nginx.conf' );
                $this->addInterfaceToListen( $conf_folder . '/nginx.ssl.conf' );

            }else{
                unlink( '/usr/local/hestia/data/hcpp/collabora_domains/' . $domain);
                unlink( $conf_folder . '/nginx.conf_coolwsd' );
                unlink( $conf_folder . '/nginx.ssl.conf_coolwsd' );

                // Remove Listen 127.0.01
                $this->removeInterfaceToListen( $conf_folder . '/nginx.conf' );
                $this->removeInterfaceToListen( $conf_folder . '/nginx.ssl.conf' );
            }
            return true;
        }

        // Intercept form submission to record Collabora subfolder option
        public function hcpp_csrf_verified() {
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
        public function hcpp_render_body( $args ) {
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
                    <label for="collabora_support">Enable Collabora Online services in root</label>
                </div>';
                $adv_div = '<div x-cloak x-show="showAdvanced">';
                $before = $hcpp->getLeftMost( $content, $adv_div ) . $adv_div;
                $content = $hcpp->delLeftMost( $content, $adv_div );
                $content = $before . $code . $content;
                $args['content'] = $content;
            }
            return $args;
        }

        /**
         * Add 127.0.0.1 as an interface to listen
         */
        public function addInterfaceToListen( $file ) {
            $content = file_get_contents( $file );

            // Skip if already added
            if ( strpos( $content, 'listen      127.0.0.1:' ) !== false ) return;

            // HTTP nginx.conf
            if ( strpos( $content, ':443 ssl http2;' ) === false ) {

                // Add 127.0.0.1 as an interface to listen
                $newContent = preg_replace( '/(listen\s+(?:\S+\s+)?)(\d+\.\d+\.\d+\.\d+):80;/i', '$1$2:80;' . PHP_EOL . '    listen      127.0.0.1:80;', $content );

            // HTTPS nginx.ssl.conf
            }else{

                // Add 127.0.0.1 as an interface to listen
                $newContent = preg_replace( '/(listen\s+(?:\S+\s+)?)(\d+\.\d+\.\d+\.\d+):443\s+ssl\s+http2;/i', '$1$2:443 ssl http2;' . PHP_EOL . '    listen      127.0.0.1:443 ssl http2;', $content );
            }
            file_put_contents( $file, $newContent );
        }
        
        /**
         * Remove 127.0.0.1 as an interface to listen
         */
        function removeInterfaceToListen( $file ) {
            $content = file_get_contents( $file );

            // HTTP nginx.conf
            if ( strpos( $content, ':443 ssl http2;' ) === false ) {

                // Remove 127.0.0.1 from the interface to listen
                $newContent = preg_replace('/\s*listen\s+(?:\S+\s+)?127.0.0.1:80;\s*/i', '', $content);
            
            // HTTPS nginx.ssl.conf
            }else{

                // Remove 127.0.0.1 from the interface to listen
                $newContent = preg_replace( '/\s*listen\s+(?:\S+\s+)?127.0.0.1:443\s+ssl\s+http2;\s*/i', '', $content );
            }
            file_put_contents( $file, $newContent );
        }
    }
    new Collabora();
} 