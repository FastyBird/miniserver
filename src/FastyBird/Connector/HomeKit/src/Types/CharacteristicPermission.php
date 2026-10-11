<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP accessory permissions type
 */
enum CharacteristicPermission: string
{

	case HIDDEN = 'hd';

	case NOTIFY = 'ev';

	case READ = 'pr';

	case WRITE = 'pw';

	case WRITE_RESPONSE = 'wr';

	case TIMED_WRITE = 'tw';

	case ADDITIONAL_AUTHORIZATION = 'aa';

}
