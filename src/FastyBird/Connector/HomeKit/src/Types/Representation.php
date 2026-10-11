<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP Representation type
 */
enum Representation: string
{

	case ACCS = 'accessories';

	case AID = 'aid';

	case CHARS = 'characteristics';

	case DESC = 'description';

	case FORMAT = 'format';

	case IID = 'iid';

	case MAX_LEN = 'maxLen';

	case PERM = 'perms';

	case PID = 'pid';

	case PRIMARY = 'primary';

	case HIDDEN = 'hidden';

	case SERVICES = 'services';

	case LINKED = 'linked';

	case STATUS = 'status';

	case TTL = 'ttl';

	case TYPE = 'type';

	case VALUE = 'value';

	case VALID_VALUES = 'valid-values';

	case MAX_VALUE = 'maxValue';

	case MIN_STEP = 'minStep';

	case MIN_VALUE = 'minValue';

	case UNIT = 'unit';

	case META = 'meta';

}
