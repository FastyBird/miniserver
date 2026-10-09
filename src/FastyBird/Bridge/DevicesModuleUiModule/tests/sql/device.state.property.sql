INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x5C1AE8A06F0B4B7E9C2D3E4F5A6B7C8D, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'state', 'state', 0, 0, 'enum', NULL, 'connected,disconnected,running,sleeping,stopped,lost,alert,unknown', NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_ui_module_widgets_data_sources` (`data_source_id`, `widget_id`, `params`, `created_at`, `updated_at`, `data_source_type`) VALUES
(_binary 0xD1A5C6E20B7F4C3A8E9D1F2A3B4C5D6E, _binary 0x155534434564454DAF040DFEEF08AA96, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'device-property');

INSERT INTO `fb_devices_module_ui_module_bridge_devices_data_sources` (`data_source_id`, `data_source_property`) VALUES
(_binary 0xD1A5C6E20B7F4C3A8E9D1F2A3B4C5D6E, _binary 0x5C1AE8A06F0B4B7E9C2D3E4F5A6B7C8D);
