<?php

namespace  App\ParseIt\export;

class openCart{
    public $prefix = 'oc_';
    public $lang = 'ru';
    public $db;

    public function updProduct( $data , $product_id )
    {
        if( !( isset($data['product_name']) && isset($data['product_id']) && isset($data['category_id']) ) ) {
            return false;
        }
        $model = $this->getModelByName( $data['product_name'] );
        $product = array(
            'model' => "$model",
            'quantity' => $data['quantity'],
            'stock_status_id' => $data['stock_status_id'],
            'price' => $data['price'],
            'price_zak' => $data['price_purchase'],
            'sort_order' => '1',
            'sku' => $data['sku'],
            'status' => $data['stock_status_id'] === 5 ? 0 : 1
        );

        $this->update( $this->prefix."product", $product, array( 'product_id' => $product_id ) );
        $sql = "UPDATE ".$this->prefix."product SET date_available=CURRENT_DATE WHERE product_id=".$product_id;
        $this->db->sql($sql);
		
        $name = str_replace( '"', '\'', $data['product_name'] );
		$name = str_replace( '&quot;', '\'', $name );
        /*
        //eng
        $product_description = array(
            'product_id' => $product_id,
            'language_id' => '2',
            'name' => $name,
            'description' => htmlspecialchars( $param['description'] )
        );
        $this->insert( $this->prefix."product_description", $product_description );
         *
         */
        //ru
        $name = str_replace( '"', '\'', $name );
        $product_description = array(
            'product_id' => $product_id,
            'language_id' => '1',
            'name' => $name,
            'description' => htmlspecialchars( $data['description'] )
        );
        $this->update( $this->prefix."product_description", $product_description, array( 'product_id' => $product_id ) );

        return $product_id;
    }

    public function addProduct( $data )
    {
        if( !( isset($data['product_name']) && isset($data['product_id']) && isset($data['category_id']) ) ) {
            return false;
        }

        $model = $this->getModelByName( $data['product_name'] );

        $data['main_image'] ? $image = $this->uploadImage($data['main_image']) : $image = '';

        !empty($data['manufacturer']) ? $manufacturer_id = $this->addManufacturer($data['manufacturer']) : $manufacturer_id = 0;

        $product = array(
            'model' => "$model",
            'quantity' => $data['quantity'],
            'stock_status_id' => $data['stock_status_id'],
            'image' => $image,
            'price' => $data['price'],
            'price_zak' => $data['price_purchase'],
            'sort_order' => '1',
            'sku' => $data['sku'],
            'status' => $data['stock_status_id'] === 5 ? 0 : 1,
            'manufacturer_id' => $manufacturer_id
        );
        
        $this->insert( $this->prefix."product", $product );

        $product_id = $this->db->insert_id();

        if( empty($product_id) ) return false;

        $this->update('pi_data_set', array('product_id' => $product_id), array('id' => $data['id']));

        $sql = "UPDATE ".$this->prefix."product SET date_available=CURRENT_DATE WHERE product_id=".$product_id;
        $this->db->sql($sql);
        
        $name = str_replace( '"', '\'', $data['product_name'] );
        /*
        //eng
        $name = str_replace( '&quot;', '\'', $name );
        $product_description = array(
            'product_id' => $product_id,
            'language_id' => '2',
            'name' => $name,
            'description' => htmlspecialchars( $param['description'] )
        );
        $this->insert( $this->prefix."product_description", $product_description );
         *
         */
        //ru
        $name = str_replace( '"', '\'', $name );
        $product_description = array(
            'product_id' => $product_id,
            'language_id' => '1',
            'name' => $name,
            'description' => htmlspecialchars( $data['description'] )
        );
        $this->insert( $this->prefix."product_description", $product_description );
        $this->insert( $this->prefix.'product_to_store', array('product_id' => $product_id, 'store_id'=>0) );
        
        return $product_id;
    }

