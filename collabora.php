<?php
/**
 * Extend the HestiaCP Pluginable object with our Collabora object for
 * allocating a shared Collabora Server domain.
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
        }
    }
    new Collabora();
} 