<?php
/**
 * 2026 Hugo BOHARD
 */

class RetractRequest extends ObjectModel
{
    public $id_order;
    public $id_customer;
    public $id_dolibarr_invoice;
    public $products_data;
    public $reason;
    public $status;
    public $date_add;
    public $date_upd;

    /**
     * Définition de la structure de l'objet pour l'ORM de PrestaShop
     */
    public static $definition = [
        'table' => 'retractplug_requests',
        'primary' => 'id_retractplug_request',
        'multilang' => false,
        'fields' => [
            'id_order' =>            ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' =>         ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_dolibarr_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'products_data' =>       ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'reason' =>              ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'status' =>              ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32],
            'date_add' =>            ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' =>            ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}