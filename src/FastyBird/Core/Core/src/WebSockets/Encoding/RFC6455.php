<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Override;
use TypeError;
use UnderflowException;
use function array_key_exists;
use function array_merge;
use function base64_encode;
use function count;
use function pack;
use function sha1;
use function strlen;
use function substr;
use function unpack;

/**
 * The latest version of the WebSocket protocol
 *
 * @link           http://tools.ietf.org/html/rfc6455
 *
 * @todo           Unicode: return mb_convert_encoding(pack("N",$u), mb_internal_encoding(), 'UCS-4BE');
 */
class RFC6455
{

	/**
	 * Handshake hash
	 */
	public const string GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	private RFC6455\HandshakeVerifier $verifier;

	/**
	 * A lookup of the valid close codes that can be sent in a frame
	 */
	private array $closeCodes = [];

	private Validator $validator;

	public function __construct()
	{
		$this->verifier = new RFC6455\HandshakeVerifier($this->getVersion());

		$this->setCloseCodes();

		$this->validator = new Validator();
	}

	/**
	 * Although the version has a name associated with it the integer returned is the proper identification
	 */
	public function getVersion(): int
	{
		return 13;
	}

	/**
	 * Given an HTTP header, determine if this version should handle the protocol
	 */
	public function isVersion(Handshake\Request $httpRequest): bool
	{
		$version = (int) (string) $httpRequest->getHeader('Sec-WebSocket-Version');

		return $this->getVersion() === $version;
	}

	/**
	 * Perform the handshake and return the response headers
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function doHandshake(Handshake\Request $httpRequest): Handshake\WampResponse
	{
		if ($this->verifier->verifyAll($httpRequest) !== true) {
			return new Handshake\WampResponse(Handshake\WampResponse::S400_BAD_REQUEST);
		}

		return new Handshake\WampResponse(Handshake\WampResponse::S101_SWITCHING_PROTOCOLS, [
			'Upgrade' => 'websocket',
			'Connection' => 'Upgrade',
			'Sec-WebSocket-Accept' => $this->sign((string) $httpRequest->getHeader('Sec-WebSocket-Key')),
		]);
	}

	/**
	 * @throws UnderflowException
	 */
	public function handleMessage(
		Entities\ConnectedClient $client,
		Controllers\Dispatcher $application,
		string $data = '',
	): void
	{
		$overflow = '';

		$webSocket = $client->getWebSocket();

		if (!$webSocket->hasMessage()) {
			$webSocket->setMessage($this->newMessage());
		}

		// There is a frame fragment attached to the connection, add to it
		if (!$webSocket->hasFrame()) {
			$webSocket->setFrame($this->newFrame());
		}

		$webSocket->getFrame()->addBuffer($data);

		if ($webSocket->getFrame()->isCoalesced()) {
			$frame = $webSocket->getFrame();

			if ($frame->getRsv1() !== false ||
				$frame->getRsv2() !== false ||
				$frame->getRsv3() !== false
			) {
				$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

				return;
			}

			if (!$frame->isMasked()) {
				$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

				return;
			}

			$opCode = $frame->getOpCode();

			if ($opCode > 2) {
				if ($frame->getPayloadLength() > 125 || !$frame->isFinal()) {
					$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

					return;
				}

				switch ($opCode) {
					case RFC6455\Frame::OP_CLOSE:
						$closeCode = 0;

						$bin = $frame->getPayload();

						if ($bin === []) {
							$this->close($client);

							return;
						}

						if (strlen($bin) >= 2) {
							[$closeCode] = array_merge(unpack('n*', substr($bin, 0, 2)));
						}

						if (!$this->isValidCloseCode($closeCode)) {
							$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

							return;
						}

						if (!$this->validator->checkEncoding(substr($bin, 2), 'UTF-8')) {
							$this->close($client, RFC6455\Frame::CLOSE_BAD_PAYLOAD);

							return;
						}

						$frame->unMaskPayload();

						$this->send($client, $frame);

						return;
					case RFC6455\Frame::OP_PING:
						$client->send($this->newFrame($frame->getPayload(), true, RFC6455\Frame::OP_PONG));

						break;
					case RFC6455\Frame::OP_PONG:
						break;
					default:
						$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

						return;
				}

				$overflow = $webSocket->getFrame()->extractOverflow();

				$webSocket->destroyFrame();

				unset($frame, $opCode);

				if (strlen($overflow) > 0) {
					$this->handleMessage($client, $application, $overflow);
				}

				return;
			}

			$overflow = $webSocket->getFrame()->extractOverflow();

			$message = $webSocket->getMessage();

			if ($frame->getOpCode() === RFC6455\Frame::OP_CONTINUE && count($message) === 0) {
				$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

				return;
			}

			if (count($message) > 0 && $frame->getOpCode() !== RFC6455\Frame::OP_CONTINUE) {
				$this->close($client, RFC6455\Frame::CLOSE_PROTOCOL);

				return;
			}

			$webSocket->getMessage()->addFrame($webSocket->getFrame());
			$webSocket->destroyFrame();
		}

		if ($webSocket->getMessage()->isCoalesced()) {
			$parsed = $webSocket->getMessage()->getPayload();

			$webSocket->destroyMessage();

			if (!$this->validator->checkEncoding($parsed, 'UTF-8')) {
				$this->close($client, RFC6455\Frame::CLOSE_BAD_PAYLOAD);

				return;
			}

			$application->handleMessage($client, $client->getRequest(), $parsed);
		}

		if (strlen($overflow) > 0) {
			$this->handleMessage($client, $application, $overflow);
		}
	}

