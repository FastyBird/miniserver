<?php declare(strict_types = 1);

/**
 * InvalidStateException.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        iPublikuj:DoctrineOrmQuery!
 * @subpackage     Exceptions
 * @since          0.0.1
 *
 * @date           10.11.19
 */

namespace FastyBird\Library\DoctrineOrmQuery\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
