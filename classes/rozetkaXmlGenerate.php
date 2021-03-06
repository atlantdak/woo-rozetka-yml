<?php


class rozetkaXMLGenerate{

    public function __construct(){

        $this->rozetkaXMLGenerate();

    }

    public function rozetkaXMLGenerate(){
        $dom = $this->xmlGenerator();
        $this->save($dom);
    }

    //Получаю список категорий, которые указаны на странице настройки плагина.
    private function getProductQueryCategoriesIsSelected(){

        $listOfCategories = array();

        $args = array(
            'taxonomy'   => "product_cat",
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => true,
        );
        //get all woocomerce category
        $categories = get_terms($args);

        foreach($categories as $category){
            $category_name = 'rozetkaxml_cat_'.$category->slug;
            if( get_option($category_name) == '1' ){
                array_push($listOfCategories, $category->slug);
            }
        }

        return $listOfCategories;
    }

    private function getProductQueryArgs(){
        /* получаю данные для запроса, всех товаров или выборочных, в зависимости от опций*/
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1
        );

        $args['tax_query'] = array(
            array(
                'taxonomy'      => 'product_cat',
                'field'         => 'slug', //This is optional, as it defaults to 'term_id'
                'terms'         => $this->getProductQueryCategoriesIsSelected(),
                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
            )
        );

