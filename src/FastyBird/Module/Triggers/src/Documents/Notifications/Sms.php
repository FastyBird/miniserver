<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Notifications;

use FastyBird\Core\Documents;
use FastyBird\Core\Phone\Entities as PhoneEntities;
use FastyBird\Core\Phone\Exceptions;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * SMS notification document
 */
#[Documents\Mapping\Document(entity: TriggersEntities\Notifications\Sms::class)]
#[Documents\Mapping\DiscriminatorEntry(name: TriggersEntities\Notifications\Sms::TYPE)]
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
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	public function getPhone(): PhoneEntities\Phone
	{
		return PhoneEntities\Phone::fromNumber($this->phone);
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'phone' => $this->getPhone()->getInternationalNumber(),
		]);
	}

}
