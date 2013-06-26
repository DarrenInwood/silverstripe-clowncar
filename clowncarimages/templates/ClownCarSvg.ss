<svg viewBox="0 0 $Width $Height" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">

<title>$Title</title>

<style>

svg{
	background-size: 100% 100%;
	background-repeat: no-repeat;
}
<% loop $BreakpointData %>
@media $MediaQuery {
	svg{
		background-image: url($BaseHref$Image.RelativeLink);
	}
}
<% if $RetinaImage %>@media
	$MediaQuery and (-webkit-min-device-pixel-ratio: 1.3),
	$MediaQuery and (min-resolution: 124.8dpi),
	$MediaQuery and (min-resolution: 1.3dppx) {
	svg{
		background-image: url($BaseHref$RetinaImage.RelativeLink);
	}
}<% end_if %>
<% end_loop %>
</style>
</svg>