<?php declare(strict_types = 1);

namespace FastyBird\Core\Transformers\Tools;

use Override;
use function abs;
use function floatval;
use function max;
use function min;
use function round;

/**
 * RGB value object
 */
final readonly class RgbTransformer implements Transformer
{

	public function __construct(
		private int $red,
		private int $green,
		private int $blue,
		private int|null $white = null,
	)
	{
	}

	public function getRed(): int
	{
		return $this->red;
	}

	public function getGreen(): int
	{
		return $this->green;
	}

	public function getBlue(): int
	{
		return $this->blue;
	}

	public function getWhite(): int|null
	{
		return $this->white;
	}

	public function toHsb(): HsbTransformer
	{
		$red = floatval($this->red / 255);
		$green = floatval($this->green / 255);
		$blue = floatval($this->blue / 255);

		$min = floatval(min($red, $green, $blue));
		$max = floatval(max($red, $green, $blue));
		$chroma = $max - $min;

		$brightness = 100 * $max;

		if ($chroma === 0.0 || $max === 0.0) {
			return new HsbTransformer(0, 0, $brightness);
		}

		$saturation = 100 * $chroma / $max;

		$hue = match ($min) {
			$red => 3 - (($green - $blue) / $chroma),
			$blue => 1 - (($red - $green) / $chroma),
			default => 5 - (($blue - $red) / $chroma),
		};

		$hue = 60 * $hue;

		return new HsbTransformer(round($hue), round($saturation, 2), $brightness);
	}

	public function toHsi(): HsiTransformer
	{
		$red = $this->constrain(floatval($this->getRed()) / 255.0);
		$green = $this->constrain(floatval($this->getGreen()) / 255.0);
		$blue = $this->constrain(floatval($this->getBlue()) / 255.0);
		$intensity = 0.333_33 * ($red + $green + $blue);

		$max = max($red, $green, $blue);
		$min = min($red, $green, $blue);

		$saturation = $intensity === 0.0 ? 0.0 : 1.0 - ($min / $intensity);

		$hue = 0;

		if ($max === $red) {
			$hue = $max === $min ? 0.0 : 60.0 * (0.0 + (($green - $blue) / ($max - $min)));
		}

		if ($max === $green) {
			$hue = $max === $min ? 0.0 : 60.0 * (2.0 + (($blue - $red) / ($max - $min)));
		}

		if ($max === $blue) {
			$hue = $max === $min ? 0.0 : 60.0 * (4.0 + (($red - $green) / ($max - $min)));
		}

		if ($hue < 0) {
			$hue += 360;
		}

		return new HsiTransformer($hue, abs($saturation), $intensity);
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
			'white' => $this->getWhite(),
		];
	}

	private function constrain(float $val): float
	{
		if ($val <= 0.0) {
			return 0.0;
		}

		if ($val >= 1.0) {
			return 1.0;
		}

		return $val;
	}

}
