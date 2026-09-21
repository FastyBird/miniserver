<?php declare(strict_types = 1);

/**
 * FileNotFound.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           08.05.21
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;

class FileNotFound extends RuntimeException implements Exception
{

}
