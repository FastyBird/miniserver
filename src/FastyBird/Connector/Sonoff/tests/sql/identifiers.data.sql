INSERT INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `connector_id`) VALUES
(_binary 0x0f3a0c1e5d2b4a8e9c7d6b5a4f3e2d1c, 'sonoff-connector', 'abcd', 'Longer identifier', NULL, NULL, '2023-10-10 20:00:00', '2023-10-10 20:00:00', _binary 0x63a28696cbea475784d7156e52ade4d3),
(_binary 0x1e4b1d2f6e3c4b9fad8e7c6b5a4f3e2d, 'sonoff-connector', 'abc', 'Shorter identifier', NULL, NULL, '2023-10-10 20:00:00', '2023-10-10 20:00:00', _binary 0x63a28696cbea475784d7156e52ade4d3);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x2a5c2e3f7f4d4caf8e9f8d7c6b5a4f3e, _binary 0x0f3a0c1e5d2b4a8e9c7d6b5a4f3e2d1c, 'dynamic', 'rssi', 'rssi', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x3b6d3f4a8a5e4dbf9fa09e8d7c6b5a4f, _binary 0x1e4b1d2f6e3c4b9fad8e7c6b5a4f3e2d, 'dynamic', 'rssi', 'rssi', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2023-10-10 20:00:00', '2023-10-10 20:00:00');
