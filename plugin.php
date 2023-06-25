<?php
/**
 * Plugin Name: Collabora
 * Plugin URI: https://github.com/virtuosoft-dev/hcpp-collabora
 * Description: Allocates a shared Collabora Server for HestiaCP user accounts.
 * Version: 1.0.0
 * Author: Stephen J. Carnam
 *
 */

// Register the install and uninstall scripts
global $hcpp;
require_once( dirname(__FILE__) . '/collabora.php' );

$hcpp->register_install_script( dirname(__FILE__) . '/install' );
$hcpp->register_uninstall_script( dirname(__FILE__) . '/uninstall' );
