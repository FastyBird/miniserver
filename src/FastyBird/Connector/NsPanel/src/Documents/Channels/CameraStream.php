<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\CameraStream::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\CameraStream::TYPE)]
final class CameraStream extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\CameraStream::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::CAMERA_STREAM->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
