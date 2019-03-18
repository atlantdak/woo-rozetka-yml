<?php


class rozetkaSettings{

    public function __construct(){

        // Она говорит WP, что нужно вызвать метод "woocommerce_add_admin_menu"
        // когда нужно будет создать страницы меню.
        add_action("admin_menu", array( $this, "woocommerce_add_admin_menu") );

        $this->wc_add_custom_fields_to_product();
    }

    public function woocommerce_add_admin_menu() {
        add_submenu_page('woocommerce',
            'Rozetka YML Setting', 'Rozetka YML Setting', 'manage_options',
            'rozetka-setting-yml-admin', array( $this, 'rozetka_settings_page_tabs') );
    }

    private function get_settings_page_active_tab(){

        //we check if the page is visited by click on the tabs or on the menu button.
        //then we get the active tab.
        $active_tab = "settings";
        if(isset($_GET["tab"]))
        {

            if($_GET["tab"] == "product-list"){
                $active_tab = "product-list";
            }
            if($_GET["tab"] == "cron-options"){
                $active_tab = "cron-options";
            }
            if($_GET["tab"] == "product_attribute-options"){
                $active_tab = "product_attribute-options";
            }
            if($_GET["tab"] == "settings"){
                $active_tab = "settings";
            }
        }
        return $active_tab;

    }
    public function rozetka_settings_page_tabs(){

        $active_tab = $this->get_settings_page_active_tab();

        ?>
        <!-- wordpress provides the styling for tabs. -->
        <h2 class="nav-tab-wrapper">
            <!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
            <a href="?page=rozetka-setting-yml-admin&tab=settings" class="nav-tab <?php if($active_tab == 'settings'){echo 'nav-tab-active';} ?>"><?php _e('Settings', 'woocommerce'); ?></a>
            <a href="?page=rozetka-setting-yml-admin&tab=product-list" class="nav-tab <?php if($active_tab == 'product-list'){echo 'nav-tab-active';} ?>"><?php _e('Product list', 'woocommerce'); ?></a>
            <a href="?page=rozetka-setting-yml-admin&tab=cron-options" class="nav-tab <?php if($active_tab == 'cron-options'){echo 'nav-tab-active';} ?> "><?php _e('Cron Options', 'woocommerce'); ?></a>
            <a href="?page=rozetka-setting-yml-admin&tab=product_attribute-options" class="nav-tab <?php if($active_tab == 'product_attribute-options'){echo 'nav-tab-active';} ?> "><?php _e('Product attribute', 'woocommerce'); ?></a>
        </h2>
        <?php

        $this->woocommerce_settings_page_content( $active_tab );

    }

    private function woocommerce_settings_page_content( $active_tab ) {

        // проверяем, что пользователь может обновлять настройки
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }     ?>
        <div class="wrap">
            <?php
            if( $active_tab == 'settings' ) {
                $this->settingsForm();
            }
            if( $active_tab == 'cron-options' ){
                ?>
                <p>YML файл каталога находится по ссылке: <a href="<?php echo $this->getUrlToFileRozetkaXML();?> " target="_blank"><?php echo $this->getUrlToFileRozetkaXML(); ?> </a></p>
                <?php
                $this->nextCronGenerateXML();
            }

            if( $active_tab == 'product-list' ){
                echo $this->listOfProductRozetkaXML();
                echo $this->getNotification();
            }

            if( $active_tab == 'product_attribute-options' ){
                $this->attributesForm();
            }

