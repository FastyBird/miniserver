INSERT INTO `fb_triggers_module_triggers` (`trigger_id`, `trigger_type`, `trigger_name`, `trigger_comment`, `trigger_enabled`, `created_at`, `updated_at`, `params`) VALUES
(_binary 0x0B48DFBCFAC2429288DC7981A121602D, 'automatic', 'Good Evening', NULL, 1, '2020-01-27 20:49:53', '2020-01-27 20:49:53', '[]'),
(_binary 0x1B17BCAAA19E45F098B456211CC648AE, 'automatic', 'Rise n\'Shine', NULL, 1, '2020-01-27 14:24:34', '2020-01-27 14:24:34', '[]'),
(_binary 0x2CEA2C1B47904D828A9F902C7155AB36, 'automatic', 'House keeping', NULL, 1, '2020-01-27 14:25:19', '2020-01-27 14:25:19', '[]'),
(_binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'manual', 'Movie Night', NULL, 1, '2020-01-27 14:25:54', '2020-01-27 14:27:15', '[]'),
(_binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'manual', 'Bubble Bath', NULL, 1, '2020-01-27 14:27:40', '2020-01-29 22:16:47', '[]'),
(_binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'manual', 'Good Night\'s Sleep', NULL, 1, '2020-01-27 14:28:17', '2020-01-27 14:28:17', '[]');

INSERT INTO `fb_triggers_module_triggers_controls` (`control_id`, `trigger_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'trigger', '2020-01-27 14:25:54', '2020-01-27 14:25:54'),
(_binary 0xCFCA08FFD19948ED9F008C6B840A567A, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'trigger', '2020-01-27 14:27:40', '2020-01-27 14:27:40'),
(_binary 0x177D6FC719054FD9B847E2DA8189DD6A, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'trigger', '2020-01-27 14:28:17', '2020-01-27 14:28:17');

INSERT INTO `fb_triggers_module_conditions` (`condition_id`, `trigger_id`, `created_at`, `updated_at`, `condition_type`, `condition_time`, `condition_days`) VALUES
(_binary 0x09C453B3C55F40508F1CB50F8D5728C2, _binary 0x1B17BCAAA19E45F098B456211CC648AE, '2020-01-27 14:24:34', '2020-01-27 14:24:34', 'time', '07:30:00', '1,2,3,4,5,6,7'),
(_binary 0x167900E919F34712AA4D00B160FF06D5, _binary 0x0B48DFBCFAC2429288DC7981A121602D, '2020-01-27 20:49:53', '2020-01-27 20:49:53', 'time', '18:00:00', '1,2,3,4,5,6,7');

INSERT INTO `fb_triggers_module_notifications` (`notification_id`, `trigger_id`, `created_at`, `updated_at`, `notification_type`, `notification_email`, `notification_phone`) VALUES
(_binary 0x05F28DF95F194923B3F8B9090116DADC, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, '2020-04-06 13:16:17', '2020-04-06 13:16:17', 'email', 'john.doe@fastybird.com', NULL),
(_binary 0x4FE1019CF49E4CBF83E620B394E76317, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, '2020-04-06 13:27:07', '2020-04-06 13:27:07', 'sms', NULL, '+420778776776');
