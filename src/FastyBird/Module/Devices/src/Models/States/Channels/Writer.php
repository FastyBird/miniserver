<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;

/**
 * Channel properties manager interface
 */
interface Writer
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array $values): States\ChannelProperty;

	/**
	 * @param array<string, mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array $values): States\ChannelProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
