<?php
/**
 * 2026 Hugo BOHARD
 */

use RetractPlug\Service\DolibarrApiClient;

// Inclusion manuelle obligatoire des classes dépendantes (Pas d'autoloader natif en PHP vanilla)
if (file_exists(dirname(__FILE__) . '/../../classes/RetractRequest.php')) {
    require_once dirname(__FILE__) . '/../../classes/RetractRequest.php';
}
if (file_exists(dirname(__FILE__) . '/../../src/Service/DolibarrApiClient.php')) {
    require_once dirname(__FILE__) . '/../../src/Service/DolibarrApiClient.php';
}

class RetractPlugFormModuleFrontController extends ModuleFrontController
{
    /** @var Order */
    protected $order;

    /**
     * Initialisation du contrôleur
     */
    public function init()
    {
        parent::init();

        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        $id_order = (int)Tools::getValue('id_order');
        $this->order = new Order($id_order);

        if (!Validate::isLoadedObject($this->order) || $this->order->id_customer != $this->context->customer->id) {
            Tools::redirect('index.php?controller=history');
        }

        $debug_customer_id = (int)Configuration::get('RETRACTPLUG_DEBUG_CUSTOMER_ID');
        $is_debug_user = ($debug_customer_id > 0 && $debug_customer_id === (int)$this->context->customer->id);

        if (!$is_debug_user) {
            $delivery_date = Db::getInstance()->getValue('
                SELECT oh.`date_add` 
                FROM `' . _DB_PREFIX_ . 'order_history` oh
                LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
                WHERE oh.`id_order` = ' . (int)$this->order->id . ' 
                AND os.`shipped` = 1
                ORDER BY oh.`date_add` DESC'
            );

            if (!$delivery_date) {
                Tools::redirect('index.php?controller=history');
            }

            $delivery_datetime = new DateTime($delivery_date);
            $current_datetime = new DateTime();
            $interval = $current_datetime->diff($delivery_datetime);

            if ($interval->days > 14) {
                Tools::redirect('index.php?controller=history');
            }
        }
    }

    /**
     * Traitement du formulaire de rétractation
     */
    public function postProcess()
    {
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        $id_order = (int)Tools::getValue('id_order');
        $order = new Order($id_order);

        if (!Validate::isLoadedObject($order) || $order->id_customer != $this->context->customer->id) {
            Tools::redirect('index.php?controller=history'); 
        }

        if (Tools::isSubmit('submitRetraction')) {
            $selected_products = Tools::getValue('returned_products');
            $quantities = Tools::getValue('returned_quantities');
            $reason = trim(Tools::getValue('retract_reason'));

            if (empty($selected_products)) {
                $this->errors[] = $this->module->l('Veuillez sélectionner au moins un produit à retourner.');
                return;
            }

            if (empty($reason)) {
                $this->errors[] = $this->module->l('Veuillez indiquer un motif pour votre rétractation.');
                return;
            }

            $products_to_return = [];
            $invoice_lines = [];
            
            $order_products = (array)$this->order->getProducts(); 

            foreach ($selected_products as $id_order_detail) {
                $qty = (int)$quantities[$id_order_detail];
                if ($qty <= 0) {
                    continue;
                }

                foreach ($order_products as $prod) {
                    if (is_array($prod) && isset($prod['id_order_detail']) && $prod['id_order_detail'] == $id_order_detail) {
                        $products_to_return[] = [
                            'id_order_detail' => $id_order_detail,
                            'id_product' => $prod['product_id'],
                            'id_product_attribute' => $prod['product_attribute_id'],
                            'quantity' => $qty,
                            'name' => $prod['product_name']
                        ];

                        $invoice_lines[] = [
                            'desc' => '[Retour #' . $this->order->reference . '] ' . $prod['product_name'],
                            'subprice' => -((float)$prod['unit_price_tax_excl']), 
                            'qty' => $qty,
                            'tva_tx' => (float)$prod['tax_rate'],
                        ];
                        break;
                    }
                }
            }

            $id_thirdparty_dolibarr = 66796; 

            $id_dolibarr_invoice = null;

            if ((int)Configuration::get('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE') === 1) {
                $invoice_payload = [
                    'socid' => (int)$id_thirdparty_dolibarr, 
                    'type' => 2,   
                    'status' => 0, 
                    'date' => time(),
                    'note_private' => "Avoir généré automatiquement via le module RetractPlug. Commande PrestaShop #" . $this->order->reference,
                    'lines' => $invoice_lines
                ];

                $api_client = new DolibarrApiClient();
                $id_dolibarr_invoice = $api_client->createDraftInvoice($invoice_payload);
            } else {
                $log_file = _PS_ROOT_DIR_ . '/var/logs/retractplug.log';
                $log_msg = date('[Y-m-d H:i:s]') . " SIMULATION FRONT: Commande #" . $this->order->reference . " | Génération d'avoir ignorée (Paramètre sur NON).\n";
                file_put_contents($log_file, $log_msg, FILE_APPEND);
                
                $id_dolibarr_invoice = 0; 
            }

            $retract_request = new RetractRequest();
            $retract_request->id_order = (int)$this->order->id;
            $retract_request->id_customer = (int)$this->context->customer->id;
            $retract_request->products_data = json_encode($products_to_return);
            $retract_request->reason = $reason; 
            $retract_request->status = 'waiting_package'; 
            $retract_request->id_dolibarr_invoice = $id_dolibarr_invoice ? (int)$id_dolibarr_invoice : null;

            if ($retract_request->add()) {
                Tools::redirect($this->context->link->getModuleLink('retractplug', 'form', [
                    'id_order' => (int)$this->order->id,
                    'step' => 'success',
                    'id_request' => (int)$retract_request->id
                ]));
            } else {
                $this->errors[] = $this->module->l('Une erreur est survenue lors de la sauvegarde locale.');
            }
        }
    }

    /**
     * Initialisation du contenu de la page
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('step') === 'success') {
            $this->context->smarty->assign([
                'order' => $this->order,
                'id_request' => (int)Tools::getValue('id_request'),
                'shop_name' => Configuration::get('PS_SHOP_NAME'),
                'shop_address' => Configuration::get('PS_SHOP_ADDR1') . ' ' . Configuration::get('PS_SHOP_ADDR2'),
                'shop_postcode' => Configuration::get('PS_SHOP_CODE'),
                'shop_city' => Configuration::get('PS_SHOP_CITY'),
            ]);

            $this->setTemplate('module:retractplug/views/templates/front/success.tpl');
            return;
        }

        $products = $this->order->getProducts();
        $this->context->smarty->assign([
            'order' => $this->order,
            'products' => $products,
            'errors' => $this->errors,
        ]);

        $this->setTemplate('module:retractplug/views/templates/front/form.tpl');
    }
}