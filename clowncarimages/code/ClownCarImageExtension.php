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
	 * $Image.ClownCar("Set title")
	 */
	public function ClownCar($set=null) {
		$image = $this->owner->newClassInstance('ClownCarImage');
		$image->setBreakpointSet($set);
		return $image;
	}

}