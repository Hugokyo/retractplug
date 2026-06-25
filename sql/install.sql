CREATE TABLE IF NOT EXISTS `_DB_PREFIX_retractplug_requests` (
    `id_retractplug_request` INT(11) NOT NULL AUTO_INCREMENT,
    `id_order` INT(11) NOT NULL,
    `id_customer` INT(11) NOT NULL,
    `id_dolibarr_invoice` INT(11) DEFAULT NULL,
    `products_data` TEXT NOT NULL,
    `reason` TEXT NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'waiting_package',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_retractplug_request`),
    KEY `id_order` (`id_order`),
    KEY `id_customer` (`id_customer`)
) ENGINE=_MYSQL_ENGINE_ DEFAULT CHARSET=utf8;