    public function addCatetgory( $category, $parent_id = 0 )
    {
        $category['name'] = preg_replace( '%\&amp\;%', '&', $category['name'] );
        
        $image = '';
        if($category['image']!=''){
            $category['image'] = preg_replace('%^\/\/%uis', 'http://', $category['image'] );
            $path = parse_url($category['image']);
            $path = $path['path'];
            $image = 'data'.$path;
            $fn = DIRECTORY_SEPARATOR.'image'.DIRECTORY_SEPARATOR.$image;
            $fn = $this->fix_filename($fn);
            //$file = 
                    $this->get_file( $category['image'], $fn );
            //if( $file != FALSE ){
                //sleep($this->sleep_get);
                //file_put_contents($fn, $file);
            //}
        }
        $query = "SELECT d.category_id FROM oc_category_description d, oc_category c WHERE d.name = '{$this->db->escape($category['name'])}' AND d.category_id = c.category_id AND c.parent_id = {$parent_id}";
//        $category_id = $this->db->sql2val("SELECT category_id FROM ".$this->prefix."category_description"." WHERE name = '' AND ");
        $category_id = $this->db->sql2val($query);

        if(is_array($category_id)) return $category_id[0];
        if( $category_id > 0 ) return $category_id;
        
        $cat = array(
            'parent_id' => $parent_id,
            'image' => $image,
            'top' => 1,
            'sort_order' => 0,
            'status' => 1,
            'date_added' => time()
        );
        $this->insert( $this->prefix."category", $cat );
        $category_id = $this->db->insert_id();
        /*
        //eng
        $cat = array(
            'category_id' => $category_id,
            'language_id' => '2',
            'name' => $category['name'],
            'description' => $category['description']
        );
        $this->insert( $this->prefix."category_description", $cat );
         * 
         */
        
        //ru
        $cat = array(
            'category_id' => $category_id,
            'language_id' => '1',
            'name' =>$category['name'],
            'description' => $category['description']
        );
        $this->insert( $this->prefix."category_description", $cat );
        
        //oc_category_to_store
        $cat = array(
            'category_id' => $category_id
        );
        $this->insert( $this->prefix."category_to_store", $cat );
        
        return $category_id;
    }

    public function addProductToCatetgory( $product_id, $category_id, $main = 0 )
    {
        $this->insert( $this->prefix."product_to_category", array('product_id'=>$product_id, 'category_id'=>$category_id, 'main_category'=>$main) );
    }

    public function addAttribute( $attribute_group_id, $name, $sort )
    {
        $attribute_id = $this->db->sql2val("SELECT a.attribute_id FROM ".$this->prefix."attribute_description a, ".$this->prefix."attribute b "." WHERE b.attribute_id = a.attribute_id AND b.attribute_group_id=$attribute_group_id AND a.name = '{$this->db->escape($name)}' GROUP BY a.attribute_id");
        if(is_array($attribute_id)) return $attribute_id[0];
        if( $attribute_id > 0 ) return $attribute_id;
        
        $this->insert( $this->prefix."attribute", array('sort_order'=>$sort, 'attribute_group_id'=>$attribute_group_id ) );
        $attribute_id = $this->db->insert_id();
        //ru desc
        $this->insert( $this->prefix."attribute_description", array('attribute_id'=>$attribute_id, 'language_id'=> 1, 'name'=>$name) );
        //eng desc
        //$this->insert( $this->prefix."attribute_description", array('attribute_id'=>$attribute_id, 'language_id'=> 2, 'name'=>$name) );
        
        return $attribute_id;
    }

