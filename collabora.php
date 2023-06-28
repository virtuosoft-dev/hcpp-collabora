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
            $hcpp->webdav = $this;
            // $hcpp->add_action( 'priv_unsuspend_domain', [ $this, 'priv_unsuspend_domain' ] );
            // $hcpp->add_action( 'new_web_domain_ready', [ $this, 'new_web_domain_ready' ] );
            // $hcpp->add_action( 'priv_delete_user', [ $this, 'priv_delete_user' ] );
            $hcpp->add_action( 'csrf_verified', [ $this, 'csrf_verified' ] );
            $hcpp->add_action( 'render_page', [ $this, 'render_page' ] );
        }

        /**
         * Inject Collabora Server option under Advanced Options
         */
        public function render_page( $args ) {
            if ( $args['page'] == 'edit_web' ) {
                global $hcpp;
                $content = $args['content'];
                $code = '<div class="form-check u-mb10">
                    <input class="form-check-input" type="checkbox" name="collabora_support" id="collabora_support" checked="true">
                    <label for="collabora_support">Enable Collabora subfolder (' . $_GET['domain'] . '/coolwsd)</label>
                </div>';
                $adv_div = '<div x-cloak x-show="showAdvanced">';
                $before = $hcpp->getLeftMost( $content, $adv_div ) . $adv_div;
                $content = $hcpp->delLeftMost( $content, $adv_div );
                $content = $before . $code . $content;
                $args['content'] = $content;
            }
            return $args;
        }

        // Intercept form submission to include Collabora subfolder option
        public function csrf_verified() {
            if ( isset( $_REQUEST['domain'] ) ) {
                global $hcpp;
                $hcpp->log( $_REQUEST );
            }
        }
    }
    new Collabora();
} 