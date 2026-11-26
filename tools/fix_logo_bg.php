<?php
$src = dirname(__DIR__) . '/assets/logo.png';
$bak = dirname(__DIR__) . '/assets/logo-bak.png';
$preview = dirname(__DIR__) . '/assets/logo-preview.png';

$im = @imagecreatefrompng($src);
if (!$im) {
    fwrite(STDERR, "Cannot load logo\n");
    exit(1);
}

if (!is_file($bak)) {
    copy($src, $bak);
    echo "backup saved\n";
}

$w = imagesx($im);
$h = imagesy($im);
echo "in={$w}x{$h}\n";

// Force truecolor + alpha canvas
$work = imagecreatetruecolor($w, $h);
imagealphablending($work, false);
imagesavealpha($work, true);
$clear = imagecolorallocatealpha($work, 0, 0, 0, 127);
imagefilledrectangle($work, 0, 0, $w - 1, $h - 1, $clear);

imagealphablending($work, true);
imagecopy($work, $im, 0, 0, 0, 0, $w, $h);
imagealphablending($work, false);
imagesavealpha($work, true);
imagedestroy($im);
$im = $work;

$isNearBlack = static function (int $r, int $g, int $b, int $a): bool {
    if ($a >= 120) {
        return false;
    }
    return ($r <= 60 && $g <= 60 && $b <= 60 && ($r + $g + $b) <= 140);
};

$visited = array_fill(0, $w * $h, 0);
$stack = [];
for ($x = 0; $x < $w; $x++) {
    $stack[] = [$x, 0];
    $stack[] = [$x, $h - 1];
}
for ($y = 0; $y < $h; $y++) {
    $stack[] = [0, $y];
    $stack[] = [$w - 1, $y];
}

$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
$cleared = 0;

while ($stack) {
    [$x, $y] = array_pop($stack);
    if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) {
        continue;
    }
    $idx = $y * $w + $x;
    if ($visited[$idx]) {
        continue;
    }
    $visited[$idx] = 1;

    $rgba = imagecolorat($im, $x, $y);
    $a = ($rgba & 0x7F000000) >> 24;
    $r = ($rgba >> 16) & 0xFF;
    $g = ($rgba >> 8) & 0xFF;
    $b = $rgba & 0xFF;

    if (!$isNearBlack($r, $g, $b, $a)) {
        continue;
    }

    imagesetpixel($im, $x, $y, $transparent);
    $cleared++;
    $stack[] = [$x + 1, $y];
    $stack[] = [$x - 1, $y];
    $stack[] = [$x, $y + 1];
    $stack[] = [$x, $y - 1];
}

// Remove dark fringe next to transparent
for ($pass = 0; $pass < 2; $pass++) {
    for ($y = 1; $y < $h - 1; $y++) {
        for ($x = 1; $x < $w - 1; $x++) {
            $rgba = imagecolorat($im, $x, $y);
            $a = ($rgba & 0x7F000000) >> 24;
            if ($a >= 120) {
                continue;
            }
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (($r + $g + $b) > 150) {
                continue;
            }
            $hasTrans = false;
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $n = imagecolorat($im, $x + $dx, $y + $dy);
                if ((($n & 0x7F000000) >> 24) >= 120) {
                    $hasTrans = true;
                    break;
                }
            }
            if ($hasTrans) {
                imagesetpixel($im, $x, $y, $transparent);
                $cleared++;
            }
        }
    }
}

$minX = $w;
$minY = $h;
$maxX = 0;
$maxY = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $a = (imagecolorat($im, $x, $y) & 0x7F000000) >> 24;
        if ($a < 120) {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }
}

$pad = 8;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($w - 1, $maxX + $pad);
$maxY = min($h - 1, $maxY + $pad);
$cw = max(1, $maxX - $minX + 1);
$ch = max(1, $maxY - $minY + 1);

$out = imagecreatetruecolor($cw, $ch);
imagealphablending($out, false);
imagesavealpha($out, true);
$t = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $cw - 1, $ch - 1, $t);
imagecopy($out, $im, 0, 0, $minX, $minY, $cw, $ch);
imagepng($out, $src, 6);

// Preview on light grey so we can visually confirm transparency
$prev = imagecreatetruecolor($cw, $ch);
imagealphablending($prev, true);
$bg = imagecolorallocate($prev, 244, 246, 248);
imagefilledrectangle($prev, 0, 0, $cw - 1, $ch - 1, $bg);
imagecopy($prev, $out, 0, 0, 0, 0, $cw, $ch);
imagepng($prev, $preview, 6);

// Sample corner of saved file
$check = imagecreatefrompng($src);
$c = imagecolorat($check, 0, 0);
$a = ($c & 0x7F000000) >> 24;
echo "cleared={$cleared} out={$cw}x{$ch} corner_a={$a}\n";

imagedestroy($im);
imagedestroy($out);
imagedestroy($prev);
imagedestroy($check);
