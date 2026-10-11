<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\ValueObjects;

use Nette\Utils;
use Orisai\ObjectMapper;
use Override;

/**
 * Websocket frame
 */
final readonly class WsFrame implements ObjectMapper\MappedObject
{

	/**
	 * @param array<string, mixed> $params
	 * @param array<string, string|int> $auth
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $src,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $method,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\MixedValue(),
				new ObjectMapper\Rules\StringValue(notEmpty: true),
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private array|null $params = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\AnyOf([
					new ObjectMapper\Rules\StringValue(notEmpty: true),
					new ObjectMapper\Rules\IntValue(),
				]),
				new ObjectMapper\Rules\StringValue(notEmpty: true),
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private array|null $auth = null,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getSrc(): string
	{
		return $this->src;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getParams(): array|null
	{
		return $this->params;
	}

	/**
	 * @return array<string, string|int>|null
	 */
	public function getAuth(): array|null
	{
		return $this->auth;
	}

	#[Override]
	public function __toString(): string
	{
		try {
			$data = [
				'id' => $this->getId(),
				'src' => $this->getSrc(),
				'method' => $this->getMethod(),
				'params' => $this->getParams(),
				'auth' => $this->getAuth(),
			];

			if ($data['params'] === null) {
				unset($data['params']);
			}

			if ($data['auth'] === null) {
				unset($data['auth']);
			}

			return Utils\Json::encode($data);
		} catch (Utils\JsonException) {
			return '{}';
		}
	}

}
