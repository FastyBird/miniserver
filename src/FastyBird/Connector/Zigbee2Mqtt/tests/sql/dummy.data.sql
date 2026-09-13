INSERT INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'zigbee2mqtt', 'Zigbee2MQTT', null, true, 'zigbee2mqtt-connector', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xb539c53c4b9d4998a50c13222938f0f4, _binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'reboot', '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x028db566d1124483ab520b7c704f8b55, _binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'discover', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x6c1ad6abc0a04a00965e24387baac0a0, _binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'mqtt', '2023-08-21 22:00:00', '2023-08-21 22:00:00');

INSERT INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `connector_id`) VALUES
(_binary 0xc9cdc7c29ae0433993b718530aec0c42, 'zigbee2mqtt-connector-bridge', 'bridge', 'Zigbee2MQTT Bridge', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', _binary 0xf15d2072fb60421aa85f2566e4dc13fe);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x03042ff4762641518e2d8f4a68517435, _binary 0xc9cdc7c29ae0433993b718530aec0c42, 'dynamic', 'state', 'state', 0, 0, 'enum', NULL, 'connected,disconnected,alert,unknown', NULL, NULL, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x2a1341932a8346cd89c07f346346d1b2, _binary 0xc9cdc7c29ae0433993b718530aec0c42, 'variable', 'base_topic', 'base_topic', 0, 1, 'string', NULL, NULL, NULL, NULL, 'zigbee2mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');
