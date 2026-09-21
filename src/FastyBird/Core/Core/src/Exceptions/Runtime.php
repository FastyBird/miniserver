<?php declare(strict_types = 1);

/**
 * Runtime.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException as PHPRuntimeException;

class Runtime extends PHPRuntimeException implements Exception
{

}
