<?php declare(strict_types = 1);

/**
 * InvalidArgumentException.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           22.12.20
 */

namespace FastyBird\MiniServer\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgumentException extends PHPInvalidArgumentException implements IException
{

}
