INSERT INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'dummy', 'Dummy', null, true, 'dummy', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'search', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x5a8b01f2621c4c41bc83c089d72b2366, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'variable', 'username', 'username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'random@username', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x28cb8a0fb33d4398bf6c5d1a0594524c, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'variable', 'password', 'password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'secret_password', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `connector_id`) VALUES
(_binary 0x69786D15FD0C4D9F937833287C2009FA, 'dummy', 'first-device', 'First device', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E),
(_binary 0xBF4CD8702AAC45F0A85EE1CEFD2D6D9A, 'dummy', 'second-device', NULL, NULL, NULL, '2020-03-20 21:54:32', '2020-03-20 21:54:32', _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E),
(_binary 0xE36A27881EF84CDFAB094735F191A509, 'dummy', 'third-device', 'Third device', 'Custom comment', NULL, '2020-03-20 21:56:41', '2020-03-20 21:56:41', _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E);

INSERT INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'uptime', 'uptime', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'rssi', 'rssi', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x3FF0029F7FE3405EA3EFEDAAD08E2FFA, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'status_led', 'status_led', 0, 0, 'enum', NULL, 'on,off', NULL, NULL, 'on', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0xC747CFDD654C4E5097156D14DBF20552, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'username', 'username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-username', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x3134BA8EF1344BF29C80C977C4DEB0FB, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'password', 'password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-password', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x03164A6D9628460C95CC90E6216332D9, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'hardware_manufacturer', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'itead', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x06599B7402364A9899C8C459A3CDB6A4, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'hardware_model', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'sonoff_basic', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x090DF4F25F234118A2BD6F0646CF2A70, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'hardware_version', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'rev1', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x0E771233FD5343DDBD24CDA3303F902E, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'hardware_mac_address', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, '807d3a3dbe6d', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x0EB39DAEEF884BB5A9EA94B0B788101F, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'variable', 'firmware_manufacturer', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'fastybird', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x0F87EFBBCB1549CF8B2FF82FF5163C53, _binary 0xE36A27881EF84CDFAB094735F191A509, 'variable', 'hardware_manufacturer', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'fastybird', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x125EFCD0492B4F73B9BD42C07D92CCDF, _binary 0xE36A27881EF84CDFAB094735F191A509, 'variable', 'hardware_model', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'fastybird_wifi_gw', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x145595C21B1E4FC29A0E54D0FA23E230, _binary 0xE36A27881EF84CDFAB094735F191A509, 'variable', 'hardware_version', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'rev1', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x1FD2EB2C087E4400808B6209DFCC5FDA, _binary 0xE36A27881EF84CDFAB094735F191A509, 'variable', 'hardware_mac_address', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, '807d3a3dbe6d', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x21D9F0393A914015A824DD42A5537879, _binary 0xE36A27881EF84CDFAB094735F191A509, 'variable', 'firmware_manufacturer', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'fastybird', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_devices_controls` (`control_id`, `device_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_devices_module_channels` (`channel_id`, `device_id`, `channel_type`, `channel_name`, `channel_comment`, `channel_identifier`, `params`, `created_at`, `updated_at`) VALUES
(_binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dummy', 'Channel one', NULL, 'channel-one', NULL, '2020-03-20 09:22:12', '2020-03-20 22:37:14'),
(_binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dummy', 'Channel two', NULL, 'channel-two', NULL, '2020-03-20 09:22:13', '2020-03-20 09:22:13'),
(_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0xBF4CD8702AAC45F0A85EE1CEFD2D6D9A, 'dummy', NULL, NULL, 'channel-one', NULL, '2020-03-20 09:22:13', '2020-03-20 09:22:13');

INSERT INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'dynamic', 'switch', 'switch', 1, 1, 'enum', NULL, 'on,off,toggle', NULL, NULL, NULL, '2019-12-09 23:19:45', '2019-12-09 23:19:49'),
(_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'dynamic', 'temperature', 'temperature', 0, 1, 'float', '°C', NULL, 999, 1, NULL, '2019-12-08 18:17:39', '2019-12-09 23:09:56'),
(_binary 0x24C436F4A2E44D2BB9101A3FF785B784, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'dynamic', 'humidity', 'humidity', 0, 1, 'float', '%', NULL, 999, 2, NULL, '2019-12-08 18:17:39', '2019-12-09 23:10:00');

INSERT INTO `fb_devices_module_channels_controls` (`control_id`, `channel_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x15DB9BEF3B574A87BF67E3C19FC3BA34, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
(_binary 0x177D6FC719054FD9B847E2DA8189DD6A, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT INTO `fb_ui_module_dashboards` (`dashboard_id`, `dashboard_identifier`, `dashboard_name`, `dashboard_comment`, `dashboard_priority`, `params`, `created_at`, `updated_at`) VALUES
(_binary 0x272379D8835144B6AD8D73A0ABCB7F9C, 'main-dashboard', 'Main dashboard', NULL, 0, '[]', '2020-05-28 10:43:58', '2020-05-28 10:43:58'),
(_binary 0xAB369E71ADA64D1AA5A8B6EE5CD58296, 'first-floor', 'First floor', NULL, 0, '[]', '2020-05-28 11:03:50', '2020-05-28 11:03:50');

INSERT INTO `fb_ui_module_tabs` (`tab_id`, `dashboard_id`, `tab_identifier`, `tab_name`, `tab_comment`, `tab_priority`, `params`, `created_at`, `updated_at`) VALUES
(_binary 0x3333dd94060545b5ac51c08cfb604e64, _binary 0x272379D8835144B6AD8D73A0ABCB7F9C, 'default', 'Default', NULL, 0, '[]', '2020-05-28 11:04:40', '2020-05-28 12:29:32'),
(_binary 0x751b30ba187c477cb1f12da7656abbb5, _binary 0xAB369E71ADA64D1AA5A8B6EE5CD58296, 'default', 'Default', NULL, 0, '[]', '2020-05-28 11:03:33', '2020-05-28 11:03:33');

INSERT INTO `fb_ui_module_groups` (`group_id`, `group_identifier`, `group_name`, `group_comment`, `group_priority`, `params`, `created_at`, `updated_at`) VALUES
(_binary 0x89F4A14F7F78421699B8584AB9229F1C, 'sleeping-room', 'Sleeping room', NULL, 0, '[]', '2020-05-28 11:04:40', '2020-05-28 12:29:32'),
(_binary 0xC74A16B167F44FFD812A9E5EC4BD5263, 'house-heaters', 'House heaters', NULL, 0, '[]', '2020-05-28 11:03:33', '2020-05-28 11:03:33'),
(_binary 0xD721529EDEC647C88035A3484070142B, 'main-lights', 'Main lights', NULL, 0, '[]', '2020-05-28 11:02:59', '2020-05-28 11:02:59');

INSERT INTO `fb_ui_module_widgets` (`widget_id`, `widget_identifier`, `widget_name`, `params`, `created_at`, `updated_at`, `widget_type`) VALUES
(_binary 0x155534434564454DAF040DFEEF08AA96, 'room-temperature', 'Room temperature', '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'analog-sensor'),
(_binary 0x1D60090154E743EE8F5DA9E22663DDD7, 'ambient-light', 'Ambient light', '[]', '2020-05-28 12:27:47', '2020-05-28 12:27:47', 'analog-actuator'),
(_binary 0x5626E7A1C42C4A319B5D848E3CF0E82A, 'tv-light', 'TV light', '[]', '2020-05-28 12:07:27', '2020-05-28 12:07:27', 'digital-actuator'),
(_binary 0x9A91473298DC47F6BFD19D81CA9F8CB6, 'main-light', 'Main light', '[]', '2020-05-28 11:35:44', '2020-05-28 11:35:44', 'digital-actuator');

INSERT INTO `fb_ui_module_widgets_data_sources` (`data_source_id`, `widget_id`, `params`, `created_at`, `updated_at`, `data_source_type`) VALUES
(_binary 0x32DD50E44B664DEA9BC5E835F8543DC4, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '[]', '2020-05-28 12:27:47', '2020-05-28 12:27:47', 'channel-property'),
(_binary 0x764937A78565472E8E12FE97CD55A377, _binary 0x155534434564454DAF040DFEEF08AA96, '[]', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'channel-property');

INSERT INTO `fb_ui_module_widgets_display` (`display_id`, `widget_id`, `params`, `created_at`, `updated_at`, `display_type`) VALUES
(_binary 0x2EA64D790D7D43D9BE3B51F9ADE849FC, _binary 0x5626E7A1C42C4A319B5D848E3CF0E82A, '[]', '2020-05-28 12:07:27', '2020-05-28 12:07:27', 'button'),
(_binary 0x467E6D4D3545481BB61353BE7E9AA641, _binary 0x155534434564454DAF040DFEEF08AA96, '{"precision": 0, "stepValue": 0.1, "maximumValue": 40, "minimumValue": 5}', '2020-05-28 12:29:32', '2020-05-28 12:29:32', 'chart-graph'),
(_binary 0x56A01A188DA14368824E3E1B2E28BDF1, _binary 0x1D60090154E743EE8F5DA9E22663DDD7, '{"precision": 0, "stepValue": 0.1, "maximumValue": 40, "minimumValue": 5}', '2020-05-28 12:27:47', '2020-05-28 12:27:47', 'slider'),
(_binary 0xFD47E9AF1120458F8F30ADEBAC933406, _binary 0x9A91473298DC47F6BFD19D81CA9F8CB6, '[]', '2020-05-28 11:35:44', '2020-05-28 11:35:44', 'button');

INSERT INTO `fb_ui_module_widgets_tabs` (`tab_id`, `widget_id`) VALUES
(_binary 0x3333dd94060545b5ac51c08cfb604e64, _binary 0x155534434564454DAF040DFEEF08AA96),
(_binary 0x3333dd94060545b5ac51c08cfb604e64, _binary 0x1D60090154E743EE8F5DA9E22663DDD7),
(_binary 0x3333dd94060545b5ac51c08cfb604e64, _binary 0x5626E7A1C42C4A319B5D848E3CF0E82A),
(_binary 0x3333dd94060545b5ac51c08cfb604e64, _binary 0x9A91473298DC47F6BFD19D81CA9F8CB6);

INSERT INTO `fb_ui_module_widgets_groups` (`group_id`, `widget_id`) VALUES
(_binary 0x89F4A14F7F78421699B8584AB9229F1C, _binary 0x155534434564454DAF040DFEEF08AA96),
(_binary 0x89F4A14F7F78421699B8584AB9229F1C, _binary 0x1D60090154E743EE8F5DA9E22663DDD7),
(_binary 0x89F4A14F7F78421699B8584AB9229F1C, _binary 0x5626E7A1C42C4A319B5D848E3CF0E82A),
(_binary 0x89F4A14F7F78421699B8584AB9229F1C, _binary 0x9A91473298DC47F6BFD19D81CA9F8CB6);

INSERT INTO `fb_devices_module_ui_module_bridge_channels_data_sources` (`data_source_id`, `data_source_property`) VALUES
(_binary 0x32DD50E44B664DEA9BC5E835F8543DC4, _binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB),
(_binary 0x764937A78565472E8E12FE97CD55A377, _binary 0x28BC0D382F7C4A71AA7427B102F8DF4C);