	public function send(Entities\ConnectedClient $client, $payload): void
	{
		if (!$client->getWebSocket()->closing) {
			if (!$payload instanceof FrameData) {
				$payload = new RFC6455\Frame($payload);
			}

			$client->getConnection()->write($payload->getContents());
		}
	}

	public function close(Entities\ConnectedClient $client, int|null $code = null): void
	{
		if ($client->getWebSocket()->closing) {
			return;
		}

		$code ??= 1_000;

		if ($code instanceof FrameData) {
			$this->send($client, $code);

		} else {
			$this->send($client, new RFC6455\Frame(pack('n', $code), true, RFC6455\Frame::OP_CLOSE));
		}

		$client->getConnection()->end();

		$client->getWebSocket()->closing = true;
	}

	/**
	 * Used when doing the handshake to encode the key, verifying client/server are speaking the same language
	 */
	private function sign(string $key): string
	{
		return base64_encode(sha1($key . self::GUID, true));
	}

	private function newMessage(): RFC6455\Message
	{
		return new RFC6455\Message();
	}

	/**
	 * @param bool|null $final
	 * @param int|null $opCode
	 */
	private function newFrame(
		string|null $payload = null,
		bool $final = true,
		int $opCode = RFC6455\Frame::OP_TEXT,
	): RFC6455\Frame
	{
		return new RFC6455\Frame($payload, $final, $opCode);
	}

	/**
	 * Determine if a close code is valid
	 */
	private function isValidCloseCode(int|string $val): bool
	{
		if (array_key_exists($val, $this->closeCodes)) {
			return true;
		}

		return $val >= 3_000 && $val <= 4_999;
	}

	/**
	 * Creates a private lookup of valid, private close codes
	 */
	private function setCloseCodes(): void
	{
		$this->closeCodes[RFC6455\Frame::CLOSE_NORMAL] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_GOING_AWAY] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_PROTOCOL] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_BAD_DATA] = true;
		//$this->closeCodes[RFC6455\Frame::CLOSE_NO_STATUS] = true;
		//$this->closeCodes[RFC6455\Frame::CLOSE_ABNORMAL] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_BAD_PAYLOAD] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_POLICY] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_TOO_BIG] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_MAND_EXT] = true;
		$this->closeCodes[RFC6455\Frame::CLOSE_SRV_ERR] = true;
		//$this->closeCodes[RFC6455\Frame::CLOSE_TLS] = true;
	}

	#[Override]
	public function __toString(): string
	{
		return (string) $this->getVersion();
	}

}
