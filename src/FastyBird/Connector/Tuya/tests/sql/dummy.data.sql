INSERT INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'tuya-cloud', 'Tuya Cloud', null, true, 'tuya-connector', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x27848e3c23b44aaaa94eaae975d98550, 'tuya-local', 'Tuya Local', null, true, 'tuya-connector', '2023-08-21 22:00:00', '2023-08-21 22:00:00');

INSERT INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xc3b18d17ba764bdc9564ec57ae0ac44d, _binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'reboot', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x240427dae7f34f9c8859185484ab0912, _binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'discover', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0xdf7f7afcd2f74e18b6138e1343620de5, _binary 0x27848e3c23b44aaaa94eaae975d98550, 'reboot', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x0ff07b0aa6874f6c84158b3460a23efe, _binary 0x27848e3c23b44aaaa94eaae975d98550, 'discover', '2023-08-21 22:00:00', '2023-08-21 22:00:00');

INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xf6c6f15f39954487911bb7bcdbba5d32, _binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'cloud', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'variable', 'access_id', 'Access ID', 0, 0, 'string', NULL, NULL, NULL, NULL, 'MftAcceHZL11BpOR', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'variable', 'access_secret', 'Access Secret', 0, 0, 'string', NULL, NULL, NULL, NULL, 'dBCQZohQNR2U4rW9', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x9d5e7eb4a5b945e599318412854969e6, _binary 0x74fba85a95854f5fa7cc8d3806da5ada, 'variable', 'string', 'UID', 0, 0, 'string', NULL, NULL, NULL, NULL, 'Bjhq01pE7q4ijNMN', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x67a1cda6a52a4cb8b5c0c6e037853fe9, _binary 0x27848e3c23b44aaaa94eaae975d98550, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'local', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x3f54a1bca6f642399747a4730f471fcc, _binary 0x27848e3c23b44aaaa94eaae975d98550, 'variable', 'access_id', 'Access ID', 0, 0, 'string', NULL, NULL, NULL, NULL, 'MftAcceHZL11BpOR', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0xd168ec28b6f44764af45111a0e106375, _binary 0x27848e3c23b44aaaa94eaae975d98550, 'variable', 'access_secret', 'Access Secret', 0, 0, 'string', NULL, NULL, NULL, NULL, 'dBCQZohQNR2U4rW9', '2023-08-21 22:00:00', '2023-08-21 22:00:00'),
(_binary 0x1aea75a55cfb40eba40a53d95dfbe3bc, _binary 0x27848e3c23b44aaaa94eaae975d98550, 'variable', 'string', 'UID', 0, 0, 'string', NULL, NULL, NULL, NULL, 'Bjhq01pE7q4ijNMN', '2023-08-21 22:00:00', '2023-08-21 22:00:00');
