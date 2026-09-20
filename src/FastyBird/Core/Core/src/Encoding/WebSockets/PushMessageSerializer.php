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

namespace FastyBird\Core\Encoding\WebSockets;

use FastyBird\Core\Entities\WebSockets\PushMessages as Entities;
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

	public function serialize(Entities\IMessage $message): string
	{
		$this->class = $message::class;

		return $this->serializer->serialize($message, 'json');
	}

	public function deserialize(string $data): Entities\IMessage
	{
		$class = $this->class ?? Entities\Message::class;

		return $this->serializer->deserialize($data, $class, 'json');
	}

}
