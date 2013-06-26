<?php

class ClownCarImage extends Image {

	protected $BreakpointSet = null;

	public static $BreakpointSets;

	public static $DefaultBreakpointSet = 'Default';

	public static $DefaultBreakpoint = 'Desktop';

	/**
	 * Enables Clown Car adaptive images.
	 * @param $fixtureFile (String) Filename for YAML fixture file to create breakpoints
	 */
	public static function enable() {
		// Add ClownCar template function to Image objects
		Object::add_extension('Image', 'ClownCarImageExtension');
	}

	public function setBreakpointSet($set=null) {
		$config = $this->config()->get('BreakpointSets');
		if ( !is_array($config) || count($config) === 0 ) {
			user_error(
				'Clown car image breakpoints are not set correctly. ' .
				'Check your YAML config files, or do a /dev/build.'
			);
		}
		// If we didn't specify anything, use the default.
		if ( $set === null ) {
			$set = self::$DefaultBreakpointSet;
		}
		// Use the default, falling back to the first set configured, if we haven't
		// got a valid breakpoint set specified
		if ( !array_key_exists($set, $config) ) {
			if ( array_key_exists(self::$DefaultBreakpointSet, $config) ) {
				$set = self::$DefaultBreakpointSet;
			} else {
				$set = array_pop(array_keys($config));
			}
		}
		$this->BreakpointSet = $set;
	}

	/**
	 * Returns the media query breakpoints for this image.
	 * Uses the default site breakpoints, or if there are custom breakpoints set up,
	 * uses those instead.
	 * As it's returning a special internal-use-only structure, it's protected.
	 * Uses the 'focuspoint' module if installed:
	 * https://github.com/jonom/silverstripe-focuspoint
	 */
	protected function getBreakpointData() {
		$config = $this->config()->get('BreakpointSets');
		$breakpoints = $config[$this->BreakpointSet];
		$out = new ArrayList(array());
		foreach( $breakpoints as $breakpoint ) {
			$data = array();
			$data['MediaQuery'] = $breakpoint['MediaQuery'];
			// Decide how to crop/resize the image, retaining the aspect ratio.
			$currentWidth = $this->Width;
			$currentHeight = $this->Height;
			$aspectRatio = $currentWidth / $currentHeight;
			if ( isset($breakpoint['Width']) && (int)$breakpoint['Width'] !== 0 ) {
				// If we have a valid width specified, use it
				$targetWidth = $breakpoint['Width'];
			} else if ( isset($breakpoint['Height']) && (int)$breakpoint['Height'] !== 0 ) {
				// If we have no valid width specified, but we have a valid height specified,
				// calculate the width from the height using the original image's aspect ratio
				$targetWidth = round($breakpoint['Height'] * $aspectRatio);
			} else {
				// If we have neither a width or height specified, use the original image width
				$targetWidth = $currentWidth;
			}
			if ( isset($breakpoint['Height']) && (int)$breakpoint['Height'] !== 0 ) {
				// If we have a valid height specified, use it
				$targetHeight = $breakpoint['Height'];
			} else if ( isset($breakpoint['Width']) && (int)$breakpoint['Width'] !== 0 ) {
				// If we have no valid height specified, but we have a valid width specified,
				// calculate the height from the width using the original image's aspect ratio
				$targetHeight = round($breakpoint['Width'] / $aspectRatio);
			} else {
				// If we have neither a width or height specified, use the original image height
				$targetHeight = $currentHeight;
			}

			// If the target width and height are the original image's, or the target image is
			// larger than the original image and the aspect ratio is the same, use the original image.
			$data['Image'] = false;
			if ( ($targetWidth == $this->Width && $targetHeight == $this->Height) ||
				($targetWidth > $this->Width && round($targetHeight * $aspectRatio) == $targetWidth) ) {
				$data['Image'] = $this;
			} else {
				// If we have the FocusPoint extension installed, use it
				if ( $this->hasExtension('FocusPointImage') ) {
					$data['Image'] = $this->CroppedFocusedImage( $targetWidth, $targetHeight );
				} else {
					$data['Image'] = $this->CroppedImage( $targetWidth, $targetHeight );
				}
			}
			// Use a double-scale version for retina images,
			// if we're auto-generating these rules.
			$data['RetinaImage'] = false;
			if ( $breakpoint['AddRetinaRules'] ) {
				$retinaWidth = $targetWidth * 2;
				$retinaHeight = $targetHeight * 2;
				// If we have the FocusPoint extension installed, use it
				if ( ($retinaWidth == $this->Width && $retinaHeight == $this->Height) ||
					($retinaWidth > $this->Width && round($retinaHeight * $aspectRatio) == $retinaWidth) ) {
					$data['RetinaImage'] = $this;
				} else {
					// If we have the FocusPoint extension installed, use it
					if ( $this->hasExtension('FocusPointImage') ) {
						$data['RetinaImage'] = $this->CroppedFocusedImage( $retinaWidth, $retinaHeight );
					} else {
						$data['RetinaImage'] = $this->CroppedImage( $retinaWidth, $retinaHeight );
					}
				}
			}
			$out->push(new ArrayData($data));
		}
		return $out;
	}

	/**
	 * Outputs the tag used for a Clown Car image.
	 */
	public function getTag() {
		$config = $this->config()->get('BreakpointSets');
		$breakpoints = $config[$this->BreakpointSet];

		// Create the SVG using the ClownCarSvg.ss template
		$svg = $this->renderWith('ClownCarSvg');

		// Compress and encode the SVG for embedding as data URI
		$svg = str_replace('"', "'", $svg);
		$svg = str_replace("\n", '', $svg);
		$svg = str_replace("\t", '', $svg);
		$svg = rawurlencode($svg);

		// Find the default width to display for early IE, old Android, etc
		// If we have a 'desktop' default image size set, use that
		if ( array_key_exists(self::$DefaultBreakpoint, $breakpoints) ) {
			$defaultWidth = $breakpoints[self::$DefaultBreakpoint]['Width'];
		} else {
			$defaultWidth = -1;
			// If we don't have the default label in this set, fall back to the largest
			foreach( $breakpoints as $breakpoint ) {
				if ( (int)$breakpoint['Width'] > $defaultWidth ) {
					$defaultWidth = $breakpoint['Width'];
				}
			}
		}

		// Encode SVG into an Object tag using the ClownCarObject.ss template
		$object = $this->customise(array(
			'SvgEncoded' => $svg,
			'DefaultImage' => $defaultWidth >= $this->Width ?
				$this :
				$this->SetWidth($defaultWidth),
		))->renderWith('ClownCarObject');

		return $object;
	}

}