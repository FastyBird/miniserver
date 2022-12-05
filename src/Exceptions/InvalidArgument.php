<?php declare(strict_types = 1);

/**
 * InvalidArgument.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           23.02.20
 */

namespace FastyBird\MiniServer\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
