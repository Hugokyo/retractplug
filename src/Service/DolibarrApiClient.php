<?php
/**
 * 2026 Hugo BOHARD
 */

namespace RetractPlug\Service;

use Configuration;

class DolibarrApiClient
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        // Récupération des paramètres de configuration enregistrés en Back-Office PrestaShop
        $this->apiUrl = rtrim(Configuration::get('RETRACTPLUG_DOLIBARR_API_URL'), '/');
        $this->apiKey = Configuration::get('RETRACTPLUG_DOLIBARR_API_KEY');
    }

    /**
     * Envoie une requête d'API (GET, POST, etc.) vers Dolibarr
     * 
     * @param string $endpoint Exemple: '/factures'
     * @param string $method 'GET', 'POST', 'PUT'
     * @param array $data Données à encoder en JSON
     * @return array|bool Réponse décodée ou false en cas d'erreur
     */
    public function call($endpoint, $method = 'GET', $data = null)
    {
        // On s'assure de récupérer la config propre
        $url_base = Configuration::get('RETRACTPLUG_DOLIBARR_API_URL');
        $api_key = Configuration::get('RETRACTPLUG_DOLIBARR_API_KEY');

        // Nettoyage des slashes pour éviter les doubles slashes optionnels
        $url = rtrim($url_base, '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // IMPORTANT : Sécurité SSL parfois stricte en local/test
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        // Injection obligatoire de la clé d'API dans les headers du cURL
        $headers = [
            'DOLAPIKEY: ' . $api_key,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // ---- Écriture automatique dans ton fichier de log pour voir ce qui cloche ----
        $log_file = _PS_ROOT_DIR_ . '/var/logs/retractplug.log';
        $log_msg = date('[Y-m-d H:i:s]') . " APPEL: $method $url | HTTP CODE: $http_code | RESPONSE: $response\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);

        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        }

        return false;
    }

    /**
     * Crée une facture de type avoir (Brouillon) sur Dolibarr
     * 
     * @param array $invoiceData Données structurées de l'avoir
     * @return int|bool ID de l'avoir créé sur Dolibarr ou false
     */
    public function createDraftInvoice($payload)
    {
        // C'est ici qu'il faut impérativement remplacer '/factures' par '/invoices'
        $response = $this->call('/invoices', 'POST', $payload);

        if ($response && is_array($response) && isset($response['id'])) {
            return (int)$response['id'];
        }

        if ($response && is_numeric($response)) {
            return (int)$response;
        }

        return false;
    }
}