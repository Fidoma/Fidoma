CREATE TABLE IF NOT EXISTS `ip_audit_logs` (
  `audit_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(32) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `status` varchar(32) NOT NULL,
  `entity_type` varchar(32) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `message` varchar(500) NOT NULL,
  PRIMARY KEY (`audit_log_id`),
  KEY `idx_audit_created_at` (`created_at`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_bank_transaction_mappings` (
  `bank_transaction_mapping_id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_transaction_id` bigint(20) unsigned NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `payment_created_by_mapping` tinyint(1) NOT NULL DEFAULT 0,
  `document_id` int(10) unsigned DEFAULT NULL,
  `bank_transaction_mapping_date_created` datetime NOT NULL,
  `bank_transaction_mapping_date_modified` datetime NOT NULL,
  PRIMARY KEY (`bank_transaction_mapping_id`),
  UNIQUE KEY `uq_bank_transaction_mapping_document_transaction` (`bank_transaction_id`,`document_id`),
  KEY `idx_bank_transaction_mapping_transaction` (`bank_transaction_id`),
  KEY `idx_bank_transaction_mapping_payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_bank_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_iban` varchar(34) NOT NULL,
  `booking_date` date NOT NULL,
  `value_date` date DEFAULT NULL,
  `amount` decimal(19,4) NOT NULL,
  `currency` char(3) NOT NULL,
  `purpose` text DEFAULT NULL,
  `counterparty_name` varchar(255) DEFAULT NULL,
  `counterparty_iban` varchar(34) DEFAULT NULL,
  `booking_text` varchar(255) DEFAULT NULL,
  `transaction_reference` char(64) NOT NULL,
  `bank_transaction_id` varchar(255) DEFAULT NULL,
  `bank_reference` varchar(255) DEFAULT NULL,
  `end_to_end_id` varchar(255) DEFAULT NULL,
  `pending` tinyint(1) NOT NULL DEFAULT 0,
  `validated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transaction_reference` (`transaction_reference`),
  KEY `idx_open_transactions` (`validated`,`pending`,`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_client_custom` (
  `client_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `client_custom_fieldid` int(11) NOT NULL,
  `client_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`client_custom_id`),
  UNIQUE KEY `client_id` (`client_id`,`client_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_client_notes` (
  `client_note_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `client_note_date` date NOT NULL,
  `client_note` longtext NOT NULL,
  PRIMARY KEY (`client_note_id`),
  KEY `client_id` (`client_id`,`client_note_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_clients` (
  `client_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_date_created` datetime NOT NULL,
  `client_date_modified` datetime NOT NULL,
  `client_name` text DEFAULT NULL,
  `client_company` varchar(150) DEFAULT NULL,
  `client_address_1` text DEFAULT NULL,
  `client_address_2` text DEFAULT NULL,
  `client_city` text DEFAULT NULL,
  `client_state` text DEFAULT NULL,
  `client_zip` text DEFAULT NULL,
  `client_country` text DEFAULT NULL,
  `client_phone` text DEFAULT NULL,
  `client_fax` text DEFAULT NULL,
  `client_mobile` text DEFAULT NULL,
  `client_email` text DEFAULT NULL,
  `client_web` text DEFAULT NULL,
  `client_vat_id` text DEFAULT NULL,
  `client_tax_code` text DEFAULT NULL,
  `client_language` varchar(255) DEFAULT 'system',
  `client_active` int(1) NOT NULL DEFAULT 1,
  `client_surname` varchar(255) DEFAULT NULL,
  `client_invoicing_contact` varchar(50) DEFAULT NULL,
  `client_title` varchar(50) DEFAULT NULL,
  `client_einvoicing_active` tinyint(1) NOT NULL DEFAULT 0,
  `client_einvoicing_version` varchar(25) DEFAULT NULL,
  `client_avs` varchar(16) DEFAULT NULL,
  `client_insurednumber` varchar(30) DEFAULT NULL,
  `client_veka` varchar(30) DEFAULT NULL,
  `client_birthdate` date DEFAULT NULL,
  `client_gender` int(1) DEFAULT 0,
  PRIMARY KEY (`client_id`),
  KEY `client_active` (`client_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_custom_fields` (
  `custom_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `custom_field_table` varchar(50) DEFAULT NULL,
  `custom_field_label` varchar(50) DEFAULT NULL,
  `custom_field_type` varchar(255) NOT NULL DEFAULT 'TEXT',
  `custom_field_location` int(11) DEFAULT 0,
  `custom_field_item_slot` tinyint(3) unsigned DEFAULT NULL,
  `custom_field_order` int(11) DEFAULT 999,
  PRIMARY KEY (`custom_field_id`),
  UNIQUE KEY `custom_field_table_2` (`custom_field_table`,`custom_field_label`),
  UNIQUE KEY `uq_custom_fields_invoice_item_slot` (`custom_field_table`,`custom_field_location`,`custom_field_item_slot`),
  KEY `custom_field_table` (`custom_field_table`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_custom_values` (
  `custom_values_id` int(11) NOT NULL AUTO_INCREMENT,
  `custom_values_field` int(11) NOT NULL,
  `custom_values_value` text NOT NULL,
  PRIMARY KEY (`custom_values_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_email_templates` (
  `email_template_id` int(11) NOT NULL AUTO_INCREMENT,
  `email_template_title` text DEFAULT NULL,
  `email_template_type` varchar(255) DEFAULT NULL,
  `email_template_body` longtext NOT NULL,
  `email_template_subject` text DEFAULT NULL,
  `email_template_from_name` text DEFAULT NULL,
  `email_template_from_email` text DEFAULT NULL,
  `email_template_cc` text DEFAULT NULL,
  `email_template_bcc` text DEFAULT NULL,
  `email_template_pdf_template` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`email_template_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_employee_employment` (
  `employment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `employment_type_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `job_title` varchar(150) NOT NULL,
  `workplace` varchar(200) NOT NULL,
  `weekly_hours` decimal(5,2) NOT NULL,
  `remuneration` decimal(12,2) NOT NULL,
  `pay_period` varchar(10) NOT NULL,
  PRIMARY KEY (`employment_id`),
  KEY `employment_employee` (`employee_id`,`start_date`),
  KEY `employee_employment_type_fk` (`employment_type_id`),
  CONSTRAINT `employee_employment_person_fk` FOREIGN KEY (`employee_id`) REFERENCES `ip_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `employee_employment_type_fk` FOREIGN KEY (`employment_type_id`) REFERENCES `ip_employee_employment_types` (`employment_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_employee_employment_types` (
  `employment_type_id` int(10) unsigned NOT NULL,
  `code` varchar(32) NOT NULL,
  PRIMARY KEY (`employment_type_id`),
  UNIQUE KEY `employment_type_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_employee_minijob` (
  `employment_id` int(10) unsigned NOT NULL,
  `pension_exempt` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`employment_id`),
  CONSTRAINT `employee_minijob_employment_fk` FOREIGN KEY (`employment_id`) REFERENCES `ip_employee_employment` (`employment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_employee_short_term` (
  `employment_id` int(10) unsigned NOT NULL,
  `previous_days` smallint(5) unsigned NOT NULL,
  `professional` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`employment_id`),
  CONSTRAINT `employee_short_term_employment_fk` FOREIGN KEY (`employment_id`) REFERENCES `ip_employee_employment` (`employment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_employee_social_insurance` (
  `employment_id` int(10) unsigned NOT NULL,
  `health_insurer` varchar(150) NOT NULL,
  PRIMARY KEY (`employment_id`),
  CONSTRAINT `employee_social_employment_fk` FOREIGN KEY (`employment_id`) REFERENCES `ip_employee_employment` (`employment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_employees` (
  `employee_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `birth_country` varchar(100) NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `street` varchar(200) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `social_security_number` char(12) NOT NULL,
  `tax_id` char(11) NOT NULL,
  PRIMARY KEY (`employee_id`),
  KEY `employee_name` (`last_name`,`first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_families` (
  `family_id` int(11) NOT NULL AUTO_INCREMENT,
  `family_name` text DEFAULT NULL,
  PRIMARY KEY (`family_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_import_details` (
  `import_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` int(11) NOT NULL,
  `import_lang_key` varchar(35) NOT NULL,
  `import_table_name` varchar(35) NOT NULL,
  `import_record_id` int(11) NOT NULL,
  PRIMARY KEY (`import_detail_id`),
  KEY `import_id` (`import_id`,`import_record_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_imports` (
  `import_id` int(11) NOT NULL AUTO_INCREMENT,
  `import_date` datetime NOT NULL,
  PRIMARY KEY (`import_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_amounts` (
  `invoice_amount_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `invoice_sign` enum('1','-1') NOT NULL DEFAULT '1',
  `invoice_item_subtotal` decimal(20,2) DEFAULT NULL,
  `invoice_item_tax_total` decimal(20,2) DEFAULT NULL,
  `invoice_tax_total` decimal(20,2) DEFAULT NULL,
  `invoice_total` decimal(20,2) DEFAULT NULL,
  `invoice_paid` decimal(20,2) DEFAULT NULL,
  `invoice_balance` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`invoice_amount_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `invoice_paid` (`invoice_paid`,`invoice_balance`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_custom` (
  `invoice_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `invoice_custom_fieldid` int(11) NOT NULL,
  `invoice_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`invoice_custom_id`),
  UNIQUE KEY `invoice_id` (`invoice_id`,`invoice_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_groups` (
  `invoice_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_group_name` text DEFAULT NULL,
  `invoice_group_identifier_format` varchar(255) NOT NULL,
  `invoice_group_next_id` int(11) NOT NULL,
  `invoice_group_left_pad` int(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`invoice_group_id`),
  KEY `invoice_group_next_id` (`invoice_group_next_id`),
  KEY `invoice_group_left_pad` (`invoice_group_left_pad`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_item_amounts` (
  `item_amount_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `item_subtotal` decimal(20,2) DEFAULT NULL,
  `item_tax_total` decimal(20,2) DEFAULT NULL,
  `item_discount` decimal(20,2) DEFAULT NULL,
  `item_total` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`item_amount_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `item_tax_rate_id` int(11) NOT NULL DEFAULT 0,
  `item_tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `item_product_id` int(11) DEFAULT NULL,
  `item_date_added` date NOT NULL,
  `item_task_id` int(11) DEFAULT NULL,
  `item_name` text DEFAULT NULL,
  `item_description` longtext DEFAULT NULL,
  `item_quantity` decimal(20,8) DEFAULT NULL,
  `item_price` decimal(20,2) DEFAULT NULL,
  `item_discount_amount` decimal(20,2) DEFAULT NULL,
  `item_order` int(2) NOT NULL DEFAULT 0,
  `item_is_recurring` tinyint(1) DEFAULT NULL,
  `item_product_unit` varchar(50) DEFAULT NULL,
  `item_product_unit_id` int(11) DEFAULT NULL,
  `item_date` date DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `invoice_id` (`invoice_id`,`item_tax_rate_id`,`item_date_added`,`item_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_items_custom` (
  `invoice_item_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `invoice_item_custom_fieldid` int(11) NOT NULL,
  `invoice_item_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`invoice_item_custom_id`),
  UNIQUE KEY `uq_invoice_item_custom_field` (`item_id`,`invoice_item_custom_fieldid`),
  KEY `idx_invoice_item_custom_field` (`invoice_item_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_sumex` (
  `sumex_id` int(11) NOT NULL AUTO_INCREMENT,
  `sumex_invoice` int(11) NOT NULL,
  `sumex_reason` int(11) NOT NULL,
  `sumex_diagnosis` varchar(500) NOT NULL,
  `sumex_observations` varchar(500) NOT NULL,
  `sumex_treatmentstart` date NOT NULL,
  `sumex_treatmentend` date NOT NULL,
  `sumex_casedate` date NOT NULL,
  `sumex_casenumber` varchar(35) DEFAULT NULL,
  PRIMARY KEY (`sumex_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoice_tax_rates` (
  `invoice_tax_rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `tax_rate_id` int(11) NOT NULL,
  `include_item_tax` int(1) NOT NULL DEFAULT 0,
  `invoice_tax_rate_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`invoice_tax_rate_id`),
  KEY `invoice_id` (`invoice_id`,`tax_rate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoices` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_group_id` int(11) NOT NULL,
  `invoice_status_id` tinyint(2) NOT NULL DEFAULT 1,
  `is_read_only` tinyint(1) DEFAULT NULL,
  `invoice_password` varchar(90) DEFAULT NULL,
  `invoice_date_created` date NOT NULL,
  `invoice_time_created` time NOT NULL DEFAULT '00:00:00',
  `invoice_date_modified` datetime NOT NULL,
  `invoice_date_due` date NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_discount_amount` decimal(20,2) DEFAULT NULL,
  `invoice_discount_percent` decimal(20,2) DEFAULT NULL,
  `invoice_terms` longtext NOT NULL,
  `invoice_url_key` char(32) NOT NULL,
  `payment_method` int(11) NOT NULL DEFAULT 0,
  `creditinvoice_parent_id` int(11) DEFAULT NULL,
  `invoice_einvoicing_version` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `invoice_url_key` (`invoice_url_key`),
  KEY `user_id` (`user_id`,`client_id`,`invoice_group_id`,`invoice_date_created`,`invoice_date_due`,`invoice_number`),
  KEY `invoice_status_id` (`invoice_status_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_invoices_recurring` (
  `invoice_recurring_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `recur_start_date` date NOT NULL,
  `recur_end_date` date DEFAULT NULL,
  `recur_frequency` varchar(255) NOT NULL,
  `recur_next_date` date DEFAULT NULL,
  PRIMARY KEY (`invoice_recurring_id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_item_lookups` (
  `item_lookup_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL DEFAULT '',
  `item_description` longtext NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_lookup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_login_log` (
  `login_name` varchar(100) NOT NULL,
  `log_count` int(11) DEFAULT 0,
  `log_create_timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`login_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_merchant_responses` (
  `merchant_response_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `merchant_response_successful` tinyint(1) DEFAULT 1,
  `merchant_response_date` date NOT NULL,
  `merchant_response_driver` varchar(35) NOT NULL,
  `merchant_response` varchar(255) NOT NULL,
  `merchant_response_reference` varchar(255) NOT NULL,
  PRIMARY KEY (`merchant_response_id`),
  KEY `merchant_response_date` (`merchant_response_date`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_paperless_zip_imports` (
  `paperless_zip_import_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_key` char(64) NOT NULL,
  `mailbox` varchar(255) NOT NULL,
  `message_uid` bigint(20) unsigned NOT NULL,
  `attachment_part` varchar(50) NOT NULL,
  `attachment_filename` varchar(255) NOT NULL,
  `received_at` date DEFAULT NULL,
  `sender` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `discovered_at` datetime DEFAULT NULL,
  `imported_at` datetime DEFAULT NULL,
  PRIMARY KEY (`paperless_zip_import_id`),
  UNIQUE KEY `uq_paperless_zip_import_source` (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_payment_custom` (
  `payment_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `payment_custom_fieldid` int(11) NOT NULL,
  `payment_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`payment_custom_id`),
  UNIQUE KEY `payment_id` (`payment_id`,`payment_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_payment_methods` (
  `payment_method_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_method_name` text DEFAULT NULL,
  PRIMARY KEY (`payment_method_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL DEFAULT 0,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(20,2) DEFAULT NULL,
  `payment_note` longtext NOT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `payment_amount` (`payment_amount`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `family_id` int(11) DEFAULT NULL,
  `product_sku` text DEFAULT NULL,
  `product_name` text DEFAULT NULL,
  `product_description` longtext NOT NULL,
  `product_price` decimal(20,2) DEFAULT NULL,
  `purchase_price` decimal(20,2) DEFAULT NULL,
  `provider_name` text DEFAULT NULL,
  `tax_rate_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `product_tariff` int(11) DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `project_name` text DEFAULT NULL,
  PRIMARY KEY (`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_quote_amounts` (
  `quote_amount_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `quote_item_subtotal` decimal(20,2) DEFAULT NULL,
  `quote_item_tax_total` decimal(20,2) DEFAULT NULL,
  `quote_tax_total` decimal(20,2) DEFAULT NULL,
  `quote_total` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`quote_amount_id`),
  KEY `quote_id` (`quote_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_quote_custom` (
  `quote_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `quote_custom_fieldid` int(11) NOT NULL,
  `quote_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`quote_custom_id`),
  UNIQUE KEY `quote_id` (`quote_id`,`quote_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_quote_item_amounts` (
  `item_amount_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `item_subtotal` decimal(20,2) DEFAULT NULL,
  `item_tax_total` decimal(20,2) DEFAULT NULL,
  `item_discount` decimal(20,2) DEFAULT NULL,
  `item_total` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`item_amount_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_quote_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `item_tax_rate_id` int(11) NOT NULL,
  `item_tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `item_product_id` int(11) DEFAULT NULL,
  `item_date_added` date NOT NULL,
  `item_name` text DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `item_quantity` decimal(20,8) DEFAULT NULL,
  `item_price` decimal(20,2) DEFAULT NULL,
  `item_discount_amount` decimal(20,2) DEFAULT NULL,
  `item_order` int(2) NOT NULL DEFAULT 0,
  `item_product_unit` varchar(50) DEFAULT NULL,
  `item_product_unit_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `quote_id` (`quote_id`,`item_date_added`,`item_order`),
  KEY `item_tax_rate_id` (`item_tax_rate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_quote_tax_rates` (
  `quote_tax_rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `tax_rate_id` int(11) NOT NULL,
  `include_item_tax` int(1) NOT NULL DEFAULT 0,
  `quote_tax_rate_amount` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`quote_tax_rate_id`),
  KEY `quote_id` (`quote_id`),
  KEY `tax_rate_id` (`tax_rate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_quotes` (
  `quote_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_group_id` int(11) NOT NULL,
  `quote_status_id` tinyint(2) NOT NULL DEFAULT 1,
  `quote_date_created` date NOT NULL,
  `quote_date_modified` datetime NOT NULL,
  `quote_date_expires` date NOT NULL,
  `quote_number` varchar(100) DEFAULT NULL,
  `quote_discount_amount` decimal(20,2) DEFAULT NULL,
  `quote_discount_percent` decimal(20,2) DEFAULT NULL,
  `quote_url_key` char(32) NOT NULL,
  `notes` longtext DEFAULT NULL,
  PRIMARY KEY (`quote_id`),
  KEY `user_id` (`user_id`,`client_id`,`invoice_group_id`,`quote_date_created`,`quote_date_expires`,`quote_number`),
  KEY `invoice_id` (`invoice_id`),
  KEY `quote_status_id` (`quote_status_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_reservation_amounts` (
  `reservation_amount_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `reservation_item_subtotal` decimal(20,2) DEFAULT NULL,
  `reservation_item_tax_total` decimal(20,2) DEFAULT NULL,
  `reservation_tax_total` decimal(20,2) DEFAULT NULL,
  `reservation_total` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`reservation_amount_id`),
  KEY `reservation_id` (`reservation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_reservation_custom` (
  `reservation_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `reservation_custom_fieldid` int(11) NOT NULL,
  `reservation_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`reservation_custom_id`),
  UNIQUE KEY `reservation_field` (`reservation_id`,`reservation_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_reservation_item_amounts` (
  `item_amount_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `item_subtotal` decimal(20,2) DEFAULT NULL,
  `item_tax_total` decimal(20,2) DEFAULT NULL,
  `item_discount` decimal(20,2) DEFAULT NULL,
  `item_total` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`item_amount_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_reservation_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `item_tax_rate_id` int(11) NOT NULL,
  `item_tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `item_product_id` int(11) DEFAULT NULL,
  `item_date_added` date NOT NULL,
  `item_name` text DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `item_quantity` decimal(20,8) DEFAULT NULL,
  `item_price` decimal(20,2) DEFAULT NULL,
  `item_discount_amount` decimal(20,2) DEFAULT NULL,
  `item_order` int(2) NOT NULL DEFAULT 0,
  `item_product_unit` varchar(50) DEFAULT NULL,
  `item_product_unit_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `reservation_id` (`reservation_id`,`item_date_added`,`item_order`),
  KEY `item_tax_rate_id` (`item_tax_rate_id`),
  KEY `room_id` (`room_id`),
  KEY `reservation_period` (`arrival_date`,`departure_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_reservation_tax_rates` (
  `reservation_tax_rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `tax_rate_id` int(11) NOT NULL,
  `include_item_tax` int(1) NOT NULL DEFAULT 0,
  `reservation_tax_rate_amount` decimal(20,2) DEFAULT NULL,
  PRIMARY KEY (`reservation_tax_rate_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `tax_rate_id` (`tax_rate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_group_id` int(11) NOT NULL,
  `reservation_status_id` tinyint(2) NOT NULL DEFAULT 1,
  `reservation_date_created` date NOT NULL,
  `reservation_date_modified` datetime NOT NULL,
  `reservation_date_expires` date NOT NULL,
  `reservation_number` varchar(100) DEFAULT NULL,
  `reservation_discount_amount` decimal(20,2) DEFAULT NULL,
  `reservation_discount_percent` decimal(20,2) DEFAULT NULL,
  `reservation_url_key` char(32) NOT NULL,
  `notes` longtext DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `reservation_lookup` (`user_id`,`client_id`,`invoice_group_id`,`reservation_date_created`,`reservation_date_expires`,`reservation_number`),
  KEY `invoice_id` (`invoice_id`),
  KEY `reservation_status_id` (`reservation_status_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `room_capacity` smallint(5) unsigned NOT NULL DEFAULT 1,
  `room_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_active` (`room_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  KEY `ip_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` longtext NOT NULL,
  PRIMARY KEY (`setting_id`),
  KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_name` text DEFAULT NULL,
  `task_description` longtext NOT NULL,
  `task_price` decimal(20,2) DEFAULT NULL,
  `task_finish_date` date NOT NULL,
  `task_status` tinyint(1) NOT NULL,
  `tax_rate_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_tax_rates` (
  `tax_rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_rate_name` text DEFAULT NULL,
  `tax_rate_percent` decimal(5,2) NOT NULL,
  PRIMARY KEY (`tax_rate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_units` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(50) DEFAULT NULL,
  `unit_name_plrl` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`unit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_uploads` (
  `upload_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `url_key` char(32) NOT NULL,
  `file_name_original` longtext NOT NULL,
  `file_name_new` longtext NOT NULL,
  `uploaded_date` date NOT NULL,
  PRIMARY KEY (`upload_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_user_clients` (
  `user_client_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  PRIMARY KEY (`user_client_id`),
  KEY `user_id` (`user_id`,`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_user_custom` (
  `user_custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_custom_fieldid` int(11) NOT NULL,
  `user_custom_fieldvalue` text DEFAULT NULL,
  PRIMARY KEY (`user_custom_id`),
  UNIQUE KEY `user_id` (`user_id`,`user_custom_fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ip_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` int(1) NOT NULL DEFAULT 0,
  `user_active` tinyint(1) DEFAULT 1,
  `user_date_created` datetime NOT NULL,
  `user_date_modified` datetime NOT NULL,
  `user_language` varchar(255) DEFAULT 'system',
  `user_name` text DEFAULT NULL,
  `user_company` text DEFAULT NULL,
  `user_address_1` text DEFAULT NULL,
  `user_address_2` text DEFAULT NULL,
  `user_city` text DEFAULT NULL,
  `user_state` text DEFAULT NULL,
  `user_zip` text DEFAULT NULL,
  `user_country` text DEFAULT NULL,
  `user_invoicing_contact` varchar(50) DEFAULT NULL,
  `user_phone` text DEFAULT NULL,
  `user_fax` text DEFAULT NULL,
  `user_mobile` text DEFAULT NULL,
  `user_email` text DEFAULT NULL,
  `user_password` varchar(60) NOT NULL,
  `user_web` text DEFAULT NULL,
  `user_vat_id` text DEFAULT NULL,
  `user_tax_code` text DEFAULT NULL,
  `user_psalt` text DEFAULT NULL,
  `user_all_clients` int(1) NOT NULL DEFAULT 0,
  `user_passwordreset_token` varchar(100) DEFAULT '',
  `user_passwordreset_token_expiry` datetime DEFAULT NULL,
  `user_force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `user_subscribernumber` varchar(40) DEFAULT NULL,
  `user_bank` varchar(100) DEFAULT NULL,
  `user_iban` varchar(34) DEFAULT NULL,
  `user_bic` varchar(11) DEFAULT NULL,
  `user_remittance_text` varchar(105) DEFAULT NULL,
  `user_gln` bigint(13) DEFAULT NULL,
  `user_rcc` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ip_versions` (
  `version_id` int(11) NOT NULL AUTO_INCREMENT,
  `version_date_applied` varchar(14) NOT NULL,
  `version_file` varchar(45) NOT NULL,
  `version_sql_errors` int(2) NOT NULL,
  PRIMARY KEY (`version_id`),
  KEY `version_date_applied` (`version_date_applied`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

