<?php
/**
 * 2026 Hugo BOHARD
 */

namespace RetractPlug\Model;

use HTMLTemplate;
use Order;
use Customer;
use Context;
use Configuration;

class HTMLTemplateBordereauReturn extends HTMLTemplate
{
    public $retract_request;
    public $order;

    public function __construct($retract_request, $smarty)
    {
        $this->retract_request = $retract_request;
        $this->order = new Order((int)$retract_request->id_order);
        $this->smarty = $smarty;

        // Configuration de la mise en page de base de PrestaShop
        $this->context = Context::getContext();
        $this->shop = $this->context->shop;
    }

    /**
     * Rendu effectif du contenu HTML du PDF
     */
    public function getContent()
    {
        $customer = new Customer((int)$this->order->id_customer);
        $products = json_decode($this->retract_request->products_data, true);

        // Récupération sécurisée du chemin absolu du logo de la boutique
        $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO');
        if (!file_exists($logo)) {
            $logo = false;
        }

        $this->smarty->assign([
            'request' => $this->retract_request,
            'order' => $this->order,
            'customer' => $customer,
            'returned_products' => $products,
            'logo' => $logo, // On passe le logo ici
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'shop_address' => Configuration::get('PS_SHOP_ADDR1') . ' ' . Configuration::get('PS_SHOP_ADDR2'),
            'shop_postcode' => Configuration::get('PS_SHOP_CODE'),
            'shop_city' => Configuration::get('PS_SHOP_CITY'),
        ]);

        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'retractplug/views/templates/pdf/bordereau_content.tpl');
    }

    /**
     * Renvoie le nom du fichier PDF généré au téléchargement
     */
    public function getFilename()
    {
        return 'bordereau-retour-' . $this->order->reference . '.pdf';
    }

    /**
     * NOUVEAU : Surcharge obligatoire pour éviter l'erreur de classe abstraite
     * Renvoie le nom du fichier par défaut lors d'un téléchargement groupé
     */
    public function getBulkFilename()
    {
        return 'bordereaux-retours.pdf';
    }

    /**
     * Surcharge obligatoire pour le Header global
     */
    public function getHeader()
    {
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'retractplug/views/templates/pdf/bordereau_header.tpl');
    }

    /**
     * Surcharge obligatoire pour le Footer global
     */
    public function getFooter()
    {
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'retractplug/views/templates/pdf/bordereau_footer.tpl');
    }
}