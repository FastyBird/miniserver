<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Utilities;

use function preg_replace;
use function strtolower;
use function strval;

final class Api
{

	public static function fieldToJsonApi(string $field): string
	{
		return strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $field)));
	}

}
