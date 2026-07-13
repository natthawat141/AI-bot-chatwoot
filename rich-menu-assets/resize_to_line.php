<?php

declare(strict_types=1);

$source = __DIR__.'/w-medic-rich-menu-v1.png';
$destination = __DIR__.'/w-medic-rich-menu-1200x810.png';
$image = imagecreatefrompng($source);

if ($image === false) {
    throw new RuntimeException('Cannot read source image');
}

$sourceWidth = imagesx($image);
$sourceHeight = imagesy($image);
$targetWidth = 1200;
$targetHeight = 810;
$targetRatio = $targetWidth / $targetHeight;
$sourceRatio = $sourceWidth / $sourceHeight;

if ($sourceRatio > $targetRatio) {
    $cropHeight = $sourceHeight;
    $cropWidth = (int) round($sourceHeight * $targetRatio);
    $sourceX = (int) floor(($sourceWidth - $cropWidth) / 2);
    $sourceY = 0;
} else {
    $cropWidth = $sourceWidth;
    $cropHeight = (int) round($sourceWidth / $targetRatio);
    $sourceX = 0;
    $sourceY = (int) floor(($sourceHeight - $cropHeight) / 2);
}

$output = imagecreatetruecolor($targetWidth, $targetHeight);
imagecopyresampled(
    $output,
    $image,
    0,
    0,
    $sourceX,
    $sourceY,
    $targetWidth,
    $targetHeight,
    $cropWidth,
    $cropHeight,
);

imagepng($output, $destination, 9);
imagedestroy($output);
imagedestroy($image);

[$width, $height] = getimagesize($destination);
echo $destination." {$width}x{$height}".PHP_EOL;
