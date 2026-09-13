INSERT INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x37b86cdc376b4d4c9683aa4f41daa13a, 'fb-mqtt', 'FB MQTT', null, true, 'fb-mqtt-connector', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x37b86cdc376b4d4c9683aa4f41daa13a, 'variable', 'protocol', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'v1', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `connector_id`) VALUES
(_binary 0x69786D15FD0C4D9F937833287C2009FA, 'fb-mqtt-connector', 'first-device', 'First device', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', _binary 0x37b86cdc376b4d4c9683aa4f41daa13a);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'uptime', 'uptime', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'rssi', 'rssi', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x3FF0029F7FE3405EA3EFEDAAD08E2FFA, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'status_led', 'status_led', 0, 0, 'enum', NULL, 'on,off', NULL, NULL, 'on', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0xC747CFDD654C4E5097156D14DBF20552, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'username', 'username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-username', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x3134BA8EF1344BF29C80C977C4DEB0FB, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'password', 'password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-password', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_devices_controls` (`control_id`, `device_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_channels` (`channel_id`, `device_id`, `channel_type`, `channel_name`, `channel_comment`, `channel_identifier`, `params`, `created_at`, `updated_at`) VALUES
(_binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'fb-mqtt-connector', 'Channel one', NULL, 'channel-one', NULL, '2020-03-20 09:22:12', '2020-03-20 22:37:14'),
(_binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'fb-mqtt-connector', 'Channel two', NULL, 'channel-two', NULL, '2020-03-20 09:22:13', '2020-03-20 09:22:13');

INSERT INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'dynamic', 'switch', 'switch', 1, 1, 'enum', NULL, 'on,off,toggle', NULL, NULL, NULL, '2019-12-09 23:19:45', '2019-12-09 23:19:49'),
(_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'dynamic', 'temperature', 'temperature', 0, 1, 'float', '°C', NULL, 999, 1, NULL, '2019-12-08 18:17:39', '2019-12-09 23:09:56'),
(_binary 0x24C436F4A2E44D2BB9101A3FF785B784, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'dynamic', 'humidity', 'humidity', 0, 1, 'float', '%', NULL, 999, 2, NULL, '2019-12-08 18:17:39', '2019-12-09 23:10:00');

INSERT INTO `fb_devices_module_channels_controls` (`control_id`, `channel_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x15DB9BEF3B574A87BF67E3C19FC3BA34, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x177D6FC719054FD9B847E2DA8189DD6A, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20');
