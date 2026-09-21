<?php declare(strict_types = 1);

/**
 * ForbiddenAccess.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           15.07.20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;

class ForbiddenAccess extends RuntimeException implements Exception
{

}
