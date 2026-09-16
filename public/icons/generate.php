<?php
// Generate simple PNG icons for PWA
function createIcon($size, $path) {
    $img = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($img, 59, 130, 246);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);
    
    // Draw a simple box icon
    $pad = intval($size * 0.25);
    $boxSize = $size - ($pad * 2);
    imagerectangle($img, $pad, $pad, $pad + $boxSize, $pad + $boxSize, $white);
    imagesetthickness($img, max(2, intval($size * 0.03)));
    
    // Draw G letter
    $fontSize = intval($size * 0.4);
    imagestring($img, 5, intval($size * 0.35), intval($size * 0.32), "G", $white);
    
    imagepng($img, $path);
    imagedestroy($img);
}

createIcon(192, __DIR__ . '/icon-192.png');
createIcon(512, __DIR__ . '/icon-512.png');
echo "Icons generated.\n";
