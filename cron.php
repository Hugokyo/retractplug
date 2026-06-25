<?php
/**
 * 2026 Hugo BOHARD
 * Tâche CRON - Synchronisation asynchrone des avoirs Dolibarr
 */

// 1. Inclusion de l'environnement PrestaShop
require_once dirname(__FILE__) . '/../../config/config.inc.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/retractplug.php';
require_once dirname(__FILE__) . '/classes/RetractRequest.php';
require_once dirname(__FILE__) . '/src/Service/DolibarrApiClient.php';

// 2. Sécurité : Vérification d'un jeton secret pour éviter que n'importe qui lance le CRON
$cron_token = Configuration::get('RETRACTPLUG_CRON_TOKEN');
if (Tools::getValue('token') !== $cron_token) {
    die('Accès refusé : Jeton CRON invalide.');
}

// 3. Récupération des demandes non synchronisées
$pending_requests = Db::getInstance()->executeS('
    SELECT * FROM `' . _DB_PREFIX_ . 'retractplug_requests`
    WHERE `id_dolibarr_invoice` IS NULL OR `id_dolibarr_invoice` = 0
    LIMIT 10
');

if (empty($pending_requests)) {
    die('Aucune demande en attente de synchronisation.');
}

$api_client = new \RetractPlug\Service\DolibarrApiClient();

foreach ($pending_requests as $req) {
    $order = new Order((int)$req['id_order']);
    $products_data = json_decode($req['products_data'], true);
    
    // Reconstruction du payload pour Dolibarr
    $invoice_lines = [];
    foreach ($products_data as $prod) {
        // Logique de reconstruction de vos lignes (identique à votre contrôleur front)
        $invoice_lines[] = [
            'desc' => '[Retour #' . $order->reference . '] ' . $prod['name'],
            'subprice' => -((float)$prod['unit_price_tax_excl']), // Exemple, à adapter selon vos données stockées
            'qty' => (int)$prod['quantity'],
            'tva_tx' => 20.0
        ];
    }

    $payload = [
        'socid' => 66796, // ID Tiers réel
        'type' => 2,
        'status' => 0,
        'date' => time(),
        'note_private' => "Avoir généré via CRON de secours. Commande #" . $order->reference,
        'lines' => $invoice_lines
    ];

    // Tentative d'appel API
    $id_invoice = $api_client->createDraftInvoice($payload);

    if ($id_invoice) {
        // Succès : Mise à jour de la ligne en BDD locale
        Db::getInstance()->update(
            'retractplug_requests',
            ['id_dolibarr_invoice' => (int)$id_invoice],
            'id_retractplug_request = ' . (int)$req['id_retractplug_request']
        );
        echo "Demande #" . $req['id_retractplug_request'] . " synchronisée avec succès (Avoir #" . $id_invoice . ").<br/>";
    } else {
        echo "Échec de synchronisation pour la demande #" . $req['id_retractplug_request'] . " (Dolibarr toujours inaccessible).<br/>";
    }
}