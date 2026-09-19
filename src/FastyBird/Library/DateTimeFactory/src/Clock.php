<?php declare(strict_types = 1);

/**
 * Clock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeFactory!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\DateTimeFactory;

use DateTimeInterface;

interface Clock
{

	public function getNow(): DateTimeInterface;

}
