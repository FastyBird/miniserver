<?php declare(strict_types = 1);

/**
 * Identity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Accounts\Documents\Identities;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Documents\Exchange as ExchangeDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Identity entity
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Identities\Identity::class)]
#[ExchangeDocuments\Mapping\RoutingMap([
	Accounts\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_REPORTED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_CREATED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_UPDATED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_DELETED_ROUTING_KEY,
])]
final readonly class Identity implements ApplicationDocuments\Document
{

	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $account,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\IdentityState::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\IdentityState::class),
		])]
		private Types\IdentityState $state,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $uid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $hash = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getAccount(): Uuid\UuidInterface
	{
		return $this->account;
	}

	public function getState(): Types\IdentityState
	{
		return $this->state;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getHash(): string|null
	{
		return $this->hash;
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::ACCOUNTS;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'account' => $this->getAccount()->toString(),
			'state' => $this->getState()->value,
			'uid' => $this->getUid(),
			'hash' => $this->getHash(),
		];
	}

}
