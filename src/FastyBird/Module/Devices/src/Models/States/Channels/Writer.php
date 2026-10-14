<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Channel properties manager interface
 *
 * The values to store come as a plain array or as an ArrayHash (#723). An implementation stores an
 * array exactly as the ArrayHash Utils\ArrayHash::from() makes of it.
 */
interface Writer
{

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\ChannelProperty;

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\ChannelProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
