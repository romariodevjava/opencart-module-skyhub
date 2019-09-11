<?php


class AddProduct extends Thread {

    private $productData;

    /**
     * AddProduct constructor.
     * @param $productData
     * @param $curl
     */
    public function __construct($productData, $configSkyHub)
    {
        $this->productData = $productData;
    }


    public function run()
    {
        parent::run();

    }
}