<?php declare(strict_types = 1);

/**
 * TEntityUpdated.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           06.01.15
 */

namespace FastyBird\Library\DoctrineTimestampable\Entities;

use DateTimeInterface;
use FastyBird\Library\DoctrineTimestampable\Mapping\Annotation as IPub;

/**
 * Doctrine timestampable modifying entity
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
trait TEntityUpdated
{

	#[IPub\Timestampable(on: 'update')]
	protected DateTimeInterface|null $updatedAt = null;

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(DateTimeInterface $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

}
