INSERT INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x86d6a4ba62924947b8c18681569ac28e, 'shelly-local', 'Shelly Local', null, true, 'shelly-connector', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xf56f419d79724d8b8f253c75e34c1c42, _binary 0x86d6a4ba62924947b8c18681569ac28e, 'reboot', '2023-09-04 10:00:00', '2023-09-04 10:00:00'),
(_binary 0x40cc4a777fbf45e8b3c49fccc8203cb4, _binary 0x86d6a4ba62924947b8c18681569ac28e, 'discover', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xca00cb856d60456ca29abc1538e8b683, _binary 0x86d6a4ba62924947b8c18681569ac28e, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'local', '2023-09-04 10:00:00', '2023-09-04 10:00:00');
