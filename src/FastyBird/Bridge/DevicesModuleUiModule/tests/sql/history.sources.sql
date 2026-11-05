INSERT INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `parent_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x7A1C0E2D3B4A4C5F8D6E9F0A1B2C3D4E, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, _binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, 'mapped', 'mapped-temperature', 'Mapped temperature', 0, 1, 'float', '°F', NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x8B2D1F3E4C5B4D6A9E7F0A1B2C3D4E5F, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'variable', 'label', 'label', 0, 0, 'string', NULL, NULL, NULL, NULL, 'Hall', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_ui_module_widgets_data_sources` (`data_source_id`, `widget_id`, `params`, `created_at`, `updated_at`, `data_source_type`) VALUES
(_binary 0xC1D2E3F405164718A9B0C1D2E3F40516, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'channel-property'),
(_binary 0xD1E2F30415264738B9C0D1E2F3041526, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'channel-property'),
(_binary 0xF1A2B3C4D5E64758698A9B0C1D2E3F40, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'connector-property'),
(_binary 0xB1C2D3E4F5A64B7C8D9E0F1A2B3C4D5E, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'device-property'),
(_binary 0x0A1B2C3D4E5F4A6B8C7D9E0F1A2B3C4D, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'generic');

INSERT INTO `fb_devices_module_ui_module_bridge_channels_data_sources` (`data_source_id`, `data_source_property`) VALUES
(_binary 0xC1D2E3F405164718A9B0C1D2E3F40516, _binary 0x7A1C0E2D3B4A4C5F8D6E9F0A1B2C3D4E),
(_binary 0xD1E2F30415264738B9C0D1E2F3041526, _binary 0x8B2D1F3E4C5B4D6A9E7F0A1B2C3D4E5F);

INSERT INTO `fb_devices_module_ui_module_bridge_connectors_data_sources` (`data_source_id`, `data_source_property`) VALUES
(_binary 0xF1A2B3C4D5E64758698A9B0C1D2E3F40, _binary 0xA4C2E6F80B1D4F3A9C5E7A9B1D3F5A70);

INSERT INTO `fb_devices_module_ui_module_bridge_devices_data_sources` (`data_source_id`, `data_source_property`) VALUES
(_binary 0xB1C2D3E4F5A64B7C8D9E0F1A2B3C4D5E, _binary 0x28BC0D382F7C4A71AA7427B102F8DF4C);

INSERT INTO `fb_ui_module_widgets_generic_data_sources` (`data_source_id`) VALUES
(_binary 0x0A1B2C3D4E5F4A6B8C7D9E0F1A2B3C4D);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `parent_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x9C3E1F2A4B5D4C6E8F7A0B1C2D3E4F50, _binary 0x69786D15FD0C4D9F937833287C2009FA, _binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, 'mapped', 'mapped-rssi', 'Mapped rssi', 0, 1, 'int', 'dBm', NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_ui_module_widgets_data_sources` (`data_source_id`, `widget_id`, `params`, `created_at`, `updated_at`, `data_source_type`) VALUES
(_binary 0xA2B3C4D5E6F74A8B9C0D1E2F3A4B5C6D, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'device-property');

INSERT INTO `fb_devices_module_ui_module_bridge_devices_data_sources` (`data_source_id`, `data_source_property`) VALUES
(_binary 0xA2B3C4D5E6F74A8B9C0D1E2F3A4B5C6D, _binary 0x9C3E1F2A4B5D4C6E8F7A0B1C2D3E4F50);
