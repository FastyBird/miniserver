<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel motor calibration channel entity schema
 *
 * @extends Channel<Entities\Channels\MotorCalibration>
 */
final class MotorCalibration extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\MotorCalibration::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\MotorCalibration::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
