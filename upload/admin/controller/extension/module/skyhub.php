<?php

include DIR_APPLICATION . 'controller/extension/module/skyhub/ProductOperations.php';
include DIR_APPLICATION . 'controller/extension/module/skyhub/ProductVariantOperations.php';

class ControllerExtensionModuleSkyhub extends Controller
{
    private $route = 'extension/module/skyhub';
    private $key_prefix = 'module_skyhub';
    private $skyhub_email;
    private $skyhub_token;
    private $skyhub_percentage;
    private $skyhub_status;
    private $skyhub_update_product;

    public function index()
    {
        $data = $this->load->language($this->route);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate($data)) {
            $this->model_setting_setting->editSetting($this->key_prefix, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data[$this->key_prefix . '_email'] = $this->request->post[$this->key_prefix . '_email'] ?? $this->config->get($this->key_prefix . '_email');
        $data[$this->key_prefix . '_token'] = $this->request->post[$this->key_prefix . '_token'] ?? $this->config->get($this->key_prefix . '_token');
        $data[$this->key_prefix . '_percentage'] = $this->request->post[$this->key_prefix . '_percentage'] ?? $this->config->get($this->key_prefix . '_percentage');
        $data[$this->key_prefix . '_prazo'] = $this->request->post[$this->key_prefix . '_prazo'] ?? $this->config->get($this->key_prefix . '_prazo');
        $data[$this->key_prefix . '_status_update_product'] = $this->request->post[$this->key_prefix . '_status_update_product'] ?? $this->config->get($this->key_prefix . '_status_update_product');
        $data[$this->key_prefix . '_status'] = $this->request->post[$this->key_prefix . '_status'] ?? $this->config->get($this->key_prefix . '_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->route, $data));
    }

    /**
     * @param $data
     * @return bool
     */
    protected function validate(&$data)
    {
        if (!$this->user->hasPermission('modify', $this->route)) {
            $data['warning'] = $this->language->get('error_permission_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_email']) || !filter_var($this->request->post[$this->key_prefix . '_email'], FILTER_VALIDATE_EMAIL)) {
            $data['error_email'] = $this->language->get('error_email_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_token'])) {
            $data['error_token'] = $this->language->get('error_token_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_percentage']) ||
                intval($this->request->post[$this->key_prefix . '_percentage']) < 0 || intval($this->request->post[$this->key_prefix . '_percentage']) > 100) {
            $data['error_percentage'] = $this->language->get('error_percentage_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_prazo']) ||
                intval($this->request->post[$this->key_prefix . '_prazo']) < 0 || intval($this->request->post[$this->key_prefix . '_prazo']) > 100) {
            $data['error_prazo'] = $this->language->get('error_prazo_message');
            return false;
        }

        return true;
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $this->load->model($this->route);
        $this->load->model('user/user_group');
        $this->load->model('setting/event');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', $this->route);
        $this->model_setting_event->addEvent('product_create_skyhub', 'admin/model/catalog/product/addProduct/after', $this->route . '/addProduct');
        $this->model_setting_event->addEvent('product_update_skyhub', 'admin/model/catalog/product/editProduct/after', $this->route . '/updateProduct');
        $this->model_setting_event->addEvent('product_update_stock_skyhub', 'catalog/model/checkout/order/after', $this->route . '/updateProductStock');
        $this->model_setting_event->addEvent('product_delete_skyhub', 'admin/model/catalog/product/deleteProduct/after', $this->route . '/deleteProduct');

        $this->load->model($this->route);

        $this->model_extension_module_skyhub->criarTabelas();
    }

    public function unistall()
    {
        $this->load->model('setting/setting');
        $this->load->model($this->route);
        $this->load->model('user/user_group');
        $this->load->model('setting/event');
        $this->load->model('extension/module');
        $this->load->model($this->route);

        $this->model_extension_module->deleteModulesByCode($this->route);
        $this->model_setting_setting->deleteSetting($this->route);

        $this->model_extension_event->deleteEvent('product_create_skyhub');
        $this->model_extension_event->deleteEvent('product_update_skyhub');
        $this->model_extension_event->deleteEvent('product_update_stock_skyhub');
        $this->model_extension_event->deleteEvent('product_delete_skyhub');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', $this->route);

        $this->model_extension_module_skyhub->removerTabelas();
    }

    private function loadConfig() {
        $this->load->model($this->route);

        $this->skyhub_email =  $this->config->get($this->key_prefix . '_email');
        $this->skyhub_token = $this->config->get($this->key_prefix . '_token');
        $this->skyhub_percentage = $this->config->get($this->key_prefix . '_percentage');
        $this->skyhub_status = $this->config->get($this->key_prefix . '_status');
        $this->skyhub_update_product = $this->config->get($this->key_prefix . '_status_update_product');
    }

    public function addProduct(&$route, &$args, &$output)  {
       $this->loadConfig();

        if (!empty($output) && $this->skyhub_status && $this->skyhub_update_product) {
            $this->load->model($this->route);
            $product = $this->model_extension_module_skyhub->getProduct($output, $this->skyhub_percentage);
            $product['sku'] = $this->generateSkuForSkyHub($output, $this->model_extension_module_skyhub);

            $operation = new ProductOperations($product, $this->skyhub_email, $this->skyhub_token, ProductOperations::OPERATION_ADD);
            $operation->start();
        }
    }

    public function syncProducts($productsIds) {
        $this->loadConfig();

        $idsProductsInSkyHub = $this->model_extension_module_skyhub->getAllProductsInSkyHub();
        $i = 0;

        foreach ($productsIds as $productId) {
            if (in_array($productId, $idsProductsInSkyHub)) continue;

            if ($this->skyhub_status) {
                $product = $this->model_extension_module_skyhub->getProduct($productId, $this->skyhub_percentage);
                $product['sku'] = $this->generateSkuForSkyHub($productId, $this->model_extension_module_skyhub);

                $operation = new ProductOperations($product, $this->skyhub_email, $this->skyhub_token, ProductOperations::OPERATION_ADD);
                $operation->start();
                $this->awaitLimitionByTimeOfSkyhub($i);
            }
        }
    }

    private function awaitLimitionByTimeOfSkyhub(&$count) {
        $count++;

        if ($count >= 9) {
            sleep(1);
            $count = 0;
        }
    }

    public function deleteProduct(&$route, &$args, &$output)  {
        $this->loadConfig();
        $product_id = $args[0];

        $skyhub_sku = $this->getSkuForSkyHub($product_id, $this->model_extension_module_skyhub);

        if ($skyhub_sku && $this->skyhub_status && $this->skyhub_update_product) {
            $this->load->model($this->route);
            $product = ['sku' => $skyhub_sku];

            $operation = new ProductOperations($product, $this->skyhub_email, $this->skyhub_token, ProductOperations::OPERATION_REMOVE);
            $operation->start();
        }
    }

    public function updateProduct(&$route, &$args, &$output)  {
        $this->loadConfig();
        $product_id = $args[0];

        $skyhub_sku = $this->getSkuForSkyHub($product_id, $this->model_extension_module_skyhub);

        if ($skyhub_sku && $this->skyhub_status && $this->skyhub_update_product) {
            $product = $this->model_extension_module_skyhub->getProduct($product_id, $this->skyhub_percentage);
            $product['sku'] = $skyhub_sku;

            $operation = new ProductOperations($product, $this->skyhub_email, $this->skyhub_token, ProductOperations::OPERATION_UPDATE);
            $operation->start();
        }
    }

    public function updateProductStock(&$route, &$args, &$output)  {
        $this->loadConfig();
        $orderId = $args[0];

        if (!$orderId) return;
        $products = $this->model_extension_module_skyhub->getOrderProducts($orderId);

        $i = 0;
        foreach ($products as $product) {
            $skyhub_sku = $this->getSkuForSkyHub($product['product_id'], $this->model_extension_module_skyhub);
            $variations = $this->model_extension_module_skyhub->getVariationForStockUpdate($orderId, $product['product_id']);

            if ($skyhub_sku && $this->skyhub_status && $this->skyhub_update_product) {
                $product = $this->model_extension_module_skyhub->getProduct($product['product_id'], $this->skyhub_percentage);

                $operation = new ProductOperations($product, $this->skyhub_email, $this->skyhub_token, ProductOperations::OPERATION_UPDATE);
                $operation->start();

                $o = 0;
                foreach ($variations as $variation) {
                    $operation_op = new ProductVariantOperations($variation, $this->skyhub_email, $this->skyhub_token, ProductOperations::OPERATION_UPDATE);
                    $operation_op->start();
                    $this->awaitLimitionByTimeOfSkyhub($o);
                }

                $this->awaitLimitionByTimeOfSkyhub($i);
            }
        }
    }

    private function generateSkuForSkyHub($product_id, $model) {
        return $model->generateSku($product_id);
    }

    private function getSkuForSkyHub($product_id, $model) {
        return $model->findSku($product_id);
    }
}