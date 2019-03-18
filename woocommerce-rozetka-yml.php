<?php
/**
Plugin Name: Rozetka marketplace - YML creator
Description: Plugin to create rozetka marketplace XML file to the plugin Woocommerce
Author: Dmitriy Kishkin
Author URI: http://www.kishkin.pro/
Version: 1.1
 */

// Подключил для того чтобы is_plugin_active работал.

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
// Проверяю версию WordPress.
if( !is_plugin_active( 'woocommerce/woocommerce.php' ) ){

    // деактивируем
    add_action( 'admin_init', 'true_plugin_off_gc_atlantdak' );
    function true_plugin_off_gc_atlantdak() {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    // добавляем соответствующее уведомление
    add_action( 'admin_notices', 'true_plugin_uvedomlenie' );
    function true_plugin_uvedomlenie() {
        echo '<div class="updated">Плагин <p><strong>Rozetka marketplace - YML creator</strong> был отключен, так как для его работы должен быть включен плагин <strong>WooCommerce</strong></p></div>';

        // также сносим параметр из URL, чтобы не выполнилось ничего лишнего
        if ( isset( $_GET['activate'] ) )
            unset( $_GET['activate'] );
    }
}
else{
    // тут будет находиться весь остальной код плагина

    require_once('classes/rozetkaSettings.php');
    require_once('classes/rozetkaStyleAndScripts.php');
    require_once('classes/rozetkaWoocommerceBrandsTaxonomies.php');
    require_once('classes/rozetkaXmlGenerate.php');

    //Планирование
    if(get_option('rozetkaYmlCron')==1){
        if( !wp_next_scheduled( 'woocommerce_rozetka_xml_refresh' ) ) {
            wp_schedule_event( time(), 'daily', 'woocommerce_rozetka_xml_refresh' );
        }
    }
    //Удалить планирование если не стоит галочка в админке на планировании
    if(get_option('rozetkaYmlCron')!=1){
        $timestamp = wp_next_scheduled('woocommerce_rozetka_xml_refresh');
        wp_unschedule_event($timestamp, 'woocommerce_rozetka_xml_refresh');
    }
    add_action( 'woocommerce_rozetka_xml_refresh', 'generate_xml_file' );
    /*$timestamp = wp_next_scheduled('woocommerce_rozetka_xml_refresh');
    wp_unschedule_event($timestamp, 'woocommerce_rozetka_xml_refresh');
    */

    /*Запускаю генерацию XML, если продукт обновлен/опубликован*/
    add_action( 'save_post_product', 'generate_xml_file' );
    add_action( 'init', 'generate_xml_file' );
}

function generate_xml_file(){
    $rozetkaXMLGenerate = new rozetkaXMLGenerate();
}

?>