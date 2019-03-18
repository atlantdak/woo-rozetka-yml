<?php
/**
 * Created by PhpStorm.
 * User: man2
 * Date: 19.11.2018
 * Time: 11:30
 */

class rozetkaWoocommerceBrandsTaxonomies
{


    public function __construct(){

        add_action('woocommerce_init', array( $this, 'woocommerce_loaded' ) );

    }


    private function create_woocommerce_brand_taxonomies() {
        // Add new taxonomy, make it hierarchical (like categories)
        $shop_page_id = woocommerce_get_page_id('shop');

        $base_slug = $shop_page_id > 0 && get_page($shop_page_id) ? get_page_uri($shop_page_id) : 'shop';

        $category_base = get_option('woocommerce_prepend_shop_page_to_urls') == "yes" ? trailingslashit($base_slug) : '';

        $cap = version_compare(WOOCOMMERCE_VERSION, '2.0', '<') ? 'manage_woocommerce_products' : 'edit_products';
        $labels = array(
            'name' => __('Бренды', 'woocommerce-brands'),
            'singular_name' => __('Бренд', 'woocommerce-brands'),
            'search_items' => __('Поиск', 'woocommerce-brands'),
            'all_items' => __('Все Бренды', 'woocommerce-brands'),
            'parent_item' => __('Родительский Бренд', 'woocommerce-brands'),
            'parent_item_colon' => __('Родительский Бренд:', 'woocommerce-brands'),
            'edit_item' => __('Редактировать Бренды', 'woocommerce-brands'),
            'update_item' => __('Обновить Бренд', 'woocommerce-brands'),
            'add_new_item' => __('Добавить новый Бренд', 'woocommerce-brands'),
            'new_item_name' => __('Название нового Бренда', 'woocommerce-brands'),
            'menu_name' => 'Бренды',
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'capabilities' => array(
                'manage_terms' => $cap,
                'edit_terms' => $cap,
                'delete_terms' => $cap,
                'assign_terms' => $cap
            ),
            'rewrite' => array('slug' => $category_base . __('brand', 'woocommerce-brands'), 'with_front' => false, 'hierarchical' => true)
        );
        register_taxonomy('product_brand', array('product'), apply_filters('register_taxonomy_product_brand', $args));
    }



    public function woocommerce_loaded() {
        $this->create_woocommerce_brand_taxonomies();
    }


}


$rozetkaWoocommerceBrandsTaxonomies = new rozetkaWoocommerceBrandsTaxonomies();