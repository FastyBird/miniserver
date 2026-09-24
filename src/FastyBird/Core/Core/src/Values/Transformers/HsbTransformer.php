<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Transformers;

use Override;
use function abs;
use function floor;
use function intval;
use function min;
use function pow;
use function round;

/**
 * HSB value object
 */
final readonly class HsbTransformer implements Transformer
{

	private const int RGB_THRESHOLD = 10; // Define a threshold for how close RGB values should be when calculate white level

	public function __construct(
		private float $hue,
		private float $saturation,
		private float $brightness,
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

	public function getBrightness(): float
	{
		return $this->brightness;
	}

	public function toRgb(): RgbTransformer
	{
		$hue = $this->hue;
		$saturation = $this->saturation;
		$brightness = $this->brightness;

		if ($hue < 0) {
			$hue = 0;
		}

		if ($hue > 360) {
			$hue = 360;
		}

		if ($saturation < 0) {
			$saturation = 0;
		}

		if ($saturation > 100) {
			$saturation = 100;
		}

		if ($brightness < 0) {
			$brightness = 0;
		}

		if ($brightness > 100) {
			$brightness = 100;
		}

		$dS = $saturation / 100.0;
		$dV = $brightness / 100.0;
		$dC = $dV * $dS;
		$dH = $hue / 60.0;
		$dT = $dH;

		while ($dT >= 2.0) {
			$dT -= 2.0;
		}

		$dX = $dC * (1 - abs($dT - 1));

		switch (intval(floor($dH))) {
			case 0:
				$dR = $dC;
				$dG = $dX;
				$dB = 0.0;

				break;
			case 1:
				$dR = $dX;
				$dG = $dC;
				$dB = 0.0;

				break;
			case 2:
				$dR = 0.0;
				$dG = $dC;
				$dB = $dX;

				break;
			case 3:
				$dR = 0.0;
				$dG = $dX;
				$dB = $dC;

				break;
			case 4:
				$dR = $dX;
				$dG = 0.0;
				$dB = $dC;

				break;
			case 5:
				$dR = $dC;
				$dG = 0.0;
				$dB = $dX;

				break;
			default:
				$dR = 0.0;
				$dG = 0.0;
				$dB = 0.0;

				break;
		}

		$dM = $dV - $dC;
		$dR += $dM;
		$dG += $dM;
		$dB += $dM;
		$dR *= 255;
		$dG *= 255;
		$dB *= 255;
		$dR = intval(round($dR));
		$dG = intval(round($dG));
		$dB = intval(round($dB));

		return new RgbTransformer($dR, $dG, $dB);
	}

	public function toRgbw(int $brightness): RgbTransformer
	{
		$rgb = $this->toRgb();

		// Calculate the white component (W) based on the correct brightness
		// The white value is the minimum of R, G, and B, adjusted by the actual brightness
		$whiteLevel = $brightness / 100.0; // Brightness factor

		// Check if RGB values are close enough to each other to justify a white component
		$dW = (
			abs($rgb->getRed() - $rgb->getGreen()) <= self::RGB_THRESHOLD
			&& abs($rgb->getGreen() - $rgb->getBlue()) <= self::RGB_THRESHOLD
		)
			? intval(round(min($rgb->getRed(), $rgb->getGreen(), $rgb->getBlue()) * $whiteLevel))
			: 0; // No white component for strongly colored light

		return new RgbTransformer($rgb->getRed(), $rgb->getGreen(), $rgb->getBlue(), $dW);
	}

	public function toMired(): MiredTransformer
	{
		$rgb = $this->toRgb();

		// This is a basic example and may not provide accurate results for all scenarios
		$temperature = 0;

		// Calculate color temperature based on RGB values
		$X = (-0.142_82 * $rgb->getRed()) + (1.549_24 * $rgb->getGreen()) + (-0.956_41 * $rgb->getBlue());
		$Y = (-0.324_66 * $rgb->getRed()) + (1.578_37 * $rgb->getGreen()) + (-0.731_91 * $rgb->getBlue());
		$Z = (-0.682_02 * $rgb->getRed()) + (0.770_73 * $rgb->getGreen()) + (0.563_32 * $rgb->getBlue());

		// Calculate xy values
		$x = $X / ($X + $Y + $Z);
		$y = $Y / ($X + $Y + $Z);

		// Calculate correlated color temperature (CCT)
		$n = ($x - 0.332_0) / (0.185_8 - $y);

		$temperature = 449.0 * pow($n, 3) + 3_525.0 * pow($n, 2) + 6_823.3 * $n + 5_520.33;

		return new MiredTransformer(intval(round(1_000_000 / $temperature)), $this->getBrightness());
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'hue' => $this->getHue(),
			'saturation' => $this->getSaturation(),
			'brightness' => $this->getBrightness(),
		];
	}

}
