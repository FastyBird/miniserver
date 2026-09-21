<?php declare(strict_types = 1);

/**
 * RefreshToken.php
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
use function sprintf;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: 'refresh_token')]
class RefreshToken extends SimpleAuthEntities\Tokens\Token implements
	Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\IEntityCreated,
	DoctrineTimestampable\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\TEntityCreated;
	use DoctrineTimestampable\TEntityUpdated;

	public const TOKEN_EXPIRATION = '+3 days';

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'token_valid_till', type: 'datetime_immutable', nullable: false)]
	private DateTimeInterface|null $validTill = null;

	public function __construct(
		AccessToken $accessToken,
		string $token,
		DateTimeInterface|null $validTill,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($token, $id);

		$this->validTill = $validTill;

		$this->setParent($accessToken);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getAccessToken(): AccessToken
	{
		$token = parent::getParent();

		if (!$token instanceof AccessToken) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Access token for refresh token is not valid type. Instance of %s expected, %s provided',
					AccessToken::class,
					$token !== null ? $token::class : 'null',
				),
			);
		}

		return $token;
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