        /*Если в настройках отмечена галочка для добавления "только выбранных" товаров**/
        if( get_option('onlyRozetkaChecked') ){
            $args['meta_query'] = array(
                array(
                    'key' => 'is_rozetka_add',
                    'value' => 'yes'
                ));
        }
        return $args;
    }

    private function productQuery(){
        $product_cat = array();
        /* получаю данные для запроса, всех товаров или выборочных, в зависимости от опций*/
        $args = $this->getProductQueryArgs();
        $query = new WP_Query;
        /* Достаю товары каталога для дальнейшей обработки материала */
        $productQuery['product'] = $query->query($args);

        foreach ($productQuery['product'] as $product){
            if( $product_terms = get_the_terms($product->ID, 'product_cat') ){
                foreach ($product_terms as $term) {
                    $product_cat_id = $term->term_id;
                    break;
                }
                array_push($product_cat, $product_cat_id);
            }
        }
        $product_cat = array_unique($product_cat);
        $product_cat_string = implode(",", $product_cat);

        $productQuery['product_cat'] = $product_cat_string;

        return $productQuery;
    }

    private function getProductPrice( $product_id ){

        $product_obj = wc_get_product( $product_id );

        // если вариативный товар
        if( $product_obj->has_child() ){
            if( $rozetka_variable_price = get_post_meta( $product_id, 'rozetka_variable_price', true ) ){
                $product_price = $rozetka_variable_price;
            }else{
                $product_price = $product_obj->get_price();
            }
        }else{
            $product_price = $product_obj->get_price();
        }

        $product_price = intval(str_replace(" ","",$product_price)); // Из цены удаляю пробел, а потом преобразую в целое число.

        return $product_price;
    }

    private function getAvailableStatus( $product_obj ){
        $status = $product_obj->is_in_stock();
        if( $status ){
            $status = 'true';
        }else{
            $status = 'false';
        }

        return $status;
    }

    private function getStockQuantity( $product_obj ){

        if( ! $stock_quantity = $product_obj->get_stock_quantity() ){
            $stock_quantity = '9999';
        }

        return $stock_quantity;
    }

    private function getVendor( $product_obj ){
        $terms = get_the_terms( $product_obj->id, 'product_brand' );

        if ( $terms && ! is_wp_error( $terms ) ) {
            $vendor = $terms[0]->name;
        }else{
            $vendor = '';
        }

        return $vendor;
    }

    private function getDescription( $product_obj ){

        if( get_option('rozetkaShortDescription') ){
            $description = $product_obj->get_short_description() . $product_obj->get_description();
        }else{
            $description = $product_obj->get_description();
        }
        $description = do_shortcode($description);
        return $description;
    }

    private function getAttributeVisibleStatus( $attribute_name ){
        // converte attribute nama to option name
        $attribute_name = 'rozetkaxml_attr_'.$attribute_name;
        $visible_status = false;
        //if 1 then is not add to xml file
        if( get_option($attribute_name) == '1' ){
            $visible_status =  false;
        }else{
            $visible_status = true;
        }

        return $visible_status;
    }

    private function xmlGenerator(){
        /* Достаю все товары каталога для дальнейшей обработки материала */
        $productQuery = $this->productQuery();
        /* */

        //Создает XML-строку и XML-документ при помощи DOM
        $dom = new DomDocument('1.0', "utf-8");
        //Add <!Doctype>
        $implementation = new DOMImplementation();
        $dom->appendChild($implementation->createDocumentType('yml_catalog SYSTEM "shops.dtd"'));

        //добавление корня - <yml_catalog>
        $yml_catalog = $dom->appendChild($dom->createElement('yml_catalog'));

        $att = $dom->createAttribute('date');
        $att->value = date("Y-m-d")." ".date("H:i");
        $yml_catalog->appendChild($att);
        $dom->appendChild($yml_catalog);


        //добавление элемента <shop> в <yml_catalog>
        $shop = $yml_catalog->appendChild($dom->createElement('shop'));

        // добавление элемента <name> в <shop>
        $name = $shop->appendChild($dom->createElement('name'));

        // добавление элемента текстового узла <name> в <shop>
        $name->appendChild(
            $dom->createTextNode( htmlspecialchars(get_option('rozetkaShopName'),ENT_QUOTES) ));

        // добавление элемента <company> в <shop>
        $company = $shop->appendChild($dom->createElement('company'));

        // добавление элемента текстового узла <company> в <shop>
        $company->appendChild(
            $dom->createTextNode( htmlspecialchars(get_option('rozetkaCompanyName'),ENT_QUOTES) ));

        // добавление элемента <url> в <shop>
        $url = $shop->appendChild($dom->createElement('url'));

        // добавление элемента текстового узла <url> в <shop>
        $url->appendChild(
            $dom->createTextNode( get_bloginfo('url') ));

        // добавление элемента <currencies> в <shop>
        $currencies = $shop->appendChild($dom->createElement('currencies'));
        // добавление элемента <currency> в <currencies>
        $currency = $currencies->appendChild($dom->createElement('currency'));

        $id = $dom->createAttribute('id');
        $id->value = "UAH";
        $currency->appendChild($id);
        $currencies->appendChild($currency);
        $rate = $dom->createAttribute('rate');
        $rate->value = "1";
        $currency->appendChild($rate);
        $currencies->appendChild($currency);



        // добавление элемента <categories> в <shop>
        $categories = $shop->appendChild($dom->createElement('categories'));


        $args = array(
            'number'    => '0',
            'taxonomy'  => 'product_cat',
            'include'   =>  $productQuery['product_cat'],
        );

        $catlist = get_categories($args);

        foreach ($catlist as $categories_item) {
            $category = $categories->appendChild($dom->createElement('category'));
            $id = $dom->createAttribute('id');
            $id->value = $categories_item->cat_ID;
            $category->appendChild($id);
            $categories->appendChild($category);
            if(!$categories_item->parent){
                $category->appendChild(
                    $dom->createTextNode(htmlspecialchars($categories_item->cat_name,ENT_QUOTES)));
            }
            if($categories_item->parent){
                $parentId = $dom->createAttribute('parentId');
                $parentId->value = $categories_item->parent;
                $category->appendChild($parentId);
                $categories->appendChild($category);
                $category->appendChild(
                    $dom->createTextNode(htmlspecialchars($categories_item->cat_name,ENT_QUOTES)));
            }

        }


        // добавление элемента <delivery-options> в <shop>
        $delivery_options = $shop->appendChild($dom->createElement('delivery-options'));

        // добавление элемента <option> в <delivery-options>
        $option = $delivery_options->appendChild($dom->createElement('option'));
        $cost = $dom->createAttribute('cost');
        $cost->value = "0";
        $option->appendChild($cost);
        $delivery_options->appendChild($option);

        // добавление элемента <offers> в <shop>
        $offers = $shop->appendChild($dom->createElement('offers'));



        foreach( $productQuery['product'] as $product ){
            $product_obj = wc_get_product( $product->ID );
            $product_price = $this->getProductPrice($product->ID);
            //		echo '<p>'. $product->post_title .' '.$product->post_content.'</p>';
            // добавление элемента <offer> в <offers>
            $offer = $offers->appendChild($dom->createElement('offer'));
            $id = $dom->createAttribute('id');
            $id->value = $product->ID;
            $available = $dom->createAttribute('available');
            $available->value = $this->getAvailableStatus( $product_obj );
            $offer->appendChild($id);
            $offer->appendChild($available);
            //	  $offers->appendChild($offer);

            $url = $offer->appendChild($dom->createElement('url'));
            $url->appendChild(
                $dom->createTextNode(get_permalink($product->ID)));
            $price = $offer->appendChild($dom->createElement('price'));
            $price->appendChild(
                $dom->createTextNode($product_price));
            $currencyId = $offer->appendChild($dom->createElement('currencyId'));
            $currencyId->appendChild(
                $dom->createTextNode('UAH'));

            /* Получаю ID категории по ID поста*/
            $term = get_the_terms($product->ID, 'product_cat');

            $categoryId = $offer->appendChild($dom->createElement('categoryId'));
            $categoryId->appendChild(
                $dom->createTextNode($term[0]->term_id));

            /*	 Закоментил, т.к. у нас непонятно в какую категорию должен попасть товар
                $market_category = $offer->appendChild($dom->createElement('market_category'));
                    $market_category->appendChild(
                            $dom->createTextNode('Все товары/Дом и дача/Строительство и ремонт/Сантехника и водоснабжение/Водяные насосы'));
            */

            /*Если есть картинка, вывожу ее в YML */
            if(get_the_post_thumbnail_url( $product->ID, 'large' )){
                $picture = $offer->appendChild($dom->createElement('picture'));
                $picture->appendChild(
                    $dom->createTextNode(get_the_post_thumbnail_url( $product->ID, 'large' )));
            }

            $stock_quantity = $offer->appendChild($dom->createElement('stock_quantity'));
            $stock_quantity->appendChild(
                $dom->createTextNode( $this->getStockQuantity( $product_obj ) ));

            $vendor = $offer->appendChild($dom->createElement('vendor'));
            $vendor->appendChild(
                $dom->createTextNode( $this->getVendor( $product_obj ) ));

            $name = $offer->appendChild($dom->createElement('name'));
            $name->appendChild(
                $dom->createTextNode(htmlspecialchars( $product_obj->get_name(),ENT_QUOTES)));

            $description = $offer->appendChild($dom->createElement('description'));

            $description->appendChild(
                $dom->createCDATASection( $this->getDescription( $product_obj ) ) );


            //Доббавляю атрибуты(свойства) товара
            $product_attributes = $product_obj->get_attributes();
            foreach ($product_attributes as $attribute){
                //get data for param
                $attribute_title = wc_attribute_label($attribute['name']);

                // if in admin panel on plugin settings not checked this attr then skip attr
                if( $attribute_visible_status = $this->getAttributeVisibleStatus( $attribute_title ) ){
                    if($attribute['variation']) {
                        //get first attribute for variation
                        $attribute_val = get_term_by( 'id', $attribute['options'][0], $attribute['name'] )->name;
                    }else{
                        $attribute_val = get_term_by( 'id', $attribute['options'][0], $attribute['name'] )->name;
                    }

                    $param = $offer->appendChild($dom->createElement('param'));

                    $param_name = $dom->createAttribute('name');
                    $param_name->value = $attribute_title;
                    $param->appendChild($param_name);

                    $param->appendChild(
                        $dom->createTextNode( $attribute_val ) );
                }

            }
        }


        //генерация xml
        $dom->formatOutput = true; // установка атрибута formatOutput
        // domDocument в значение true

        return $dom;
    }

    private function save( $dom ){
        //генерация xml
        $dom->formatOutput = true; // установка атрибута formatOutput
        // domDocument в значение true
        // save XML as string or file
        $test1 = $dom->saveXML(); // передача строки в test1
        $dom->save('rozetka.xml'); // сохранение файла
    }

}
