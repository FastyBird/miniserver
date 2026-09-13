INSERT INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x3c75e7f0bdfe407a823083dbbbdf0155, 'ns-panel', 'NS Panel', null, true, 'ns-panel-connector', '2023-07-29 16:00:00', '2023-07-29 16:00:00');

INSERT INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x4cfae62109a848c9bd387bdc7e4478f4, _binary 0x3c75e7f0bdfe407a823083dbbbdf0155, 'discover', '2023-07-29 16:00:00', '2023-07-29 16:00:00');

INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x55396c31495a4fa8a52677c5b2988a2e, _binary 0x3c75e7f0bdfe407a823083dbbbdf0155, 'variable', 'mode', 'mode', 0, 0, 'string', null, null, null, null, 'both', '2023-07-29 16:00:00', '2023-07-29 16:00:00');

INSERT INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `connector_id`) VALUES
(_binary 0x896a5f357c9a47f29c72f1520d503364, 'ns-panel-connector-gateway', 'Dummy NS Panel', null, null, null, '2023-07-29 16:00:00', '2023-07-29 16:00:00', _binary 0x3c75e7f0bdfe407a823083dbbbdf0155);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x55396c31495a4fa8a52677c5b2988a2e, _binary 0x896a5f357c9a47f29c72f1520d503364, 'variable', 'ip_address', 'ip_address', 0, 0, 'string', null, null, null, null, '127.0.0.1', '2023-07-29 16:00:00', '2023-07-29 16:00:00'),
(_binary 0x6d4ef5952cff46e287acde852234ff45, _binary 0x896a5f357c9a47f29c72f1520d503364, 'variable', 'access_token', 'access_token', 0, 0, 'string', null, null, null, null, 'abcdefghijklmnopqrstuvwxyz', '2023-07-29 16:00:00', '2023-07-29 16:00:00');
