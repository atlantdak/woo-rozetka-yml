<?php
/**
 * Created by PhpStorm.
 * User: man2
 * Date: 20.12.2018
 * Time: 15:24
 */

class rozetkaStyleAndScripts
{
    public function __construct(){

        add_action('admin_print_styles', array( $this, 'add_my_stylesheet') );

    }

    public function add_my_stylesheet()
    {
        wp_enqueue_style( 'rozetkaXML-admin-style', plugins_url( '../assets/css/admin.css', __FILE__ ) );
    }


}

$rozetkaStyleAndScripts = new rozetkaStyleAndScripts();