<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding;

use FastyBird\Core\WebSockets\Entities\PushMessages;
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

	public function serialize(PushMessages\IMessage $message): string
	{
		$this->class = $message::class;

		return $this->serializer->serialize($message, 'json');
	}

	public function deserialize(string $data): PushMessages\IMessage
	{
		$class = $this->class ?? PushMessages\Message::class;

		return $this->serializer->deserialize($data, $class, 'json');
	}

}
