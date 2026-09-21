<?php declare(strict_types = 1);

/**
 * QueryNotImplemented.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           17.05.21
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;

class QueryNotImplemented extends RuntimeException implements Exception
{

}
