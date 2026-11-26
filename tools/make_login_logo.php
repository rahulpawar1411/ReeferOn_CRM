<?php
/**
 * Build a login-safe logo on pure white (no black background).
 */
$src = dirname(__DIR__) . '/assets/logo.png';
$dst = dirname(__DIR__) . '/assets/logo-login.png';

$im = imagecreatefrompng($src);
if (!$im) {
    exit(1);
}
$w = imagesx($im);
$h = imagesy($im);

$out = imagecreatetruecolor($w, $h);
imagealphablending($out, true);
$white = imagecolorallocate($out, 255, 255, 255);
imagefilledrectangle($out, 0, 0, $w - 1, $h - 1, $white);
imagecopy($out, $im, 0, 0, 0, 0, $w, $h);

// Also replace any leftover pure-black pixels outside green-R with white
// (safety net if transparency was lost in some viewers)
$cx = 152;
$cy = 124;
$radius = 96;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($out, $x, $y);
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($r > 25 || $g > 25 || $b > 25) {
            continue;
        }
        $dx = $x - $cx;
        $dy = $y - $cy;
        if (($dx * $dx + $dy * $dy) <= ($radius * $radius)) {
            continue; // keep black R
        }
        imagesetpixel($out, $x, $y, $white);
    }
}

imagepng($out, $dst, 6);
echo "wrote logo-login.png {$w}x{$h}\n";
imagedestroy($im);
imagedestroy($out);
