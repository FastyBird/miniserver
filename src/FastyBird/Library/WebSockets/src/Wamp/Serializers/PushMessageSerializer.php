<?php declare(strict_types = 1);

/**
 * PushMessageSerializer.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Serializers
 * @since          1.0.0
 *
 * @date           28.02.17
 */

namespace FastyBird\Library\WebSockets\Wamp\Serializers;

use FastyBird\Library\WebSockets\Wamp\Entities;
use Nette;
use Symfony\Component\Serializer;

/**
 * Push message data serializer
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Serializers
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class PushMessageSerializer
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private Serializer\Serializer $serializer;

	/** @var array<Serializer\Normalizer\NormalizerInterface> */
	private array $normalizers;

	private string $class;

	/** @var array<Serializer\Encoder\EncoderInterface> */
	private array $encoders;

	public function __construct()
	{
		$this->normalizers = [
			new Serializer\Normalizer\GetSetMethodNormalizer(),
		];

		$this->encoders = [
			new Serializer\Encoder\JsonEncoder(),
		];

		$this->serializer = new Serializer\Serializer($this->normalizers, $this->encoders);
	}

	public function serialize(Entities\PushMessages\IMessage $message): string
	{
		$this->class = $message::class;

		return $this->serializer->serialize($message, 'json');
	}

	public function deserialize(string $data): Entities\PushMessages\IMessage
	{
		$class = $this->class ?? Entities\PushMessages\Message::class;

		return $this->serializer->deserialize($data, $class, 'json');
	}

}
