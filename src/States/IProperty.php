<?php declare(strict_types = 1);

/**
 * IProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\MiniServer\States;

use DateTimeInterface;

/**
 * Property state entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IProperty
{
	/**
	 * @param string|null $actual
	 *
	 * @return void
	 */
	public function setActual(?string $actual): void;

	/**
	 * @param string|null $expected
	 *
	 * @return void
	 */
	public function setExpected(?string $expected): void;

	/**
	 * @param string|null $created
	 *
	 * @return void
	 */
	public function setCreated(?string $created): void;

	/**
	 * @return DateTimeInterface|null
	 */
	public function getCreated(): ?DateTimeInterface;

	/**
	 * @param string|null $updated
	 *
	 * @return void
	 */
	public function setUpdated(?string $updated): void;

	/**
	 * @return DateTimeInterface|null
	 */
	public function getUpdated(): ?DateTimeInterface;

}
