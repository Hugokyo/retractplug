<?php
/**
 * 2026 Hugo BOHARD
 */

require_once dirname(__FILE__) . '/../../classes/RetractRequest.php';

class AdminRetractRequestsController extends AdminController
{
    public function __construct()
    {
        $this->table = 'retractplug_requests';
        $this->className = 'RetractRequest';
        $this->identifier = 'id_retractplug_request';
        $this->bootstrap = true;

        parent::__construct();

        $this->_select = 'o.`reference` AS `order_reference`, CONCAT(c.`firstname`, " ", c.`lastname`) AS `customer_name`';
        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = a.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)';

        $this->fields_list = [
            'id_retractplug_request' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_retractplug_request',
            ],
            'order_reference' => [
                'title' => $this->l('Référence Commande'),
                'align' => 'left',
                'filter_key' => 'o!reference',
            ],
            'customer_name' => [
                'title' => $this->l('Client'),
                'align' => 'left',
                'filter_key' => 'customer_name',
                'havingFilter' => true,
            ],
            'reason' => [
                'title' => $this->l('Motif du retour'),
                'align' => 'left',
                'maxlength' => 100, 
                'filter_key' => 'a!reason',
            ],
            'id_dolibarr_invoice' => [
                'title' => $this->l('ID Avoir Dolibarr'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!id_dolibarr_invoice',
                'callback' => 'displayDolibarrLink',
            ],
            'status' => [
                'title' => $this->l('Statut du retour'),
                'align' => 'left',
                'type' => 'select',
                'list' => [
                    'waiting_package' => $this->l('En attente du colis'),
                    'received' => $this->l('Colis reçu'),
                    'refused' => $this->l('Refusé'),
                    'processed' => $this->l('Traité / Remboursé'),
                ],
                'filter_key' => 'a!status',
                'callback' => 'displayStatusBadge',
            ],
            'date_add' => [
                'title' => $this->l('Date Demande'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    /**
     * Rendu visuel personnalisé pour le statut (Badge HTML)
     */
    public function displayStatusBadge($value, $row)
    {
        $badges = [
            'waiting_package' => '<span class="label label-warning">En attente du colis</span>',
            'received' => '<span class="label label-info">Colis reçu</span>',
            'refused' => '<span class="label label-danger">Refusé</span>',
            'processed' => '<span class="label label-success">Traité / Remboursé</span>',
        ];

        return isset($badges[$value]) ? $badges[$value] : $value;
    }

    /**
     * Rendu personnalisé pour afficher proprement l'ID Dolibarr
     */
    public function displayDolibarrLink($value, $row)
    {
        if (empty($value)) {
            return '<span class="text-muted">N/A (Échec API)</span>';
        }
        return '<span class="badge badge-default">#' . (int)$value . '</span>';
    }

    /**
     * Génération automatique du formulaire d'édition lors du clic sur "Modifier"
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Mise à jour de la demande de rétractation'),
                'icon' => 'icon-edit',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Statut de la demande'),
                    'name' => 'status',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'waiting_package', 'name' => $this->l('En attente du colis')],
                            ['id' => 'received', 'name' => $this->l('Colis reçu')],
                            ['id' => 'refused', 'name' => $this->l('Refusé')],
                            ['id' => 'processed', 'name' => $this->l('Traité / Remboursé')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Motif du client (Lecture seule)'),
                    'name' => 'reason',
                    'disabled' => true,
                    'cols' => 40,
                    'rows' => 5,
                ],
            ],
            'submit' => [
                'title' => $this->l('Enregistrer'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        return parent::renderForm();
    }

    /**
     * Initialisation du contenu de la page d'administration
     */
    public function initContent()
    {
        if (Tools::isSubmit('submitRetractPlugConfig')) {
            $email = trim(Tools::getValue('RETRACTPLUG_ALERT_EMAIL'));

            if (!empty($email) && !Validate::isEmail($email)) {
                $this->errors[] = $this->l('L\'adresse e-mail d\'alerte de secours n\'est pas valide.');
            } else {
                Configuration::updateValue('RETRACTPLUG_DOLIBARR_API_URL', trim(Tools::getValue('RETRACTPLUG_DOLIBARR_API_URL')));
                Configuration::updateValue('RETRACTPLUG_DOLIBARR_API_KEY', trim(Tools::getValue('RETRACTPLUG_DOLIBARR_API_KEY')));
                Configuration::updateValue('RETRACTPLUG_DEBUG_CUSTOMER_ID', (int)Tools::getValue('RETRACTPLUG_DEBUG_CUSTOMER_ID'));
                Configuration::updateValue('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE', (int)Tools::getValue('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE'));
                Configuration::updateValue('RETRACTPLUG_ALERT_EMAIL', $email);
                
                $this->confirmations[] = $this->l('Configuration mise à jour avec succès.');
            }
        }

        if (Tools::isSubmit('submitTestEmail')) {
            $test_email = Configuration::get('RETRACTPLUG_ALERT_EMAIL');

            if (empty($test_email) || !Validate::isEmail($test_email)) {
                $this->errors[] = $this->l('Veuillez d\'abord enregistrer une adresse e-mail valide avant d\'exécuter le test.');
            } else {
                $id_lang = (int)$this->context->language->id;
                $iso_lang = Language::getIsoById($id_lang);
                
                if (!file_exists(_PS_MODULE_DIR_ . 'retractplug/mails/' . $iso_lang . '/alert_backup.html')) {
                    $id_lang = (int)Language::getIdByIso('fr');
                }

                $from_email = (string)Configuration::get('PS_SHOP_EMAIL');
                $shop_name = (string)Configuration::get('PS_SHOP_NAME');

                $success = Mail::send(
                    $id_lang,
                    'alert_backup', 
                    $this->l('[RetractPlug] Test d\'envoi de notification de secours'),
                    [
                        '{message}' => "L'envoi de mail de secours fonctionne parfaitement ! Cet e-mail d'alerte automatique sera envoyé à l'administrateur si l'API Dolibarr subit une panne de service."
                    ],
                    $test_email,
                    null,
                    $from_email,
                    $shop_name,
                    null, null,
                    _PS_MODULE_DIR_ . 'retractplug/mails/', 
                    false,
                    $this->context->shop->id
                );

                if ($success) {
                    $this->confirmations[] = $this->l('E-mail de test envoyé avec succès à : ') . $test_email;
                } else {
                    $this->errors[] = $this->l('Une erreur est survenue lors de l\'envoi du mail. Vérifiez que les fichiers alert_backup.html et alert_backup.txt existent bien dans modules/retractplug/mails/fr/');
                }
            }
        }

        if (Tools::isSubmit('submitTestDolibarrApi')) {
            require_once dirname(__FILE__) . '/../../src/Service/DolibarrApiClient.php';
            $api_client = new \RetractPlug\Service\DolibarrApiClient();
            $response = $api_client->call('/status', 'GET');
            
            if ($response !== false) {
                $this->confirmations[] = $this->l('Connexion API Dolibarr établie avec succès ! (Code 200)');
            } else {
                $this->errors[] = $this->l('Impossible de valider la connexion avec Dolibarr. Vérifiez les logs.');
            }
        }

        if (Tools::isSubmit('submitCreateTestInvoice')) {
            if (!(int)Configuration::get('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE')) {
                $this->errors[] = $this->l('Action impossible : La génération d\'avoirs Dolibarr est actuellement désactivée dans les paramètres.');
            } else {
                require_once dirname(__FILE__) . '/../../src/Service/DolibarrApiClient.php';
                $api_client = new \RetractPlug\Service\DolibarrApiClient();
                
                $test_payload = [
                    'socid' => 3635, 
                    'type' => 2,  
                    'status' => 0, 
                    'date' => time(),
                    'note_private' => "AVOIR DE TEST - Généré manuellement via l'onglet Debug de RetractPlug.",
                    'lines' => [
                        [
                            'desc' => "Article de Test Rétractation",
                            'subprice' => -10.00,
                            'qty' => 1,
                            'tva_tx' => 20.0
                        ]
                    ]
                ];

                $id_invoice = $api_client->createDraftInvoice($test_payload);
                if ($id_invoice) {
                    $this->confirmations[] = sprintf($this->l('Avoir de test #%d créé avec succès en brouillon sur Dolibarr !'), $id_invoice);
                } else {
                    $this->errors[] = $this->l('Échec de la création de l\'avoir de test sur Dolibarr. Vérifiez les logs.');
                }
            }
        }

        if (Tools::isSubmit('submitClearLogs')) {
            $log_file = _PS_ROOT_DIR_ . '/var/logs/retractplug.log';
            if (file_exists($log_file)) {
                file_put_contents($log_file, '');
            }
            $this->confirmations[] = $this->l('Le journal des logs a été réinitialisé.');
        }

        $form_html = $this->renderConfigurationForm();
        $log_file = _PS_ROOT_DIR_ . '/var/logs/retractplug.log';
        $log_content = file_exists($log_file) && filesize($log_file) > 0 ? file_get_contents($log_file) : "--- Aucun log disponible ---";

        $this->context->smarty->assign([
            'form_html' => $form_html,
            'log_content' => $log_content,
            'debug_customer_id' => (int)Configuration::get('RETRACTPLUG_DEBUG_CUSTOMER_ID')
        ]);

        $this->content = $this->context->smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/configure.tpl');

        parent::initContent();
    }

   /**
    * Génération du formulaire de configuration du module
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
        $helper->module = Module::getInstanceByName('retractplug');        
        $helper->default_form_language = $this->context->language->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRetractPlugConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminRetractRequests');
        $helper->token = Tools::getAdminTokenLite('AdminRetractRequests');

        $helper->fields_value['RETRACTPLUG_DOLIBARR_API_URL'] = Configuration::get('RETRACTPLUG_DOLIBARR_API_URL');
        $helper->fields_value['RETRACTPLUG_DOLIBARR_API_KEY'] = Configuration::get('RETRACTPLUG_DOLIBARR_API_KEY');
        $helper->fields_value['RETRACTPLUG_DEBUG_CUSTOMER_ID'] = Configuration::get('RETRACTPLUG_DEBUG_CUSTOMER_ID');
        $helper->fields_value['RETRACTPLUG_GENERATE_DOLIBARR_INVOICE'] = Configuration::get('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE') !== false ? Configuration::get('RETRACTPLUG_GENERATE_DOLIBARR_INVOICE') : 1;
        
        $helper->fields_value['RETRACTPLUG_ALERT_EMAIL'] = Configuration::get('RETRACTPLUG_ALERT_EMAIL');
        $helper->fields_value['RETRACTPLUG_CRON_URL'] = $cron_url;

        $email_desc = $this->l('Adresse qui recevra une notification si l\'API Dolibarr échoue.') . '<br/><br/>' .
            '<button type="submit" name="submitTestEmail" class="btn btn-info">' .
            '<i class="icon-envelope"></i> ' . $this->l('Tester l\'envoi') .
            '</button>';

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Paramètres d\'accès & Outils de test'),
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
                        'label' => $this->l('Générer les avoirs automatiquement sur Dolibarr'),
                        'name' => 'RETRACTPLUG_GENERATE_DOLIBARR_INVOICE',
                        'is_bool' => true,
                        'desc' => $this->l('Si désactivé, le module enregistrera les demandes localement mais n\'enverra rien à Dolibarr (idéal pour la phase de test front).'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('ID Client autorisé pour la Recette (Debug)'),
                        'name' => 'RETRACTPLUG_DEBUG_CUSTOMER_ID',
                        'required' => false,
                        'col' => 2,
                        'desc' => $this->l('Laissez vide en production. Renseignez l\'ID d\'un compte client pour forcer l\'accès au droit de rétractation sur TOUTES ses commandes.'),
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
                        'desc' => $this->l('Copiez cette URL pour configurer votre gestionnaire de tâches (Crontab) afin d\'exécuter la file d\'attente.'),
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
}