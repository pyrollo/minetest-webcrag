<?php

$wwwimgpath = realpath('img');

$maxsize = 128;
$sizes = array( 32, 128 );

function list_images($images) {
	$ret = array();
	if (gettype($images) == 'array') {
		foreach ($images as $image) {
			foreach (list_images($image) as $retitem) {
				$ret[] = $retitem;
			}
		}
	}
	if (gettype($images) == 'string') {
		$parts = explode('^', $images);
		foreach ($parts as $part) {
			if ($part != '' && $part[0] != '[') {
				$ret[] = $part;
			}
		}
	}
	return $ret;
}

function get_item_image_name($itemname, $size) {
	return str_replace(':', '_', $itemname).'_'.$size.'px.png';
}

// GD Simplification functions

function image_new($width, $height) {
	$new = imagecreatetruecolor($width, $height);
	imagesavealpha($new, true); 
    $trans_colour = imagecolorallocatealpha($new, 0, 0, 0, 127);
    imagefill($new, 0, 0, $trans_colour);
	return $new;
}

function image_resize(&$img, $width, $height) {
	$old = $img;
	$new = image_new($width, $height);
    imagecopyresampled($new, $old, 0, 0, 0, 0, $width, $height, imagesx($old), imagesy($old));
	imagedestroy($old);
	$img = $new;
}

function image_transform(&$img, $matrix) {
	$old = $img;
	$new = imageaffine ($old , $matrix);

	imagedestroy($old);
	$img = $new;
}

// Image processing functions

function save_all_sizes($image, $itemname) {
	global $wwwimgpath, $sizes;

	foreach ($sizes as $size) {
		$resized = image_new($size, $size);
	    imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));
		imagepng($resized, $wwwimgpath.'/'.get_item_image_name($itemname, $size));
		imagedestroy($resized);
	} 
}

function generate_overlayed_image($string) {
	global $mtimgpath, $maxsize;

	$result = image_new($maxsize,$maxsize);

	$parts = explode('^', $string);
	foreach ($parts as $part) {
		if ($part != '' && $part[0] != '[') {

			$overlay = imagecreatefrompng ($mtimgpath.'/'.$part);
			image_resize($overlay, $maxsize,$maxsize);
			imagecopy($result, $overlay, 0, 0, 0, 0, imagesx($overlay), imagesy($overlay));
			imagedestroy($overlay);
		}
	}
	return $result;
}

function generate_item_image($string, $itemname) {
	$result = generate_overlayed_image($string);
	save_all_sizes($result, $itemname);
	imagedestroy($result);
}