            ?>
        </div>
        <?php
    }

    /**********************
    Add field (is_rozetka_add) for settings
     *******/
    public function wc_add_custom_fields_to_product(){

        add_action( 'woocommerce_process_product_meta', array( $this, 'wc_custom_save_is_rozetka_add' ) );
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'wc_custom_add_custom_fields_is_rozetka_add' ) );
        add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'wc_custom_add_custom_fields_is_rozetka_add' ) );

        add_action( 'woocommerce_process_product_meta', array( $this, 'wc_custom_save_rozetka_variable_price' ) );
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'wc_custom_add_custom_fields_rozetka_variable_price' ) );
        add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'wc_custom_add_custom_fields_rozetka_variable_price' ) );

    }
    public function wc_custom_add_custom_fields_rozetka_variable_price() {
        global $post;
        $_product = wc_get_product($post->ID);
        if($_product->is_type('variable') ) {
            $rozetka_variable_price = get_post_meta($post->ID, 'rozetka_variable_price', true);
            if (empty($rozetka_variable_price)) {
                $rozetka_variable_price = $_product->get_price();
            }

            woocommerce_wp_text_input(array(
                'id' => 'rozetka_variable_price',
                'label' => __('Цена для Rozetka', 'woocommerce'),
                'description' => __('Эта цена будет отображатся в Rozetka XML', 'woocommerce'),
                'value' => $rozetka_variable_price,
            ));
        }
    }

    public function wc_custom_save_rozetka_variable_price($post_id) {
        update_post_meta( $post_id, 'rozetka_variable_price', $_POST['rozetka_variable_price'] );
    }

    public function wc_custom_add_custom_fields_is_rozetka_add() {
        global $post;

        $input_checkbox = get_post_meta( $post->ID, 'is_rozetka_add', true );
        if( empty( $input_checkbox ) ) $input_checkbox = '';

        woocommerce_wp_checkbox(array(
            'id'            => 'is_rozetka_add',
            'label'         => __('Добавить в каталог Rozetka', 'woocommerce' ),
            'description'   => __( 'Добавить в файл импорта товаров для Rozetka marketplace', 'woocommerce' ),
            //'value'         => $input_checkbox,
        ));

    }


    public function wc_custom_save_is_rozetka_add($post_id) {

        //update_post_meta( $post_id, 'is_rozetka_add', $_custom_text_option );

        if( isset($_POST['is_rozetka_add']) ){
            update_post_meta($post_id, "is_rozetka_add", $_POST['is_rozetka_add'] );
        }else{
            delete_post_meta($post_id, "is_rozetka_add");
        }
    }
    /**********************
    ###Add field (is_rozetka_add) for settings
     *******/

    private function attributesForm(){
        $listOfAttributes = $this->listOfAttributes();
        ?>
        <h2>Настройка создания Goods Catalog YML </h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options') ?>
            <p><strong>Укажите атрибуты, которые не нужно добавлять в файл Rozekta.xml</strong></p>
            <div class="attributes-select">
                <?php echo $listOfAttributes['form_of_attributes']; ?>
            </div>
            <p><input type="submit" class="button-primary" name="Submit" value="Сохранить" /></p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="<?php echo $listOfAttributes['list_of_attributes']; ?>" />
        </form>
        <?php
    }

    private function settingsForm(){
        ?>
        <h2>Настройка создания Goods Catalog YML </h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options') ?>
            <p><strong>Название магазина:</strong><br />
                <?php
                $shop_name = get_bloginfo('name');
                $company_name = get_bloginfo('description');
                ?>
                <input type="text" name="rozetkaShopName" size="145" value="<?php echo get_option('rozetkaShopName', $shop_name); ?>" />
            </p>
            <p><strong>Название компании:</strong><br />
                <input type="text" name="rozetkaCompanyName" size="145" value="<?php echo get_option('rozetkaCompanyName', $company_name); ?>" />
            </p>
            <p><strong>Добавлять в XML файл только выбранные товары (товары которые нужно добавлять отмечаются на странице товара):</strong><br />
                <input type="checkbox" name="onlyRozetkaChecked"  value="1" <?php checked( get_option('onlyRozetkaChecked') ); ?> />
            </p>
            <p><strong>Добавлять в XML файл "Короткое описание товара":</strong><br />
                <input type="checkbox" name="rozetkaShortDescription"  value="1" <?php checked( get_option('rozetkaShortDescription') ); ?> />
            </p>
            <p><strong>Включить автоматическую генерацию YML(Cron):</strong><br />
                <input type="checkbox" name="rozetkaYmlCron"  value="1" <?php checked( get_option('rozetkaYmlCron') ); ?> />
            </p>

            <p><input type="submit" class="button-primary" name="Submit" value="Сохранить" /></p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="rozetkaShopName,rozetkaCompanyName,onlyRozetkaChecked,rozetkaShortDescription,rozetkaYmlCron" />
        </form>
        <?php
    }

    private function nextCronGenerateXML(){

        if(get_option('rozetkaYmlCron')==1){
            echo '<p>Следующая генерация YML файла произойдет: '.  get_date_from_gmt( date("Y-m-d H:i:s",wp_next_scheduled('woocommerce_rozetka_xml_refresh')) ).' </p>';
        }else{
            echo '<p style="color:red">Генерация YML файла выключена </p>';
            echo 'Чтобы включить, нажмите галочку "Включить автоматическую генерацию" ';
        }

    }

    private function listOfAttributes(){
        $listOfAttributes['form_of_attributes'] = '';
        $listOfAttributes['list_of_attributes'] = array();
        $taxonomies = wc_get_attribute_taxonomies();
        foreach($taxonomies as $taxonomy){
            //generate name for wp_options that name will check in generator
            $attribute_name = 'rozetkaxml_attr_'.$taxonomy->attribute_name;
            $listOfAttributes['form_of_attributes'] .= '<label for="'.$attribute_name.'"><input type="checkbox" id="'.$attribute_name.'" name="'.$attribute_name.'" value="1" '.checked( get_option($attribute_name),true , false ).'> '.$taxonomy->attribute_label.'</label>';
            array_push($listOfAttributes['list_of_attributes'], $attribute_name) ;
        }
        //translate array to string format
        $listOfAttributes['list_of_attributes'] = implode(",", $listOfAttributes['list_of_attributes']);

        return $listOfAttributes;
    }

    private function listOfProductRozetkaXML(){

        $list_of_product = '';

        if (file_exists('rozetka.xml')) {
            $xml = simplexml_load_file( $this->getUrlToFileRozetkaXML() );

            if( $products_from_xml = $xml->shop->offers ){
                $list_of_product .= '<p>Список товаров добавленных в файл rozetka.xml:</p>';
                $list_of_product .= '<ol style="height: 600px; overflow: scroll; background: gainsboro; padding: 20px 60px; margin: 0;">';
                foreach ( $products_from_xml->offer as $key => $product){
                    $product_url = $product->url[0];
                    $product_url_edit = get_edit_post_link( (int)$product->attributes()->id[0] );
                    $product_name = $product->name[0];
                    $list_of_product .= '<li><a href="'.$product_url.'" target="_blank">'.$product_name.'</a> <a href="' . $product_url_edit . '" target="_blank">(Изменить)</a></li>';
                }
                $list_of_product .= '</ul>';
            }


        } else {
            $list_of_product = 'Не удалось открыть файл rozetka.xml.';
        }

        return $list_of_product;
    }

    private function getUrlToFileRozetkaXML(){
        return home_url().'/rozetka.xml';
    }

    private function getNotification(){
        return '<p>Не забудьте оформить все товары согласно требованиям Rozetka marketplace. Заполните графу "Brand" и другие свойства товаров.</p>';
    }
}


$rozetkaSettings = new rozetkaSettings();


?>