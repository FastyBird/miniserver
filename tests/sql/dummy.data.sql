INSERT IGNORE INTO `fb_connectors` (`connector_id`, `connector_name`, `connector_key`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`)
VALUES (_binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'Virtual', 'bLikvZ', true, 'virtual', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT IGNORE INTO `fb_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`)
VALUES (_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'search', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT IGNORE INTO `fb_devices` (`device_id`, `parent_id`, `device_type`, `device_identifier`, `device_key`, `device_name`, `device_comment`, `device_enabled`, `device_hardware_manufacturer`, `device_hardware_model`, `device_hardware_version`, `device_hardware_mac_address`, `device_firmware_manufacturer`, `device_firmware_version`, `params`, `created_at`, `updated_at`, `owner`, `connector_id`)
VALUES (_binary 0x69786D15FD0C4D9F937833287C2009FA, NULL, 'virtual', 'first-device', 'bLikkz', 'First device', NULL, 1, 'itead', 'sonoff_basic', 'rev1', '807d3a3dbe6d', 'fastybird', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', '455354e8-96bd-4c29-84e7-9f10e1d4db4b', _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E),
       (_binary 0xBF4CD8702AAC45F0A85EE1CEFD2D6D9A, NULL, 'virtual', 'second-device', 'bLijjH', NULL, NULL, 1, 'generic', 'custom', NULL, NULL, 'generic', NULL, NULL, '2020-03-20 21:54:32', '2020-03-20 21:54:32', '455354e8-96bd-4c29-84e7-9f10e1d4db4b', NULL),
       (_binary 0xE36A27881EF84CDFAB094735F191A509, NULL, 'virtual', 'third-device', 'bLijlz', 'Third device', 'Custom comment', 1, 'fastybird', 'fastybird_wifi_gw', 'rev1', '807d3a3dbe6d', 'fastybird', NULL, NULL, '2020-03-20 21:56:41', '2020-03-20 21:56:41', '455354e8-96bd-4c29-84e7-9f10e1d4db4b', NULL),
       (_binary 0xA1036FF86EE84405AAED58BAE0814596, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'virtual', 'child-device', 'bLikzr', 'Child device', 'This is child', 1, 'generic', 'custom', NULL, NULL, 'generic', NULL, NULL, '2020-03-20 21:56:41', '2020-03-20 21:56:41', NULL, NULL);

INSERT IGNORE INTO `fb_devices_properties` (`property_id`, `device_id`, `property_type`, `property_key`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_number_of_decimals`, `property_value`, `created_at`, `updated_at`)
VALUES (_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'bLikpV', 'uptime', 'uptime', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
       (_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'dynamic', 'bLikvh', 'rssi', 'rssi', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
       (_binary 0x3FF0029F7FE3405EA3EFEDAAD08E2FFA, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'static', 'bLikvt', 'status_led', 'status_led', 0, 0, 'enum', NULL, 'on,off', NULL, NULL, 'on', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
       (_binary 0xC747CFDD654C4E5097156D14DBF20552, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'static', 'bLykvt', 'username', 'username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-username', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
       (_binary 0x3134BA8EF1344BF29C80C977C4DEB0FB, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'static', 'bLykvV', 'password', 'password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-password', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT IGNORE INTO `fb_devices_controls` (`control_id`, `device_id`, `control_name`, `created_at`, `updated_at`)
VALUES (_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT IGNORE INTO `fb_channels` (`channel_id`, `device_id`, `channel_key`, `channel_name`, `channel_comment`, `channel_identifier`, `params`, `created_at`, `updated_at`)
VALUES (_binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'bLikxh', 'Channel one', NULL, 'channel-one', NULL, '2020-03-20 09:22:12', '2020-03-20 22:37:14'),
       (_binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, _binary 0x69786D15FD0C4D9F937833287C2009FA, 'bLikxq', 'Channel two', NULL, 'channel-two', NULL, '2020-03-20 09:22:13', '2020-03-20 09:22:13'),
       (_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0xBF4CD8702AAC45F0A85EE1CEFD2D6D9A, 'bLikxv', NULL, NULL, 'channel-one', NULL, '2020-03-20 09:22:13', '2020-03-20 09:22:13');

INSERT IGNORE INTO `fb_channels_properties` (`property_id`, `channel_id`, `property_type`, `property_key`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_number_of_decimals`, `property_value`, `created_at`, `updated_at`)
VALUES (_binary 0xBBCCCF8C33AB431BA795D7BB38B6B6DB, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'dynamic', 'bLikx4', 'switch', 'switch', 1, 1, 'enum', NULL, 'on,off,toggle', NULL, NULL, NULL, '2019-12-09 23:19:45', '2019-12-09 23:19:49'),
       (_binary 0x28BC0D382F7C4A71AA7427B102F8DF4C, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'dynamic', 'bLikxE', 'temperature', 'temperature', 0, 1, 'float', '°C', NULL, 999, 1, NULL, '2019-12-08 18:17:39', '2019-12-09 23:09:56'),
       (_binary 0x24C436F4A2E44D2BB9101A3FF785B784, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'dynamic', 'bLikxM', 'humidity', 'humidity', 0, 1, 'float', '%', NULL, 999, 2, NULL, '2019-12-08 18:17:39', '2019-12-09 23:10:00');

INSERT IGNORE INTO `fb_channels_controls` (`control_id`, `channel_id`, `control_name`, `created_at`, `updated_at`)
VALUES (_binary 0x15DB9BEF3B574A87BF67E3C19FC3BA34, _binary 0x17C59DFA2EDD438E8C49FAA4E38E5A5E, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20'),
       (_binary 0x177D6FC719054FD9B847E2DA8189DD6A, _binary 0x6821F8E9AE694D5C9B7CD2B213F1AE0A, 'configure', '2020-03-20 09:18:20', '2020-03-20 09:18:20');

INSERT IGNORE INTO `fb_triggers` (`trigger_id`, `trigger_type`, `trigger_name`, `trigger_comment`, `trigger_enabled`, `created_at`, `updated_at`, `params`)
VALUES (_binary 0x0B48DFBCFAC2429288DC7981A121602D, 'automatic', 'Environment', NULL, 1, '2020-01-27 20:49:53', '2020-01-27 20:49:53', '[]');

INSERT IGNORE INTO `fb_conditions` (`condition_id`, `trigger_id`, `created_at`, `updated_at`, `condition_type`, `condition_time`, `condition_days`, `condition_device`, `condition_channel`, `condition_channel_property`, `condition_operator`, `condition_operand`)
VALUES (_binary 0x167900E919F34712AA4D00B160FF06D5, _binary 0x0B48DFBCFAC2429288DC7981A121602D, '2020-01-27 20:49:53', '2020-01-27 20:49:53', 'channel-property', null, null, _binary 0x69786d15fd0c4d9f937833287c2009fa, _binary 0x6821f8e9ae694d5c9b7cd2b213f1ae0a, _binary 0x28bc0d382f7c4a71aa7427b102f8df4c, 'below', '20');

INSERT IGNORE INTO `fb_actions` (`action_id`, `trigger_id`, `action_type`, `created_at`, `updated_at`, `action_device`, `action_channel`, `action_channel_property`, `action_value`)
VALUES (_binary 0x21D13F148BE0462587644D5B1F3B4D1E, _binary 0x0B48DFBCFAC2429288DC7981A121602D, 'channel-property', '2020-01-28 18:39:35', '2020-01-28 18:39:35', _binary 0x69786d15fd0c4d9f937833287c2009fa, _binary 0x17c59dfa2edd438e8c49faa4e38e5a5e, _binary 0xbbcccf8c33ab431ba795d7bb38b6b6db, 'on');
