<?php declare(strict_types = 1);

/**
 * TOwner.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           05.06.22
 */

namespace FastyBird\Core\Documents\Application;

use Ramsey\Uuid;

/**
 * Document owner trait
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Uuid\UuidInterface|null $owner
 */
trait TOwner
{

	public function getOwner(): Uuid\UuidInterface|null
	{
		return $this->owner;
	}

}