    public function addAttributeGroup( $name , $sort )
    {
        $attribute_group_id = $this->db->sql2val("SELECT attribute_group_id FROM ".$this->prefix."attribute_group_description"." WHERE name = '{$this->db->escape($name)}'");
        if(is_array($attribute_group_id)) return $attribute_group_id[0];
        if( $attribute_group_id > 0 ) return $attribute_group_id;
        
        $this->insert( $this->prefix."attribute_group", array('sort_order'=>$sort) );
        $attribute_group_id = $this->db->insert_id();        
        //ru desc
        $this->insert( $this->prefix."attribute_group_description", array('attribute_group_id'=>$attribute_group_id, 'language_id'=> 1, 'name'=>$name) );
        //eng desc
        //$this->insert( $this->prefix."attribute_group_description", array('attribute_group_id'=>$attribute_group_id, 'language_id'=> 2, 'name'=>$name) );
        
        return $attribute_group_id;
    }

    public function addProductAttribute( $product_id, $attribute_id, $text )
    {
        $find = $this->db->sql2val("SELECT attribute_id FROM ".$this->prefix."product_attribute"." WHERE product_id = $product_id AND attribute_id = $attribute_id AND text = '{$this->db->escape($text)}'");
        if(is_array($find)) return;
        if( $find > 0 ) return;
        //ru desc
        $this->insert( $this->prefix."product_attribute", array('attribute_id'=>$attribute_id, 'product_id'=>$product_id, 'language_id'=> 1, 'text'=>$text) );
        //eng desc
        //$this->insert( $this->prefix."product_attribute", array('attribute_id'=>$attribute_id, 'product_id'=>$product_id, 'language_id'=> 2, 'text'=>$text) );
    }

    public function addProductMainImage( $product_id, $image )
    {
        $this->update( $this->prefix."product", array('image'=>$image), array( 'product_id'=>$product_id) );
    }

    public function addProductImage( $product_id, $image )
    {
        $this->insert( $this->prefix."product_image", array('image'=>$image, 'product_id'=>$product_id) );
    }

    public function update( $table, $param, $where )
    {
        $wh = '';
        foreach ( $where as $k => $v ) {
            $wh .= "$k= "."'".$this->db->escape($v)."' AND ";
        }
        $wh = substr( $wh, 0, -4 );
        
        $update = '';
        foreach ( $param as $k => $v ) {
            $update .= "$k = "."'".$this->db->escape($v)."',";
        }
        $update = substr($update, 0, -1);
        //die("UPDATE $table SET $update WHERE $wh");
        $this->db->sql( "UPDATE $table SET $update WHERE $wh" );
    }

    public function insert( $table, $param )
    {
        $insert_k = '';
        $insert_v = '';
        foreach ( $param as $k => $v ) {
            $insert_k .= " $k,";
            $insert_v .= " '".$this->db->escape($v)."',";
        }
        $insert_k = substr($insert_k, 0, -1);
        $insert_v = substr($insert_v, 0, -1);
        
        $this->db->sql( "INSERT IGNORE INTO $table ($insert_k) VALUES ($insert_v)" );
    }

    public function delete( $table, $where )
    {
        $wh = '';
        foreach ( $where as $k => $v ) {
            $wh .= "$k= "."'".$this->db->escape($v)."' AND ";
        }
        $wh = substr( $wh, 0, -4 );
        
        $this->db->sql( "DELETE FROM $table WHERE $wh" );
    }

    public function getModelByName( $name )
    {
        preg_match('%^(.{1,63})%uis', $name, $result);
        return $result[1];
    }

    public function getPriceWithProcent( $price )
    {
        $procent = (float)$this->getProcent();
        return $price+($price/100*$procent);
    }

    public function getProcent(  )
    {
        $value = $this->db->sql2val( "SELECT value FROM ".PREFIX."settings WHERE name='procent_price'" );
        return $value;
    }

    public function setProcent( $procent )
    {
        $procent = (float)$procent;
        $this->update(PREFIX."settings", array('value'=>$procent), array('name'=>'procent_price'));
    }

    public function translateWithTags( $html )
    {
        preg_match_all( '%(>|^|\.)([^<$\.]+)%uis', $html, $result, PREG_SET_ORDER );
        $translate_html = $html;
        foreach ( $result as $str ) {
            $translate_html = str_replace( $str[2], $this->translate($str[2]), $translate_html );
        }

        return $translate_html;
    }

