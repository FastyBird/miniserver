<?php declare(strict_types = 1);

/**
 * WampNotImplemented.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 */

namespace FastyBird\Core\Exceptions;

use Nette;

class WampNotImplemented extends Nette\NotImplementedException implements Exception
{

}
