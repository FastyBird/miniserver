<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as SlimRouterExceptions;
use Override;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_resource_type;
use function preg_match;
use function stream_get_contents;
use function stream_get_meta_data;
use function strstr;
use const SEEK_SET;

/**
 * Basic http response resource
 */
final class Stream implements StreamInterface
{

	/** @var array<mixed>|null */
	private array|null $metaData = null;

	private bool|null $readable = null;

	private bool|null $writable = null;

	private bool|null $seekable = null;

	/**
	 * @param resource $resource One of the stream type resources
	 *
	 * @see https://www.php.net/manual/en/resource.php
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private $resource = null)
	{
		if (get_resource_type($resource) !== 'stream') {
			throw new Exceptions\InvalidArgument('Invalid stream resource');
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public static function fromResourceUri(string $streamUri, string $mode = 'r'): self
	{
		$resource = fopen($streamUri, $mode);

		if ($resource === false) {
			throw preg_match('/^[acrwx](?:\+?[tb]?|[tb]?\+?)$/', $mode) === false
				? new Exceptions\InvalidArgument('Invalid stream resource mode')
				: new Exceptions\Runtime('Invalid stream reference');
		}

		return new self($resource);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws RuntimeException
	 */
	public static function fromBodyString(string $body): self
	{
		$resource = fopen('php://temp', 'w+b');

		if ($resource === false) {
			throw new Exceptions\Runtime('Resource could not be created');
		}

		$stream = new self($resource);
		$stream->write($body);
		$stream->rewind();

		return $stream;
	}

	#[Override]
	public function close(): void
	{
		$resource = $this->detach();

		if ($resource !== null) {
			fclose($resource);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function detach()
	{
		if ($this->resource === null) {
			return null;
		}

		$resource = $this->resource;

		$this->resource = null;
		$this->metaData = null;
		$this->readable = false;
		$this->seekable = false;
		$this->writable = false;

		return $resource;
	}

	#[Override]
	public function getSize(): int|null
	{
		if ($this->resource === null) {
			return null;
		}

		$fileInfo = fstat($this->resource);

		return $fileInfo !== false ? $fileInfo['size'] : null;
	}

	#[Override]
	public function tell(): int
	{
		if ($this->resource === null) {
			throw new Exceptions\Runtime('Pointer position not available in detached resource');
		}

		$position = ftell($this->resource);

		if ($position === false) {
			throw new SlimRouterExceptions\StreamResourceCall('Failed to tell pointer position');
		}

		return $position;
	}

	#[Override]
	public function eof(): bool
	{
		return $this->resource !== null ? feof($this->resource) : true;
	}

	#[Override]
	public function isSeekable(): bool
	{
		if ($this->seekable !== null) {
			return $this->seekable;
		}

		return $this->seekable = (bool) $this->getMetadata('seekable');
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function seek($offset, $whence = SEEK_SET): void
	{
		if ($this->resource === null) {
			throw new Exceptions\Runtime('No resource available; cannot read');
		}

		if (!$this->isSeekable()) {
			throw new Exceptions\Runtime('Stream is not seekable or detached');
		}

		$exitCode = fseek($this->resource, $offset, $whence);

		if ($exitCode === -1) {
			throw new SlimRouterExceptions\StreamResourceCall('Failed to seek the stream');
		}
	}

	#[Override]
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function isWritable()
	{
		if ($this->writable !== null) {
			return $this->writable;
		}

		$mode = $this->getMetadata('mode');
		$writable = ['w' => true, 'a' => true, 'x' => true, 'c' => true];

		return $this->writable = (isset($writable[$mode[0]]) || strstr($mode, '+') !== false);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function write($string)
	{
		if ($this->resource === null) {
			throw new Exceptions\Runtime('No resource available; cannot write');
		}

		if (!$this->isWritable()) {
			throw new Exceptions\Runtime('Stream is not writable');
		}

		$bytesWritten = fwrite($this->resource, $string);

		if ($bytesWritten === false) {
			throw new SlimRouterExceptions\StreamResourceCall('Failed writing to stream');
		}

		return $bytesWritten;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function isReadable()
	{
		if ($this->readable !== null) {
			return $this->readable;
		}

		$mode = $this->getMetadata('mode');

		return $this->readable = ($mode[0] === 'r' || strstr($mode, '+') !== false);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function read($length)
	{
		if ($this->resource === null) {
			throw new Exceptions\Runtime('No resource available; cannot read');
		}

		if (!$this->isReadable()) {
			throw new Exceptions\Runtime('Stream is not readable');
		}

		$streamData = fread($this->resource, $length);

		if ($streamData === false) {
			throw new SlimRouterExceptions\StreamResourceCall('Failed reading from stream');
		}

		return $streamData;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getContents()
	{
		if ($this->resource === null) {
			throw new Exceptions\Runtime('No resource available; cannot read');
		}

		if (!$this->isReadable()) {
			throw new Exceptions\Runtime('Stream is not readable or detached');
		}

		$streamContents = stream_get_contents($this->resource);

		if ($streamContents === false) {
			throw new SlimRouterExceptions\StreamResourceCall('Failed to retrieve stream contents');
		}

		return $streamContents;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getMetadata($key = null)
	{
		if ($this->resource === null) {
			return $key !== null ? null : [];
		}

		$this->metaData ??= stream_get_meta_data($this->resource);

		return $key !== null ? $this->metaData[$key] ?? null : $this->metaData;
	}

	/**
	 * @throws RuntimeException
	 */
	#[Override]
	public function __toString(): string
	{
		try {
			$this->rewind();

			return $this->getContents();
		} catch (Exceptions\Runtime) {
			return '';
		}
	}

}
