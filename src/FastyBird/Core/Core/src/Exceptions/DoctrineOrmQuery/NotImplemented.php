<?php declare(strict_types = 1);

/**
 * NotImplementedException.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        iPublikuj:DoctrineOrmQuery!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           17.05.21
 */

namespace FastyBird\Core\Exceptions\DoctrineOrmQuery;

use FastyBird\Core\Exceptions\Exception;
use RuntimeException;

class NotImplemented extends RuntimeException implements Exception
{

}
