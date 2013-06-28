<?php

class ClownCarImage extends Image {

	/**
	 * The configured breakpointsets live here.  They are populated
	 * by the YAML config system - see _config/clowncarbreakpoints.yml
	 */
	public static $BreakpointSets;

	/**
	 * If no Breakpoint Set is specified in the template (or a set
	 * is specified that doesn't have any configuration) this is
	 * the Breakpoint Set that will be used.  It is provided by
	 * the module in _config/clowncarbreakpoints.yml.
	 */
	public static $DefaultBreakpointSet = 'Default';

	/**
	 * Internet Explorer 8 and below do not support Clown Car adaptive
	 * images.  This is the name of the breakpoint that will be used as
	 * the fallback image for these browsers.
	 * If this breakpoint is not defined for an image's Breakpoint Set,
	 * it will fall back to the original image size.
	 */
	public static $DefaultBreakpoint = 'Desktop';

	/**
	 * If a 'AddRetinaRules: 1' line is added to a breakpoint via
	 * the YAML config, additional rules will automatically be added
	 * to serve upscaled images to devices with a screen pixel dnesity
	 * above 1.3 - this setting defines the amount the image will be
	 * scaled up by.
	 * Images are not scaled up.  If the retina version of an image
	 * is larger than the base image being scaled, the original image
	 * will be used on Retina devices.  For this reason it is a good
	 * idea to call CLownCar on full-sized images in your templates.
	 */
	public static $AddRetinaRulesDensity = 2;

	/**
	 * The Breakpoint Set this image instance is using - set by
	 * the template.  Falls back to self::$DefaultBreakpointSet
	 * if nothing is specified, or if a breakpoint set is specified
	 * that is not configured.
	 */
	protected $BreakpointSet = null;

	/**
	 * Shows the breakpoint name, image dimensions, and if AddRetinaRule
	 * is used in the config will show @1x or @2x to indicate whether the visible
	 * image is the retina version.
	 * Useful for testing your breakpoints.
	 */
	public static $ShowDebug = false;

	/**
	 * If set to false, will use regular IMG tags instead of the clown car.
	 */
	public static $Enabled = true;

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
	 * Outputs the tag used for a Clown Car image.
	 */
	public function getTag() {
		if ( !self::$Enabled ) {
			return parent::getTag();
		}

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

	/**
	 * Returns the media query breakpoints for this image.
	 * Uses the default site breakpoints, or if there are custom breakpoints set up,
	 * uses those instead.
	 * As it's returning a special internal-use-only structure, it's protected.
	 * Uses the 'focuspoint' module if installed:
	 * https://github.com/jonom/silverstripe-focuspoint
	 * This will create all the thumbnail sizes for the given image, so can be
	 * very processor-intensive.
	 */
	protected function getBreakpointData() {
		$config = $this->config()->get('BreakpointSets');
		$breakpoints = $config[$this->BreakpointSet];
		$out = new ArrayList(array());
		foreach( $breakpoints as $label => $breakpoint ) {
			$data = array();
			$data['MediaQuery'] = $breakpoint['MediaQuery'];

			// Create resized image for this breakpoint
			$data['Image'] = $this->getImageForBreakpoint($breakpoint, 1);

			// Add debug info if required
			if ( self::$ShowDebug ) {
				$data['Image'] = $data['Image']->ClownCarDebugInfo(sprintf(
					'%s:%sx%s%s',
					$label,
					$data['Image']->Width,
					$data['Image']->Height,
					isset($breakpoint['AddRetinaRules']) && $breakpoint['AddRetinaRules'] ? '@1x' : ''
				));
			}

			// Use a double-scale version for retina images,
			// if we're auto-generating these rules.
			$data['RetinaImage'] = false;
			if ( isset($breakpoint['AddRetinaRules']) && $breakpoint['AddRetinaRules'] ) {
				$data['RetinaImage'] = $this->getImageForBreakpoint(
					$breakpoint,
					self::$AddRetinaRulesDensity
				);

				// Add debug info if required
				if ( self::$ShowDebug ) {
					$data['RetinaImage'] = $data['RetinaImage']->ClownCarDebugInfo(sprintf(
						'%s:%sx%s%s',
						$label,
						$data['RetinaImage']->Width,
						$data['RetinaImage']->Height,
						isset($breakpoint['AddRetinaRules']) && $breakpoint['AddRetinaRules'] ?
							'@'.self::$AddRetinaRulesDensity.'x' : ''
					));
				}
			}
			$out->push(new ArrayData($data));
		}
		return $out;
	}

	// Returns an image created from the current image, for a specified breakpoint data array
	// and pixel density
	protected function getImageForBreakpoint($breakpoint, $pixelDensity=1) {
		$currentWidth = $this->Width;
		$currentHeight = $this->Height;
		$aspectRatio = $currentWidth / $currentHeight;
		if ( isset($breakpoint['Width']) && (int)$breakpoint['Width'] !== 0 ) {
			// If we have a valid width specified, use it
			$targetWidth = round($breakpoint['Width'] * $pixelDensity);
		} else if ( isset($breakpoint['Height']) && (int)$breakpoint['Height'] !== 0 ) {
			// If we have no valid width specified, but we have a valid height specified,
			// calculate the width from the height using the original image's aspect ratio
			$targetWidth = round($breakpoint['Height'] * $aspectRatio * $pixelDensity);
		} else {
			// If we have neither a width or height specified, use the original image width
			$targetWidth = round($currentWidth * $pixelDensity);
		}
		if ( isset($breakpoint['Height']) && (int)$breakpoint['Height'] !== 0 ) {
			// If we have a valid height specified, use it
			$targetHeight = round($breakpoint['Height'] * $pixelDensity);
		} else if ( isset($breakpoint['Width']) && (int)$breakpoint['Width'] !== 0 ) {
			// If we have no valid height specified, but we have a valid width specified,
			// calculate the height from the width using the original image's aspect ratio
			$targetHeight = round($breakpoint['Width'] * $pixelDensity / $aspectRatio);
		} else {
			// If we have neither a width or height specified, use the original image height
			$targetHeight = round($currentHeight * $pixelDensity);
		}

		// If the target width and height are the original image's, or the target image is
		// larger than the original image and the aspect ratio is the same, use the original image.
		if ( ($targetWidth == $currentWidth && $targetHeight == $currentHeight) ||
			($targetWidth > $currentWidth && round($targetHeight * $aspectRatio) == $targetWidth) ) {
			return $this;
		}

		// If we have the FocusPoint extension installed, use it to resize the image
		if ( $this->hasExtension('FocusPointImage') ) {
			return $this->CroppedFocusedImage( $targetWidth, $targetHeight );
		}

		// Resize the image as required
		return $this->CroppedImage( $targetWidth, $targetHeight );
	}

	/////   Debug



}
