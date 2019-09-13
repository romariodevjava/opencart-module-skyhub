<?php

include './SkyHubCommons.php';

class ProductOperations {

    const OPERATION_ADD = 1;
    const OPERATION_UPDATE = 2;
    const OPERATION_REMOVE = 3;
    const OPERATION_PATH = 'products';

    private $productData;
    private $operation;
    private $skyHubEmail;
    private $skyhubToken;

    /**
     * AddProduct constructor.
     * @param $productData
     * @param $skyHubEmail
     * @param $skyhubToken
     * @param $operation
     */
    public function __construct($productData, $skyHubEmail, $skyhubToken, $operation) {
        $this->productData = $productData;
        $this->operation = $operation;
        $this->skyHubEmail = $skyHubEmail;
        $this->skyhubToken = $skyhubToken;
    }


    public function run() {
        try {
            if ($this->operation == self::OPERATION_ADD) {
                $this->addProduct();
            } else if ($this->operation == self::OPERATION_REMOVE) {
                $this->deleteProduct();
            } else if ($this->operation == self::OPERATION_UPDATE) {
                $this->updateProduct();
            }
        } catch (Exception $ex) {
            $log = new Log('skyhub_module.log');
            $log->write($ex->getMessage());
        }
    }

    private function addProduct() {
        SkyHubCommons::executeRequest($this->skyHubEmail, $this->skyhubToken, self::OPERATION_PATH, SkyHubCommons::REQUEST_METHOD_POST, $this->productData);
    }

    private function deleteProduct() {
        $path = self::OPERATION_PATH . ' /' . $this->productData['sku'];
        SkyHubCommons::executeRequest($this->skyHubEmail, $this->skyhubToken, $path, SkyHubCommons::REQUEST_METHOD_DELETE);
    }

    private function updateProduct() {
        $path = self::OPERATION_PATH . ' /' . $this->productData['sku'];
        SkyHubCommons::executeRequest($this->skyHubEmail, $this->skyhubToken, $path, SkyHubCommons::REQUEST_METHOD_PUT, $this->productData);
    }
}