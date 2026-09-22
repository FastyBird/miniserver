<?php declare(strict_types = 1);

namespace FastyBird\Core\Transformers\Tools;

use function cos;
use function floatval;
use function fmod;
use function intval;

/**
 * HSI value object
 */
final readonly class HsiTransformer implements Transformer
{

	public function __construct(
		private float $hue,
		private float $saturation,
		private float $intensity,
	)
	{
	}

	public function getHue(): float
	{
		return $this->hue;
	}

	public function getSaturation(): float
	{
		return $this->saturation;
	}

	public function getIntensity(): float
	{
		return $this->intensity;
	}

	public function toRgb(): RgbTransformer
	{
		$hue = fmod($this->getHue(), 360.0);
		$hue = 3.141_59 * $hue / 180.0;
		$saturation = $this->constrain($this->getSaturation(), 0.0, 1.0);
		$intensity = $this->constrain($this->getIntensity(), 0.0, 1.0);

		if ($hue < 2.094_39) {
			$red = 255.0 * $intensity / 3.0 * (1.0 + $saturation * cos($hue) / cos(1.047_196_667 - $hue));
			$green = 255.0 * $intensity / 3.0 * (1.0 + $saturation * (1.0 - cos($hue) / cos(1.047_196_667 - $hue)));
			$blue = 255.0 * $intensity / 3.0 * (1.0 - $saturation);

		} elseif ($hue < 4.188_787) {
			$hue -= 2.094_39;

			$red = 255.0 * $intensity / 3.0 * (1.0 - $saturation);
			$green = 255.0 * $intensity / 3.0 * (1.0 + $saturation * cos($hue) / cos(1.047_196_667 - $hue));
			$blue = 255.0 * $intensity / 3.0 * (1.0 + $saturation * (1.0 - cos($hue) / cos(1.047_196_667 - $hue)));

		} else {
			$hue -= 4.188_787;

			$red = 255.0 * $intensity / 3.0 * (1.0 + $saturation * (1.0 - cos($hue) / cos(1.047_196_667 - $hue)));
			$green = 255.0 * $intensity / 3.0 * (1.0 - $saturation);
			$blue = 255.0 * $intensity / 3.0 * (1.0 + $saturation * cos($hue) / cos(1.047_196_667 - $hue));
		}

		return new RgbTransformer(
			intval($this->constrain(intval($red * 3.0), 0, 255)),
			intval($this->constrain(intval($green * 3.0), 0, 255)),
			intval($this->constrain(intval($blue * 3.0), 0, 255)),
		); // for some reason, the rgb numbers need to be X3...
	}

	public function toRgbw(): RgbTransformer
	{
		$hue = floatval(fmod($this->getHue(), 360)); // Cycle H around to 0-360 degrees
		$hue = 3.141_59 * $hue / 180.0; // Convert to radians
		$saturation = $this->constrain($this->getSaturation(), 0.0, 1.0);
		$intensity = $this->constrain($this->getIntensity(), 0.0, 1.0);

		if ($hue < 2.094_39) {
			$cosH = cos($hue);
			$cos1047H = cos(1.047_196_667 - $hue);

			$red = $saturation * 255.0 * $intensity / 3.0 * (1.0 + $cosH / $cos1047H);
			$green = $saturation * 255.0 * $intensity / 3.0 * (1.0 + (1.0 - $cosH / $cos1047H));
			$blue = 0.0;
			$white = 255.0 * (1.0 - $saturation) * $intensity;

		} elseif ($hue < 4.188_787) {
			$hue -= 2.094_39;
			$cosH = cos($hue);
			$cos1047H = cos(1.047_196_667 - $hue);

			$red = 0.0;
			$green = $saturation * 255.0 * $intensity / 3.0 * (1.0 + $cosH / $cos1047H);
			$blue = $saturation * 255.0 * $intensity / 3.0 * (1.0 + (1.0 - $cosH / $cos1047H));
			$white = 255.0 * (1.0 - $saturation) * $intensity;

		} else {
			$hue -= 4.188_787;
			$cosH = cos($hue);
			$cos1047H = cos(1.047_196_667 - $hue);

			$red = $saturation * 255.0 * $intensity / 3.0 * (1.0 + (1.0 - $cosH / $cos1047H));
			$green = 0.0;
			$blue = $saturation * 255.0 * $intensity / 3.0 * (1.0 + $cosH / $cos1047H);
			$white = 255.0 * (1.0 - $saturation) * $intensity;
		}

		return new RgbTransformer(
			intval($this->constrain($red * 3, 0, 255)),
			intval($this->constrain($green * 3, 0, 255)),
			intval($this->constrain($blue * 3, 0, 255)),
			intval($this->constrain($white, 0, 255)),
		); // For some reason, the rgb numbers need to be X3...
	}

	public function toArray(): array
	{
		return [
			'hue' => $this->getHue(),
			'saturation' => $this->getSaturation(),
			'intensity' => $this->getIntensity(),
		];
	}

	private function constrain(float $val, float $min, float $max): float
	{
		if ($val <= $min) {
			return $min;
		}

		if ($val >= $max) {
			return $max;
		}

		return $val;
	}

}
