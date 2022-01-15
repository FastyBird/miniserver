<?php declare(strict_types = 1);

/**
 * LogicException.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\Exceptions;

use LogicException as PHPLogicException;

class LogicException extends PHPLogicException implements IException
{

}
