<?php
/**
 * 2026 Hugo BOHARD
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class RetractPlug extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'retractplug';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Hugo BOHARD';
        $this->need_instance = 0;

        /**
         * Compatibilité PrestaShop 1.7 & 8.x
         */
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('RetractPlug');
        $this->description = $this->l('Gère le droit de rétractation client sous 14 jours et synchronise les avoirs brouillons sur Dolibarr.');

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller RetractPlug ? Cela supprimera l\'historique local.');
    }

    /**
     * Procédure d'installation du module
     */
    public function install()
    {
        return parent::install() &&
            $this->executeSqlFile(dirname(__FILE__) . '/sql/install.sql') &&
            $this->registerHook('header') &&
            $this->registerHook('displayCustomerAccountOrder') &&
            $this->installTab() && 
            $this->registerHook('displayOrderDetail');
    }

    /**
     * Crée l'onglet de menu dans le Back-Office PrestaShop
     */
    protected function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminRetractRequests';
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders'); 
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = 'Rétractations & Retours';
        }

        return $tab->add();
    }

    /**
     * Nettoyage de l'onglet lors de la désinstallation
     */
    public function uninstall()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminRetractRequests');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        return $this->executeSqlFile(dirname(__FILE__) . '/sql/uninstall.sql') &&
            parent::uninstall();
    }

    /**
     * Exécute les fichiers SQL d'installation et de désinstallation
     *
     * @param string $file Chemin du fichier SQL
     * @return bool
     */
    protected function executeSqlFile($file)
    {
        if (!file_exists($file)) {
            return true;
        }

        if (!$sql_content = file_get_contents($file)) {
            return false;
        }

        $sql_content = str_replace(
            ['_DB_PREFIX_', '_MYSQL_ENGINE_'],
            [_DB_PREFIX_, _MYSQL_ENGINE_],
            $sql_content
        );

        $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);
        foreach ($sql_requests as $request) {
            if (!empty(trim($request))) {
                if (!Db::getInstance()->execute(trim($request))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Hook d'injection d'assets sur le Front-Office (CSS)
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/retractplug.css', 'all');

        if (Tools::getValue('retract_success') == 1) {
            $msg = $this->l('Votre demande de rétractation a bien été enregistrée. Il vous reste 14 jours pour renvoyer les produits et finaliser le processus.');
            
            return '
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    var container = document.querySelector("#content-wrapper, #main, .columns");
                    if (container) {
                        var alertDiv = document.createElement("div");
                        alertDiv.className = "alert alert-success text-center";
                        alertDiv.style.margin = "20px 0";
                        alertDiv.innerHTML = "' . pSQL($msg) . '";
                        container.insertBefore(alertDiv, container.firstChild);
                    }
                });
            </script>';
        }
    }

    /**
     * Hook d'affichage du bouton de rétractation sur le détail de la commande
     *
     * @param array $params Contient notamment l'objet 'order' courant
     */
    public function hookDisplayCustomerAccountOrder($params)
    {
        $order = $params['order'];
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $debug_customer_id = (int)Configuration::get('RETRACTPLUG_DEBUG_CUSTOMER_ID');
        $is_debug_user = ($debug_customer_id > 0 && $debug_customer_id === (int)$this->context->customer->id);

        if (!$is_debug_user) {
            $delivery_date = Db::getInstance()->getValue('
                SELECT oh.`date_add` 
                FROM `' . _DB_PREFIX_ . 'order_history` oh
                LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
                WHERE oh.`id_order` = ' . (int)$order->id . ' 
                AND os.`shipped` = 1
                ORDER BY oh.`date_add` DESC'
            );

            if (!$delivery_date) {
                return '';
            }

            $delivery_datetime = new DateTime($delivery_date);
            $current_datetime = new DateTime();
            $interval = $current_datetime->diff($delivery_datetime);

            if ($interval->days > 14) {
                return '';
            }
            
            $this->context->smarty->assign('days_left', 14 - $interval->days);
        } else {
            $this->context->smarty->assign('days_left', 'DEBUG (Illimité)');
        }

        $already_requested = Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'retractplug_requests` WHERE `id_order` = ' . (int)$order->id);
        $this->context->smarty->assign([
            'retract_already_done' => ($already_requested > 0),
            'retract_url' => $this->context->link->getModuleLink('retractplug', 'form', ['id_order' => $order->id])
        ]);

        return $this->display(__FILE__, 'views/templates/hook/order_detail.tpl');
    }

    /**
     * Hook d'affichage du bouton de rétractation sur le détail de la commande
     *
     * @param array $params Contient notamment l'objet 'order' courant
     */
    public function hookDisplayOrderDetail($params)
    {
        $order = $params['order'];
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        require_once dirname(__FILE__) . '/classes/RetractRequest.php';
        
        $request_data = Db::getInstance()->getRow('
            SELECT id_retractplug_request, status 
            FROM `' . _DB_PREFIX_ . 'retractplug_requests` 
            WHERE `id_order` = ' . (int)$order->id
        );

        if (!$request_data) {
            return ''; 
        }

        $id_request = (int)$request_data['id_retractplug_request'];
        
        $this->context->smarty->assign([
            'id_request' => $id_request,
            'download_pdf_link' => $this->context->link->getModuleLink('retractplug', 'pdf', ['id_request' => $id_request]),
            'request_status' => $request_data['status']
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'retractplug/views/templates/hook/order_detail_return.tpl'
        );
    }
    /**
     * Hook d'affichage du bouton de rétractation sur le détail de la commande
     *
     * @param array $params Contient notamment l'objet 'order' courant
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitRetractPlugConfig')) {
            $email = trim(Tools::getValue('RETRACTPLUG_ALERT_EMAIL'));

            if (!empty($email) && !Validate::isEmail($email)) {
                $output .= $this->displayError($this->l('L\'adresse e-mail d\'alerte saisie n\'est pas valide.'));
            } else {
                Configuration::updateValue('RETRACTPLUG_DOLIBARR_API_URL', Tools::getValue('RETRACTPLUG_DOLIBARR_API_URL'));
                Configuration::updateValue('RETRACTPLUG_DOLIBARR_API_KEY', Tools::getValue('RETRACTPLUG_DOLIBARR_API_KEY'));
                Configuration::updateValue('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE', (int)Tools::getValue('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE'));
                Configuration::updateValue('RETRACTPLUG_ALERT_EMAIL', $email);

                $output .= $this->displayConfirmation($this->l('Configurations enregistrées avec succès.'));
            }
        }

        if (Tools::isSubmit('submitTestEmail')) {
            $test_email = Configuration::get('RETRACTPLUG_ALERT_EMAIL');

            if (empty($test_email) || !Validate::isEmail($test_email)) {
                $output .= $this->displayError($this->l('Veuillez d\'abord enregistrer une adresse e-mail valide avant d\'exécuter le test.'));
            } else {
                $success = Mail::send(
                    $this->context->language->id,
                    'reply_msg', 
                    $this->l('[RetractPlug] Test d\'envoi de notification de secours'),
                    [
                        '{reply}' => "L'envoi de mail de secours fonctionne parfaitement ! Cet e-mail sera envoyé si l'API Dolibarr subit une panne de service."
                    ],
                    $test_email,
                    null,
                    null,
                    null,
                    null,
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    $this->context->shop->id
                );

                if ($success) {
                    $output .= $this->displayConfirmation($this->l('E-mail de test envoyé avec succès à : ') . $test_email);
                } else {
                    $output .= $this->displayError($this->l('Une erreur est survenue lors de l\'envoi du mail. Vérifiez la configuration SMTP globale de votre PrestaShop.'));
                }
            }
        }

        return $output . $this->renderConfigurationForm() . $this->renderDiagnosticTools(); 
    }

    /**
     * Génère le formulaire de configuration natif et parfaitement aligné
     */
    protected function renderConfigurationForm()
    {
        $cron_token = Configuration::get('RETRACTPLUG_CRON_TOKEN');
        if (!$cron_token) {
            $cron_token = Tools::encrypt(time());
            Configuration::updateValue('RETRACTPLUG_CRON_TOKEN', $cron_token);
        }

        $cron_url = $this->context->shop->getBaseURL() . 'modules/retractplug/cron.php?token=' . $cron_token;

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRetractPlugConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['RETRACTPLUG_DOLIBARR_API_URL'] = Configuration::get('RETRACTPLUG_DOLIBARR_API_URL');
        $helper->fields_value['RETRACTPLUG_DOLIBARR_API_KEY'] = Configuration::get('RETRACTPLUG_DOLIBARR_API_KEY');
        $helper->fields_value['RETRACTPLUG_GENERATE_DOLIBARR_INVOICE'] = Configuration::get('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE') !== false ? Configuration::get('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE') : 1;
        $helper->fields_value['RETRACTPLUG_ALERT_EMAIL'] = Configuration::get('RETRACTPLUG_ALERT_EMAIL'); // Nouveau
        $helper->fields_value['RETRACTPLUG_CRON_URL'] = $cron_url;

        $email_desc = $this->l('Adresse qui recevra une notification si l\'API Dolibarr échoue.') . '<br/><br/>' .
            '<button type="submit" name="submitTestEmail" class="btn btn-info">' .
            '<i class="icon-envelope"></i> ' . $this->l('Envoyer un e-mail de test') .
            '</button>';

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Paramètres de connexion Dolibarr & Sécurité'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('URL de l\'API REST Dolibarr'),
                        'name' => 'RETRACTPLUG_DOLIBARR_API_URL',
                        'required' => true,
                        'col' => 6,
                        'desc' => $this->l('Exemple: https://mon-dolibarr.com/api/index.php'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Clé d\'API Dolibarr (DOLAPIKEY)'),
                        'name' => 'RETRACTPLUG_DOLIBARR_API_KEY',
                        'required' => true,
                        'col' => 4,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Générer les avoirs en temps réel'),
                        'name' => 'RETRACTPLUG_GENERATE_DOLIBARR_INVOICE',
                        'is_bool' => true,
                        'desc' => $this->l('Si activé, l\'avoir est envoyé immédiatement lors de la demande du client.'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('E-mail d\'alerte (Secours Dolibarr)'),
                        'name' => 'RETRACTPLUG_ALERT_EMAIL',
                        'required' => false,
                        'col' => 4,
                        'desc' => $email_desc, 
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('URL de la tâche CRON de secours'),
                        'name' => 'RETRACTPLUG_CRON_URL',
                        'readonly' => true,
                        'col' => 8,
                        'desc' => $this->l('Copiez cette URL pour configurer votre gestionnaire de tâches (Crontab).'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Génère le tableau des demandes de rétractation natif
     */
    protected function renderRequestsList()
    {
        $sql = 'SELECT a.`id_retractplug_request`, o.`reference` AS `order_reference`, CONCAT(c.`firstname`, " ", c.`lastname`) AS `customer_name`, a.`reason`, a.`status`, a.`date_add`
                FROM `' . _DB_PREFIX_ . 'retractplug_requests` a
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = a.`id_order`)
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)
                ORDER BY a.`date_add` DESC';
        
        $results = Db::getInstance()->executeS($sql);

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_retractplug_request';
        $helper->actions = ['edit'];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->title = $this->l('Demandes enregistrées');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;

        $fields_list = [
            'id_retractplug_request' => ['title' => $this->l('ID'), 'type' => 'text', 'search' => false, 'orderby' => false],
            'order_reference' => ['title' => $this->l('Commande'), 'type' => 'text', 'search' => false, 'orderby' => false],
            'customer_name' => ['title' => $this->l('Client'), 'type' => 'text', 'search' => false, 'orderby' => false],
            'reason' => ['title' => $this->l('Motif'), 'type' => 'text', 'search' => false, 'orderby' => false],
            'status' => ['title' => $this->l('Statut'), 'type' => 'text', 'search' => false, 'orderby' => false],
            'date_add' => ['title' => $this->l('Date'), 'type' => 'datetime', 'search' => false, 'orderby' => false],
        ];

        return $helper->generateList($results, $fields_list);
    }
}