<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Hydrators;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Records every value validateLabelAttribute() sees and rejects the label "rejected"
 *
 * @extends Hydrators\Hydrator<DummyCrudEntity>
 */
final class DummyValidatingHydrator extends Hydrators\Hydrator
{

	/** @var array<mixed> */
	public array $validated = [];

	/** @var array<int|string, string> */
	protected array $attributes = [
		'identifier',
		'label',
	];

	public function getEntityName(): string
	{
		return DummyCrudEntity::class;
	}

	/**
	 * Returns a value only to prove that the hydrator ignores it
	 *
	 * @param Objects\StandardObject<string, mixed> $attributes
	 *
	 * @throws Exceptions\JsonApiError
	 */
	protected function validateLabelAttribute(Objects\StandardObject $attributes): string
	{
		$this->validated[] = $attributes->get('label');

		if ($attributes->get('label') === 'rejected') {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				'Invalid label',
				'Label is rejected',
				[
					'pointer' => '/data/attributes/label',
				],
			);
		}

		return 'from the validator';
	}

}
