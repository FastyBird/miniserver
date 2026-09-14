<?php declare(strict_types = 1);

/**
 * UnexpectedValueException.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           04.01.16
 */

namespace FastyBird\Library\DoctrineTimestampable\Exceptions;

use UnexpectedValueException as PHPUnexpectedValueException;

class UnexpectedValue extends PHPUnexpectedValueException implements Exception
{

}
