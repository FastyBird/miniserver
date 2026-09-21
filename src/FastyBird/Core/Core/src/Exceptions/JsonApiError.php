<?php declare(strict_types = 1);

/**
 * JsonApiError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           12.04.19
 */

namespace FastyBird\Core\Exceptions;

use Exception as PHPException;
use Neomerx\JsonApi as NeomerxJsonApi;
use function strval;

/**
 * Process single error
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Exceptions
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class JsonApiError extends PHPException implements Exception, JsonApi
{

	/**
	 * @param array<mixed>|null $source
	 */
	public function __construct(
		int $code,
		string $title,
		private readonly string|null $detail = null,
		private readonly array|null $source = null,
		private readonly string|null $type = null,
	)
	{
		parent::__construct($title, $code);
	}

	public function getError(): NeomerxJsonApi\Schema\Error
	{
		return new NeomerxJsonApi\Schema\Error(
			strval($this->getType()),
			null,
			null,
			(string) $this->code,
			(string) $this->code,
			$this->message,
			strval($this->getDetail()),
			$this->getSource(),
		);
	}

	public function getType(): string|null
	{
		return $this->type;
	}

	public function getDetail(): string|null
	{
		return $this->detail;
	}

	/**
	 * @return array<mixed>|null
	 */
	public function getSource(): array|null
	{
		return $this->source;
	}

}
