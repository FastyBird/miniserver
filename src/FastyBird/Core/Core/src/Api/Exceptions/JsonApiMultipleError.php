<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Exceptions;

use Exception as PHPException;
use Fig\Http\Message\StatusCodeInterface;
use Neomerx\JsonApi as NeomerxJsonApi;

/**
 * Process multiple error
 */
final class JsonApiMultipleError extends PHPException implements JsonApi
{

	/** @var array<NeomerxJsonApi\Schema\Error> */
	private array $errors = [];

	public function __construct()
	{
		parent::__construct(
			'Json:API multiple errors',
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
		);
	}

	/**
	 * @param array<string>|null $source
	 */
	public function addError(
		int $code,
		string $title,
		string|null $detail = null,
		array|null $source = null,
		string|null $type = null,
	): void
	{
		$this->errors[] = new NeomerxJsonApi\Schema\Error(
			$type,
			null,
			null,
			(string) $code,
			(string) $code,
			$title,
			$detail,
			$source,
		);
	}

	public function hasErrors(): bool
	{
		return $this->errors !== [];
	}

	/**
	 * @return array<NeomerxJsonApi\Schema\Error>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

}
