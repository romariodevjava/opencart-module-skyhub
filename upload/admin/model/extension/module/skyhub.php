<?php


class ModelExtensionModuleSkyhub extends Model
{
    private $key_prefix = 'module_skyhub';

    public function criarTabelas()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "skyhub_products` (
			  `product_id` int(11) NOT NULL,
			  `skyhub_sku` int(11) NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY (`skyhub_sku`),
			  FOREIGN KEY (`product_id`) REFERENCES " . DB_PREFIX . "product(`product_id`),
			  UNIQUE KEY (`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
    }

    public function removerTabelas() {
        $this->db->query("DROP TABLE `" . DB_PREFIX . "skyhub_products`");
    }

    public function generateSku($product_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "skyhub_products (product_id) VALUES (" . (int)$product_id . ")");

        return $this->db->getLastId();
    }

    public function getAllProductsInSkyHub() {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "skyhub_products;");
        $products = array();

        if ($query->num_rows) {
            foreach ($query->num_rows as $product) {
                $products[] = $product['product_id'];
            }
        }

        return $products;
    }

    public function findSku($productId) {
        $query = $this->db->query("SELECT skyhub_sku FROM " . DB_PREFIX . "skyhub_products WHERE product_id = " . (int) $productId . ";");

        if ($query->row) {
            return $query->row['skyhub_sku'];
        }

        return false;
    }

    public function getAllProductsOfStore($productsIds) {
        $query = $this->db->query("SELECT DISTINCT product_id FROM " . DB_PREFIX . "product p 
        LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
        WHERE p.product_id not in (" . implode(',', array_map('intval', $productsIds)) . ") 
        AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        $products = array();

        if ($query->num_rows) {
            foreach ($query->num_rows as $product) {
                $products[] = $product['product_id'];
            }
        }

        return $products;
    }

    public function removeSku($sku) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "skyhub_products WHERE skyhub_sku = " . (int) $sku . ";");
    }

    public function getProduct($product_id, $skyhub_percentage)
    {
        $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, m.name AS manufacturer, 
                                   (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, 
                                   (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special,  
                                   p.sort_order FROM " . DB_PREFIX . "product p 
                                   LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                                   LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
                                   LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) 
                                   WHERE p.product_id = '" . (int)$product_id . "' 
                                   AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                   AND p.date_available <= NOW() 
                                   AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        $product = null;

        if ($query->num_rows) {
            $prazo = (int) $this->config->get($this->key_prefix . '_prazo');
            $images = $this->getProductImages($product_id);
            $price = ($query->row['discount'] ? $query->row['discount'] : $query->row['price']);
            $price_percentage_incremental = 1 + (intval($skyhub_percentage) / 100);
            $variantes = $this->getVariation($product_id, $images, $query->row['ean'], $price, $query->row['special'], $prazo, $price_percentage_incremental);
            $categories = $this->categories($product_id);

            $product = array(
                'product_id' => $query->row['product_id'],
                'name' => $query->row['name'],
                'description' => $query->row['description'],
                'qty' => $query->row['quantity'],
                'status' => intval($query->row['status']) === 1 ? 'enabled' : 'disabled',
                'price' => $price * $price_percentage_incremental,
                'promotional_price' => $query->row['special'] * $price_percentage_incremental,
                'weight' => $query->row['weight'],
                'height' => $query->row['height'],
                'width' => $query->row['width'],
                'length' => $query->row['length'],
                'ean' => $query->row['ean'],
                'nbm' => $query->row['mpn'],
                'brand' => $query->row['manufacturer'],
                'variations' => $variantes,
                'images' => $images,
                'specifications' => [
                    [
                        'key' => 'CrossDocking',
                        'value' => $prazo
                    ]
                ],
                'categories' => $categories
            );
        }

        return $product;
    }

    public function getProductForUpdateStock($product_id, $skyhub_percentage)
    {
        $query = $this->db->query("SELECT DISTINCT p.quantity FROM " . DB_PREFIX . "product p 
                                   WHERE p.product_id = '" . (int)$product_id . "'");

        $product = null;

        if ($query->num_rows) {
            $product = array(
                'qty' => $query->row['quantity'],
            );
        }

        return $product;
    }

    public function getProductImages($product_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");
        $images = array();

        if ($query->rows) {
            foreach ($query->rows as $row) {
                $images[] = $row['images'];
            }
        }

        return $images;
    }

    private function getVariation($productId, $images, $ean, $price, $pricePromotional, $prazo, $price_percentage_incremental) {
        $query = $this->db->query("SELECT od.name as nomeTipoVariacao, pov.sku as sku,  ovd.name as tipoVariacao, pov.quantity as quantidadeVariacao, pov.price as precoVaricao, pov.price_prefix as prefixPrecoVaricao,  pov.weight as pesoVaricao, pov.weight_prefix as prefixPesoVaricao, pov.product_option_value_id as idVariation
					   FROM " . DB_PREFIX . "option_description od 
					   LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON ( od.option_id = ovd.option_id )
					   LEFT JOIN " . DB_PREFIX . "product_option_value pov ON ( ovd.option_value_id = pov.option_value_id )
			   		   WHERE pov.product_id = '" . (int)$productId . "'");
        $variations = array();

        if ($query->rows) {
            foreach ($query->rows as $row) {
                $priceFinal = ($row['prefixPrecoVaricao'] == '-') ? $price - $row['precoVaricao'] : $price + $row['precoVaricao'];
                $pricePromotionalFinal = isset($pricePromotional) ?
                    ($row['prefixPrecoVaricao'] == '-') ? $pricePromotional - $row['precoVaricao'] : $pricePromotional + $row['precoVaricao']
                    : $priceFinal;

                $variations[] = [
                    'images' => $images,
                    'ean' => $ean,
                    'qty' => $row['quantidadeVariacao'],
                    'sku' => empty($row['sku']) ? 'OP' . $row['idVariation'] : $row['sku'],
                    'specifications' => [
                        [
                            'key' => $row['nomeTipoVariacao'],
                            'value' => $row['tipoVariacao']
                        ],
                        [
                            'key' => 'price',
                            'value' => $priceFinal * $price_percentage_incremental
                        ],
                        [
                            'key' => "promotional_price",
                            'value' => $pricePromotionalFinal * $price_percentage_incremental
                        ],
                        [
                            'key' => "CrossDocking",
                            'value' => $prazo
                        ]
                    ]

                ];
            }
        }

        return $variations;
    }

    public function getVariationForStockUpdate($order_id, $order_product_id) {
        $query = $this->db->query("SELECT pov.sku as sku, pov.quantity as quantidadeVariacao, , pov.product_option_value_id as idVariation FROM " . DB_PREFIX . "order_option oo
                                     LEFT JOIN product_option_value pov ON oo.product_option_value_id = pov.product_option_value_id
                                     WHERE oo.order_id = '" . (int)$order_id . "' AND oo.order_product_id = '" . (int)$order_product_id . "'");

        $variations = array();

        if ($query->rows) {
            foreach ($query->rows as $row) {
                $variations[] = [
                    'qty' => $row['quantidadeVariacao'],
                    'sku' => empty($row['sku']) ? 'OP' . $row['idVariation'] : $row['sku'],
                ];
            }
        }

        return $variations;
    }

    private function categories($product_id) {
        $query = $this->db->query("SELECT cd.name AS name, cd.category_id AS code FROM " . DB_PREFIX . "product_to_category ptc 
					   LEFT JOIN " . DB_PREFIX . "category c ON ( ptc.category_id = c.category_id )
					   LEFT JOIN " . DB_PREFIX . "oc_category_description cd ON ( c.category_id = cd.category_id)
			   		   WHERE ptc.product_id = '" . (int)$product_id . "' 
			   		   AND c.parent_id <> '0'
			   		   AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->rows;
    }

    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }
}