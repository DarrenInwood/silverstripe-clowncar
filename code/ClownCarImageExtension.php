<?php

/*
"Clown Car" adaptive images
http://coding.smashingmagazine.com/2013/06/02/clown-car-technique-solving-for-adaptive-images-in-responsive-web-design/

Usage in templates:
$Image.ClownCar(100,200)

This will output a Clown Car built up using media queries.
*/

class ClownCarImageExtension extends DataExtension {

	/**
	 * Adds a template function to regular Image Instances enabling
	 * outputting images as a clown car.
	 * Usage:
	 * $Image.ClownCar
	 * $Image.ClownCar("Set name")
	 */
	public function ClownCar($set=null) {
		$image = $this->owner->newClassInstance('ClownCarImage');
		$image->setBreakpointSet($set);
		return $image;
	}

	public function ClownCarDebugInfo($string) {
		return $this->owner->getFormattedImage('ClownCarDebugInfo', $string);
	}

	public function generateClownCarDebugInfo($gd, $string) {
		$newGD = imagecreatetruecolor($gd->getWidth(), $gd->getHeight());
		imagealphablending($newGD, false);
		imagesavealpha($newGD, true);

		imagecopyresampled($newGD, $gd->getGD(), 0, 0, 0, 0, $gd->getWidth(), $gd->getHeight(), $gd->getWidth(), $gd->getHeight());

		$black = GD::color_web2gd($newGD, '#000000');
		$white = GD::color_web2gd($newGD, '#FFFFFF');
		imagestring($newGD, 1, 1, 1, $string, $white);
		imagestring($newGD, 1, 1, 3, $string, $white);
		imagestring($newGD, 1, 3, 1, $string, $white);
		imagestring($newGD, 1, 3, 3, $string, $white);
		imagestring($newGD, 1, 2, 2, $string, $black);

		// Create a new GD
		$output = clone $gd;
		$output->setGD($newGD);

		return $output;
	}

}