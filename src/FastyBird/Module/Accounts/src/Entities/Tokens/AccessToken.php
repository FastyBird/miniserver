<?php declare(strict_types = 1);

/**
 * AccessToken.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Entities\Tokens;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Entities\DoctrineTimestampable;
use FastyBird\Core\Entities\SimpleAuth as SimpleAuthEntities;
use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use Ramsey\Uuid;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: 'access_token')]
class AccessToken extends SimpleAuthEntities\Tokens\Token implements
	Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\IEntityCreated,
	DoctrineTimestampable\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\TEntityCreated;
	use DoctrineTimestampable\TEntityUpdated;

	public const TOKEN_EXPIRATION = '+6 hours';

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\ManyToOne(targetEntity: Entities\Identities\Identity::class)]
	#[ORM\JoinColumn(
		name: 'identity_id',
		referencedColumnName: 'identity_id',
		nullable: true,
		onDelete: 'CASCADE',
	)]
	private Entities\Identities\Identity|null $identity = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'token_valid_till', type: 'datetime_immutable', nullable: false)]
	private DateTimeInterface|null $validTill = null;

	public function __construct(
		Entities\Identities\Identity $identity,
		string $token,
		DateTimeInterface|null $validTill,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($token, $id);

		$this->identity = $identity;
		$this->validTill = $validTill;
	}

	public function setRefreshToken(RefreshToken $refreshToken): void
	{
		parent::addChild($refreshToken);
	}

	public function getRefreshToken(): RefreshToken|null
	{
		$token = $this->children->first();

		if ($token instanceof RefreshToken) {
			return $token;
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getIdentity(): Entities\Identities\Identity
	{
		if ($this->identity === null) {
			throw new Exceptions\InvalidState('Identity is not set to token.');
		}

		return $this->identity;
	}

	public function getValidTill(): DateTimeInterface|null
	{
		return $this->validTill;
	}

	public function isValid(DateTimeInterface $dateTime): bool
	{
		if ($this->validTill === null) {
			return true;
		}

		return $this->validTill >= $dateTime;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
		];
	}

}
