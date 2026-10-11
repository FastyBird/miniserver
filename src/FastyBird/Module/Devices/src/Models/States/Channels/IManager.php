<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Channel properties manager interface
 */
interface IManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
