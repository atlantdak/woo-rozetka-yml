<?php
function setup_theme_admin_menus_gc_atlantdak() {
    add_submenu_page('woocommerce',
        'Rozetka YML Setting', 'Rozetka YML Setting', 'manage_options',
        'rozetka-setting-yml-admin', 'theme_front_page_settings_rozetka_atlantdak');
}  

// Она говорит WP, что нужно вызвать функцию "setup_theme_admin_menus"
// когда нужно будет создать страницы меню.
add_action("admin_menu", "setup_theme_admin_menus_gc_atlantdak"); 



function theme_front_page_settings_rozetka_atlantdak() {
// проверяем, что пользователь может обновлять настройки
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}     ?>
	<div class="wrap">
			<h2>Настройка создания Goods Catalog YML </h2>
			<form method="post" action="options.php">
				<?php wp_nonce_field('update-options') ?>
				<p><strong>Название магазина:</strong><br />
                    <?php
                        $shop_name = bloginfo('name');
                        $company_name = bloginfo('description');
                    ?>
					<input type="text" name="rozetkaShopName" size="145" value="<?php echo get_option('rozetkaShopName', $shop_name); ?>" />
				</p>
                <p><strong>Название компании:</strong><br />
                    <input type="text" name="rozetkaCompanyName" size="145" value="<?php echo get_option('rozetkaCompanyName', $company_name); ?>" />
                </p>
				<p><strong>Добавлять в XML файл только выбранные товары (товары которые нужно добавлять отмечаются на странице товара):</strong><br />
					<input type="checkbox" name="onlyRozetkaChecked"  value="1" <?php checked( get_option('onlyRozetkaChecked') ); ?>" />
				</p>
				<p><strong>Включить автоматическую генерацию YML(Cron):</strong><br />
					<input type="checkbox" name="rozetkaYmlCron"  value="1" <?php checked( get_option('rozetkaYmlCron') ); ?>" />
				</p>
				<p><input type="submit" name="Submit" value="Сохранить" /></p>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="rozetkaShopName,rozetkaCompanyName,onlyRozetkaChecked,rozetkaYmlCron" />
			</form>
			<p>YML файл каталога находится по ссылке: <a href="<?php echo home_url().'/rozetka.xml';?> " target="_blank"><?php echo home_url().'/rozetka.xml';?> </a></p>
			<?php if(get_option('rozetkaYmlCron')==1){
						echo '<p>Следующая генерация YML файла произойдет: '.  get_date_from_gmt( date("Y-m-d H:i:s",wp_next_scheduled('woocommerce_rozetka_xml_refresh')) ).' </p>';
					}else{
						echo '<p style="color:red">Генерация YML файла выключена </p>';
						echo 'Чтобы включить, нажмите галочку "Включить автоматическую генерацию" ';
					} 
			?>
		</div>
		<?php
}

/**********************
Add field (is_rozetka_add) for settings
 *******/

add_action( 'woocommerce_product_options_general_product_data', 'wc_custom_add_custom_fields_is_rozetka_add' );
function wc_custom_add_custom_fields_is_rozetka_add() {
    global $post;

    $input_checkbox = get_post_meta( $post->ID, 'is_rozetka_add', true );
    if( empty( $input_checkbox ) ) $input_checkbox = '';

    woocommerce_wp_checkbox(array(
        'id'            => 'is_rozetka_add',
        'label'         => __('Добавить в каталог Rozetka', 'woocommerce' ),
        'description'   => __( 'Добавить в файл импорта товаров для Rozetka marketplace', 'woocommerce' ),
        'value'         => $input_checkbox,
    ));
}

add_action( 'woocommerce_process_product_meta', 'wc_custom_save_is_rozetka_add' );
function wc_custom_save_is_rozetka_add($post_id) {
    $_custom_text_option = isset( $_POST['is_rozetka_add'] ) ? 'true' : '';
    update_post_meta( $post_id, 'is_rozetka_add', $_custom_text_option );
}

/**********************
###Add field (is_rozetka_add) for settings
 *******/
function create_woocommerce_brand_taxonomies() {
    // Add new taxonomy, make it hierarchical (like categories)
    $shop_page_id = woocommerce_get_page_id('shop');

    $base_slug = $shop_page_id > 0 && get_page($shop_page_id) ? get_page_uri($shop_page_id) : 'shop';

    $category_base = get_option('woocommerce_prepend_shop_page_to_urls') == "yes" ? trailingslashit($base_slug) : '';

    $cap = version_compare(WOOCOMMERCE_VERSION, '2.0', '<') ? 'manage_woocommerce_products' : 'edit_products';
    $labels = array(
        'name' => __('Brands', 'woocommerce-brands'),
        'singular_name' => __('Brands', 'woocommerce-brands'),
        'search_items' => __('Search Genres', 'woocommerce-brands'),
        'all_items' => __('All Brands', 'woocommerce-brands'),
        'parent_item' => __('Parent Brands', 'woocommerce-brands'),
        'parent_item_colon' => __('Parent Brands:', 'woocommerce-brands'),
        'edit_item' => __('Edit Brands', 'woocommerce-brands'),
        'update_item' => __('Update Brands', 'woocommerce-brands'),
        'add_new_item' => __('Add New Brands', 'woocommerce-brands'),
        'new_item_name' => __('New Brands Name', 'woocommerce-brands'),
        'menu_name' => 'Brand',
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

add_action('woocommerce_init', 'woocommerce_loaded');

function woocommerce_loaded() {
    create_woocommerce_brand_taxonomies();
}

?>