<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding;

/**
 * Communication message interface
 */
interface IMessage extends FrameData
{

	public function addFrame(IFrame $fragment): void;

	public function getOpCode(): int;

}