    public function fix_filename( $fn )
    {
            $fn = $_SERVER['DOCUMENT_ROOT'] . $fn;
            if( DIRECTORY_SEPARATOR === '\\' ) {
                $fn = str_replace('\\', '/', $fn);
            }
            $dir = pathinfo($fn, PATHINFO_DIRNAME);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, TRUE);
            }
            return $fn;
    }

    public function getDataByCategoryId($category_id)
    {
        $rows = $this->db->sql2array("SELECT * FROM parser_data WHERE category_id='{$category_id}' AND status = 0");
        return $rows;
    }

    public function getAllRdyData()
    {
        $rows = $this->db->sql2array("SELECT * FROM parser_data WHERE status = 0");
        return $rows;
    }

    public function getCategoryRdyToExport()
    {
		$rows = $this->db->sql2array("SELECT * FROM oc_category WHERE export_rdy=1");
        return $rows;
    }

    public function findProductIdByDataId($data_id)
    {
        $data_id = (int)$data_id;
        $pid = $this->db->sql2val("SELECT product_id FROM oc_product WHERE parser_data_id={$data_id}");
        return $pid;
    }

    public function findProduct($product_id)
    {
        $product_id = (int)$product_id;
        $pid = $this->db->sql2val("SELECT product_id FROM oc_product WHERE `product_id`={$product_id} LIMIT 1");
        if ( empty($pid) )
        {
            return false;
        }
        return $pid;
    }

    public function productInsert( $data )
    {
        //product tables
        $product_id = $this->addProduct( $data );
		if (!$product_id)
			return;
        //product tables

        //category tables
        $data['categories'] = [];
        if( !empty($data['categories']) ) {
            $this->delete($this->prefix . "product_to_category", array('product_id' => $product_id));// убираем товар из всех категорий, будем добавлять по новой
            $cats = [];
            $category_id = 0;
            $data['categories'] = unserialize( $data['categories'] );
            foreach ($data['categories'] as $k => $value) {
                // создание категорий
                $cat = [
                    'name' => $value,
                    'image' => '',
                    'description' => ''
                ];
                if (!$k) {
                    $category_id = $this->addCatetgory($cat);// first cat - parent_id = 0, root
                } else {
                    $category_id = $this->addCatetgory($cat, $category_id);
                }
                // добавление товара в категорию
                if (count($data['categories']) - 1 === $k) {
                    $this->addProductToCatetgory($product_id, $category_id, 1);//last cat - main cat
                } else {
                    if( $k != 0 )
                    {
                        $this->addProductToCatetgory($product_id, $category_id);
                    }
                }
                //прописываем пути категорий, необходимо для выпадающих списков в админ панеле
                $cats[] = $category_id;
                //oc_category_path
                foreach ($cats as $key => $val) {
                    $this->insert($this->prefix . 'category_path', array('category_id' => $category_id, 'path_id' => $val, 'level' => $key));
                }
            }
        } elseif( !empty($data['category_id']) ) {
            $categories = $this->getParents($data['category_id']);
            $last_k = count($categories)-1;
            foreach ($categories as $k => $category_id) {
                // добавление товара в категорию
                if ($k == 0) {
                    $this->addProductToCatetgory($product_id, $category_id, 1);//last cat - main cat
                } else {
                    if( $k != $last_k )
                    {
                        $this->addProductToCatetgory($product_id, $category_id);
                    }
                }
            }
        }
        //category tables

        //atribute tables
        if( !empty($data['product_attributes']) )
        {
            $data['product_attributes'] = unserialize($data['product_attributes']);
            foreach ( $data['product_attributes'] as $sort => $spec ) {
                $attribute_group_id = $this->addAttributeGroup( $spec['atr_group_mame'], $sort );
                foreach ( $spec['attributes'] as $s => $param ) {
                    $key = key($param);
                    if ( trim($key)==='' )
                        $key = '   ';
                    $attribute_id = $this->addAttribute( $attribute_group_id, $key, $s );
                    $this->addProductAttribute( $product_id, $attribute_id, $param[$key] );
                }
            }
        }
        //atribute tables

        //special tables
        if( !empty($data['special']) )
        {
            $this->delete( $this->prefix."product_special", array('product_id' => $product_id) );
            $data['special'] = (int)$data['special'];
            $this->addSpecial($product_id, $data['special']);
        }
        //special tables

        //image tables
        if( !empty($data['gallery']) )
        {
            $data['gallery'] = unserialize($data['gallery']);
			if(!empty($data['gallery'])) {
				$this->delete( $this->prefix."product_image", array('product_id' => $product_id) );
				foreach ( $data['gallery'] as $key => $url ) {
					$image = $this->uploadImage($url);
					if(!$image) {
						continue;
					}
					if( $key==0 ) {
						$this->addProductMainImage($product_id, $image);
					} else {
						$this->addProductImage($product_id, $image);
					}
				}
			}
        }
        //image tables

        //related tables
        if( !empty($data['related']) )
        {
            $data['related'] = unserialize($data['related']);
			if(!empty($data['related'])) {
				$this->delete( $this->prefix."product_related", array('product_id' => $product_id) );
				foreach ( $data['related'] as $pid ) {
					$related_id = $this->getRelatedId($pid);
					$related_id > 0 ? $this->insert( $this->prefix."product_related", array('product_id' => $product_id, 'related_id' => $related_id) ):'';
				}
			}
        }
        //related tables
//        $this->update( "pi_data_set", array('status'=> 1), array('id' => $data['id']) );
    }

    public function productUpdate( $data, $product_id )
    {
        //product tables
        $this->updProduct( $data, $product_id );
        //product tables

        //category tables
        $data['categories'] = [];
        if( !empty($data['categories']) ) {
//            $this->delete($this->prefix . "product_to_category", array('product_id' => $product_id));// убираем товар из всех категорий, будем добавлять по новой
            $cats = [];
            $category_id = 0;
            $data['categories'] = unserialize( $data['categories'] );
            foreach ( $data['categories'] as $k => $value ){
                // создание категорий
                $cat = [
                    'name' => $value,
                    'image' => '',
                    'description' => ''
                ];
                if( !$k ) {
                    $category_id = $this->addCatetgory( $cat );// first cat - parent_id = 0, root
                } else {
                    $category_id = $this->addCatetgory( $cat, $category_id );
                }
                // добавление товара в категорию
                if( count($data['categories'])-1 === $k) {
                    $this->addProductToCatetgory($product_id, $category_id, 1);//last cat - main cat
                } else {
                    if( $k != 0 )
                    {
                        $this->addProductToCatetgory($product_id, $category_id);
                    }
                }
                //прописываем пути категорий, необходимо для выпадающих списков в админ панеле
                $cats[] = $category_id;
                //oc_category_path
                foreach ( $cats as $key => $val ) {
                    $this->insert( $this->prefix.'category_path', array('category_id'=>$category_id, 'path_id'=>$val, 'level'=>$key) );
                }
            }
        } elseif( !empty($data['category_id']) ) {
            $categories = $this->getParents($data['category_id']);
            $last_k = count($categories)-1;
            foreach ($categories as $k => $category_id) {
                // добавление товара в категорию
                if ($k == 0) {
                    $this->addProductToCatetgory($product_id, $category_id, 1);//last cat - main cat
                } else {
                    if( $k != $last_k )
                    {
                        $this->addProductToCatetgory($product_id, $category_id);
                    }
                }
            }
        }
        //category tables

        //special tables
        if( !empty($data['special']) ) {
            $this->delete( $this->prefix."product_special", array('product_id' => $product_id) );
            $data['special'] = (int)$data['special'];
            $this->addSpecial($product_id, $data['special']);
        } else {
			$this->delete( $this->prefix."product_special", array('product_id' => $product_id) );
		}
        //special tables

        //related tables
        if( !empty($data['related']) )
        {
            $data['related'] = unserialize($data['related']);
			if(!empty($data['related'])) {
				$this->delete( $this->prefix."product_related", array('product_id' => $product_id) );
				foreach ( $data['related'] as $pid ) {
					$related_id = $this->getRelatedId($pid);
					$related_id > 0 ? $this->insert( $this->prefix."product_related", array('product_id' => $product_id, 'related_id' => $related_id) ):'';
				}
			}
        }
        //related tables

//        $this->update( PREFIX."data", array('status'=> 1), array('id' => $data['id']) );
    }

    public function uploadImage($url)
    {
		if(empty($url))
			return false;
        $path = parse_url($url);
        $path = $path['path'];
        $image = 'catalog'.$path;
		if( $image === 'catalog/')
		    return false;
		$image = preg_replace('#%\d+#is', '', $image);
        $fn = DIRECTORY_SEPARATOR.'image'.DIRECTORY_SEPARATOR.$image;
        $fn = $this->fix_filename($fn);
        if(!file_exists($fn))
        {
            $content = file_get_contents($url);
            file_put_contents($fn, $content);

//            $img = Image::make($fn);
//            $width = $img->width();
//            $watermark = Image::make(base_path().'/../image/nakladka_png_mobzilla.png');
//            $watermark->resize($width, null, function ($constraint) {
//                $constraint->aspectRatio();
//            });
//            $img->insert($watermark, 'center');
//            $img->save($fn);
        }
        return $image;
    }

    public function getParents($category_id, $categories = [])
    {
        $category_id = (int)$category_id;
        if( !empty($category_id) && $category_id > 0 ) {
            $categories[] = $category_id;
            $parent_id = $this->db->sql2val("SELECT `parent_id` FROM `oc_category` WHERE `category_id` = ".$category_id);
            $categories = $this->getParents($parent_id, $categories);
        }
        return $categories;
    }

    public function addSpecial($product_id, $price)
    {
        $sql = "INSERT INTO `oc_product_special`(
                  `product_id`, 
                  `customer_group_id`, 
                  `priority`, 
                  `price`, 
                  `date_start`, 
                  `date_end`
                  ) VALUES (
                  '{$product_id}',
                  '1',
                  '1',
                  '{$price}',
                  NOW(),
                  NOW()+INTERVAL 5 DAY
                  )";
        $this->db->sql($sql);
    }

    public function addManufacturer( $manufacturer )
    {

        $find = $this->db->sql2val("SELECT manufacturer_id FROM ".$this->prefix."manufacturer"." WHERE name = '{$this->db->escape($manufacturer)}'");
        if( !empty($find) && $find > 0 ) return $find;
        //ru desc
        $name = $this->db->escape($manufacturer);
        $this->insert( $this->prefix."manufacturer", array('name'=>$this->db->escape($manufacturer), 'image'=>'', 'sort_order'=> 0) );
        $manufacturer_id = $this->db->insert_id();
        $this->insert( $this->prefix."manufacturer_description", array('manufacturer_id'=>$manufacturer_id, 'language_id'=>1, 'name'=>$name, 'meta_title'=>$name) );
        $this->insert( $this->prefix."manufacturer_to_store", array('manufacturer_id'=>$manufacturer_id, 'store_id'=>0) );
        return $manufacturer_id;
    }

    public function getRelatedId( $product_id )
    {
        $product_id = (int)$product_id;
        $parser_data_id = $this->db->sql2val("SELECT id FROM parser_data WHERE product_id = '{$product_id}'");		
        if( !$parser_data_id ) return false;

        $related_id = $this->db->sql2val("SELECT product_id FROM ".$this->prefix."product"." WHERE parser_data_id = '{$parser_data_id}'");

        return $related_id;
    }
}