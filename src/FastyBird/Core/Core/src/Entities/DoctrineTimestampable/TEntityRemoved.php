<?php declare(strict_types = 1);

/**
 * TEntityRemoved.php
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

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;
use FastyBird\Core\Mapping\DoctrineTimestampable\Annotation as IPub;

/**
 * Doctrine timestampable removing entity
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
trait TEntityRemoved
{

	#[IPub\Timestampable(on: 'delete')]
	protected DateTimeInterface|null $deletedAt = null;

	public function getDeletedAt(): DateTimeInterface|null
	{
		return $this->deletedAt;
	}

	public function setDeletedAt(DateTimeInterface $deletedAt): void
	{
		$this->deletedAt = $deletedAt;
	}

}
