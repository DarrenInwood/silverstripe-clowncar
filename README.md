silverstripe-clowncar
=====================

Adaptive images module for SilverStripe, based on Estelle Weyl's Clown Car Technique

## Overview

This SilverStripe module provides a method to output adaptive images, based on Estelle Weyl's Clown Car Technique, originally published by Smashing Magazine:
http://coding.smashingmagazine.com/2013/06/02/clown-car-technique-solving-for-adaptive-images-in-responsive-web-design/

It has not been extensively tested as yet, and should not be considered for production sites.

## Requirements

* SilverStripe 3.0.X (not tested with 3.1)
* Optionally integrated with Jonathon Menz's FocusPoint module, https://github.com/jonom/silverstripe-focuspoint

## Installation

Download, place the folder in your project root and run dev/build?flush=1.

## Quick Usage Overview

To start outputting images using the Clown Car technique, use in your templates:

> $Image.ClownCar

Make sure that your image is inside a container that sets a width.  The image will expand to fill the container, maintaining aspect ratio.

You can still use $Image to output a normal <img> tag.

## Documentation

See https://github.com/DarrenInwood/silverstripe-clowncar/wiki formore in-depth documentation.
