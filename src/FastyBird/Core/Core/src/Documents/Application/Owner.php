<?php declare(strict_types = 1);

/**
 * Owner.php
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
 * Data document owner interface
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Owner
{

	public function getOwner(): Uuid\UuidInterface|null;

}
