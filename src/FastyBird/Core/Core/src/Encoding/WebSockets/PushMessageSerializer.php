<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\WebSockets;

use FastyBird\Core\Entities\WebSockets\PushMessages as Entities;
use Symfony\Component\Serializer;

/**
 * Push message data serializer
 */
final class PushMessageSerializer
{

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
