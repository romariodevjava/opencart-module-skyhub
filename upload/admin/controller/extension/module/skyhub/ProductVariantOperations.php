<?php

include './SkyHubCommons.php';

class ProductVariantOperations {

    const OPERATION_UPDATE = 2;
    const OPERATION_REMOVE = 3;
    const OPERATION_PATH = 'variations';

    private $variationData;
    private $operation;
    private $skyHubEmail;
    private $skyhubToken;

    /**
     * AddProduct constructor.
     * @param $variationData
     * @param $skyHubEmail
     * @param $skyhubToken
     * @param $operation
     */
    public function __construct($variationData, $skyHubEmail, $skyhubToken, $operation) {
        $this->variationData = $variationData;
        $this->operation = $operation;
        $this->skyHubEmail = $skyHubEmail;
        $this->skyhubToken = $skyhubToken;
    }


    public function run() {
        try {
            if ($this->operation == self::OPERATION_REMOVE) {
                $this->deleteVariation();
            } else if ($this->operation == self::OPERATION_UPDATE) {
                $this->updateVariation();
            }
        } catch (Exception $ex) {
            $log = new Log('skyhub_module.log');
            $log->write($ex->getMessage());
        }
    }

    private function deleteVariation() {
        $path = self::OPERATION_PATH . ' /' . $this->variationData['sku'];
        SkyHubCommons::executeRequest($this->skyHubEmail, $this->skyhubToken, $path, SkyHubCommons::REQUEST_METHOD_DELETE);
    }

    private function updateVariation() {
        $path = self::OPERATION_PATH . ' /' . $this->variationData['sku'];
        SkyHubCommons::executeRequest($this->skyHubEmail, $this->skyhubToken, $path, SkyHubCommons::REQUEST_METHOD_PUT, $this->variationData);
    }
}