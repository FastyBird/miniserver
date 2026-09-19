<?php declare(strict_types = 1);

/**
 * Sms.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Triggers\Documents\Notifications;

use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Library\Phone\Entities as PhoneEntities;
use FastyBird\Library\Phone\Exceptions as PhoneExceptions;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * SMS notification document
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Notifications\Sms::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Notifications\Sms::TYPE)]
final class Sms extends Notification
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		string $type,
		bool $enabled,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $phone,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $owner);
	}

	/**
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function getPhone(): PhoneEntities\Phone
	{
		return PhoneEntities\Phone::fromNumber($this->phone);
	}

	/**
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'phone' => $this->getPhone()->getInternationalNumber(),
		]);
	}

}
