<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Exchange
 * @since          1.0.0
 *
 * @date           28.03.23
 */

namespace FastyBird\Core\Messaging\Exchange;

/**
 * Exchange factory interface
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Exchange
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Factory
{

	public function create(): void;

}
