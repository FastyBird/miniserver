INSERT INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `connector_id`) VALUES
(_binary 0x4F9A1C2E7B3D4E8FA6C51D2E3F4A5B6C, 'dummy', 'foreign-device', 'Foreign device', NULL, NULL, '2020-03-20 21:56:41', '2020-03-20 21:56:41', _binary 0x7A3DD94C729446FD8C611B375C313D4D);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `parent_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x9E8D7C6B5A494382B716A5F4E3D2C1B0, _binary 0x4F9A1C2E7B3D4E8FA6C51D2E3F4A5B6C, _binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, 'mapped', 'uptime', 'Mapped uptime', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `parent_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x2B7E4C913D5A4F688E19C0A7B6D5E4F3, _binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, 'mapped', 'temperature', 'Mapped temperature', 0, 1, 'float', '°C', NULL, 999, 1, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20');