function generate_bloc_image($topstring, $bottomstring, $rearleftstring, 
				 		     $frontrightstring, $rearrightstring, $frontleftstring, 
                             $itemname) {
	global $maxsize; 

	// Create faces images
	$imgtop        = generate_overlayed_image($topstring);
	$imgfrontleft  = generate_overlayed_image($frontleftstring);
	$imgfrontright = generate_overlayed_image($frontrightstring);

	$imgbottom     = generate_overlayed_image($bottomstring);
	$imgrearleft   = generate_overlayed_image($rearleftstring);
	$imgrearright  = generate_overlayed_image($rearrightstring);

	// Light adjustment
	imagefilter ($imgbottom,     IMG_FILTER_BRIGHTNESS, -50 );
	imagefilter ($imgrearleft,   IMG_FILTER_BRIGHTNESS, -30 );
	imagefilter ($imgrearright,  IMG_FILTER_BRIGHTNESS,   0 );
	imagefilter ($imgtop,        IMG_FILTER_BRIGHTNESS, -10 );
	imagefilter ($imgfrontleft,  IMG_FILTER_BRIGHTNESS,  10 );
	imagefilter ($imgfrontright, IMG_FILTER_BRIGHTNESS, -30 );

	// Geometry

	$cos = cos(3.1415/6);
	$sin = sin(3.1415/6);
	$scale = 0.47;

	// a, b, c, d, e ,f : x' = ax + cy + e, y' = bx + dy + f
	$transformleft  = array($cos*$scale,  $sin*$scale, 0, $scale, 0, 0);
	$transformright = array($cos*$scale, -$sin*$scale, 0, $scale, 0, 0);
	$transformtop   = array($cos*$scale*0.99, -$sin*$scale, $cos*$scale*0.99, $sin*$scale, 0, 0); // 0.99 factor to avoid extra pixel on sides (?)

	image_transform($imgfrontleft,  $transformleft);
	image_transform($imgrearleft,   $transformright);
	image_transform($imgfrontright, $transformright);
	image_transform($imgrearright,  $transformleft);
	image_transform($imgbottom,     $transformtop);
	image_transform($imgtop,        $transformtop);

	$xcenter = $maxsize/2;
	$xleft =  $xcenter - imagesx($imgrearleft);
	$ycenter = $maxsize/2;
	$yheight = imagesy($imgtop)/2 + imagesy($imgfrontleft);
	$ytop = $ycenter - $yheight/2;
	$ybottom = $ycenter + $yheight/2 - imagesy($imgbottom);
	$ysides = $ytop + imagesy($imgtop)/2;

	// Composition
	$result = image_new($maxsize,$maxsize);
	imagecopy($result, $imgbottom,     $xleft,   $ybottom, 0, 0, imagesx($imgbottom),    imagesy($imgbottom));
	imagecopy($result, $imgrearleft,   $xleft,   $ytop,    0, 0, imagesx($imgrearleft),  imagesy($imgrearleft));
	imagecopy($result, $imgrearright,  $xcenter, $ytop,    0, 0, imagesx($imgrearright), imagesy($imgrearright));
	imagecopy($result, $imgfrontleft,  $xleft,   $ysides,  0, 0, imagesx($imgrearleft),  imagesy($imgrearleft));
	imagecopy($result, $imgfrontright, $xcenter, $ysides,  0, 0, imagesx($imgrearright), imagesy($imgrearright));
	imagecopy($result, $imgtop,        $xleft,   $ytop,    0, 0, imagesx($imgbottom),    imagesy($imgbottom));

	// Ressource freeing
	imagedestroy($imgtop);
	imagedestroy($imgfrontleft);
	imagedestroy($imgfrontright);
	imagedestroy($imgbottom);
	imagedestroy($imgrearleft);
	imagedestroy($imgrearright);

	// Save result(s)
	save_all_sizes($result, $itemname);
	imagedestroy($result);
}

// File time coparizon functions

// Returns time of oldest image file, or false if any file is missing
function get_oldest_image_time($itemname) {
	global $sizes, $wwwimgpath;
	$time = false;

	foreach ($sizes as $size) {
		$file = $wwwimgpath.'/'.get_item_image_name($itemname, $size);
		if (!file_exists($file))
			return false; // If any missing file, images must be recreated

		$filestat = stat($file);
		$filetime = $filestat[9];
		if (!$time or ($time > $filetime)) 
			$time = $filetime;
	}
	return $time;
}

function is_mt_file_newer($file, $time) {
	global $mtimgpath;
	$filestat = stat($mtimgpath.'/'.$file);
	return $filestat[9] > $time;
}


function is_image_uptodate($item) {

	if (!($time = get_oldest_image_time($item['name']))) {
		return false;
	}

	if ($item['inventory_image']) {
		foreach (list_images($item['inventory_image']) as $file) {
			if (is_mt_file_newer($file, $time)) {
				return false;
			}
		}
	}

	if ($item['tiles']) {
		foreach(list_images($item['tiles']) as $file) {
			if (is_mt_file_newer($file, $time)) {
				return false;
			}
		}
	}
	return true;
}
// Main functions

function get_image_url($item, $size) {
	if (gettype($item) == 'array') 
		$item = $item['name'];

	return 'img/'.get_item_image_name($item, $size);
}

function prepare_images($item) {
	if (!is_image_uptodate($item)) {
		if ($item['inventory_image']) {
			generate_item_image($item['inventory_image'], $item['name']);
			return;
		}

		if ($item['tiles']) {
			$tiles = array();
			if (gettype($item['tiles']) == 'array') {
				for ($index = 0; $index < 6; $index++) {
					if ($index < count($item['tiles']))
						$tiles[$index] = $item['tiles'][$index];
					else {
						$tiles[$index] = $item['tiles'][count($item['tiles'])-1];
					}
				}
			}

			if (gettype($item['tiles']) == 'string') {
				for ($index = 0; $index < 6; $index++) {
					$tiles[$index] = $item['tiles'];
				}
			}

			generate_bloc_image($tiles[0], $tiles[1], $tiles[2], $tiles[3], $tiles[4], $tiles[5], $item['name']);
			return;
		}
	}
}
?>
