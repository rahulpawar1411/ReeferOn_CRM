<?php
/**
 * Clear black letter-hole fills; keep black "R" inside green power-circle only.
 * Restores from bak first so we start clean.
 */
$src = dirname(__DIR__) . '/assets/logo.png';
$bak = dirname(__DIR__) . '/assets/logo-bak.png';
$preview = dirname(__DIR__) . '/assets/logo-preview.png';

if (is_file($bak)) {
    copy($bak, $src);
}

$im = imagecreatefrompng($src);
if (!$im) {
    exit(1);
}
$w = imagesx($im);
$h = imagesy($im);

// Ensure truecolor alpha
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

$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);

$isNearBlack = static function (int $r, int $g, int $b, int $a): bool {
    if ($a >= 120) {
        return false;
    }
    return ($r <= 55 && $g <= 55 && $b <= 55 && ($r + $g + $b) <= 130);
};

$isLime = static function (int $r, int $g, int $b, int $a): bool {
    if ($a >= 120) {
        return false;
    }
    // solid lime disk (not thin tagline glyphs): stronger green, decent area color
    return ($g > 150 && $g > $r + 25 && $g > $b + 50 && $r > 60 && $r < 190 && $b < 120);
};

// 1) Clear outer black via edge flood
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
$clearedEdge = 0;
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
    $c = imagecolorat($im, $x, $y);
    $a = ($c & 0x7F000000) >> 24;
    $r = ($c >> 16) & 0xFF;
    $g = ($c >> 8) & 0xFF;
    $b = $c & 0xFF;
    if (!$isNearBlack($r, $g, $b, $a)) {
        continue;
    }
    imagesetpixel($im, $x, $y, $transparent);
    $clearedEdge++;
    $stack[] = [$x + 1, $y];
    $stack[] = [$x - 1, $y];
    $stack[] = [$x, $y + 1];
    $stack[] = [$x, $y - 1];
}

// 2) Find largest contiguous lime blob in UPPER-LEFT (emblem only)
$seen = array_fill(0, $w * $h, 0);
$best = null;
$bestCount = 0;
$searchMaxX = (int) ($w * 0.55);
$searchMaxY = (int) ($h * 0.55);

for ($sy = 0; $sy < $searchMaxY; $sy++) {
    for ($sx = 0; $sx < $searchMaxX; $sx++) {
        $sidx = $sy * $w + $sx;
        if ($seen[$sidx]) {
            continue;
        }
        $c = imagecolorat($im, $sx, $sy);
        $a = ($c & 0x7F000000) >> 24;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if (!$isLime($r, $g, $b, $a)) {
            $seen[$sidx] = 1;
            continue;
        }

        $q = [[$sx, $sy]];
        $seen[$sidx] = 1;
        $pts = [];
        $minX = $sx;
        $maxX = $sx;
        $minY = $sy;
        $maxY = $sy;
        while ($q) {
            [$x, $y] = array_pop($q);
            $pts[] = [$x, $y];
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                    continue;
                }
                $nidx = $ny * $w + $nx;
                if ($seen[$nidx]) {
                    continue;
                }
                $seen[$nidx] = 1;
                $nc = imagecolorat($im, $nx, $ny);
                $na = ($nc & 0x7F000000) >> 24;
                $nr = ($nc >> 16) & 0xFF;
                $ng = ($nc >> 8) & 0xFF;
                $nb = $nc & 0xFF;
                if ($isLime($nr, $ng, $nb, $na)) {
                    $q[] = [$nx, $ny];
                }
            }
        }
        $count = count($pts);
        if ($count > $bestCount) {
            $bestCount = $count;
            $best = compact('minX', 'maxX', 'minY', 'maxY', 'count');
            $best['pts'] = $pts;
        }
    }
}

if (!$best || $bestCount < 200) {
    fwrite(STDERR, "Green circle not found (count={$bestCount})\n");
    exit(1);
}

$cx = (int) round(($best['minX'] + $best['maxX']) / 2);
$cy = (int) round(($best['minY'] + $best['maxY']) / 2);
$radius = (int) round(max($best['maxX'] - $best['minX'], $best['maxY'] - $best['minY']) / 2) + 4;
echo "green_blob={$bestCount} center={$cx},{$cy} r={$radius}\n";

// 3) Clear remaining near-black EXCEPT inside green circle (black R)
$clearedHoles = 0;
$kept = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($im, $x, $y);
        $a = ($c & 0x7F000000) >> 24;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if (!$isNearBlack($r, $g, $b, $a)) {
            continue;
        }
        $dx = $x - $cx;
        $dy = $y - $cy;
        if (($dx * $dx + $dy * $dy) <= ($radius * $radius)) {
            $kept++;
            continue;
        }
        imagesetpixel($im, $x, $y, $transparent);
        $clearedHoles++;
    }
}

imagepng($im, $src, 6);

$prev = imagecreatetruecolor($w, $h);
imagealphablending($prev, true);
$bg = imagecolorallocate($prev, 244, 246, 248);
imagefilledrectangle($prev, 0, 0, $w - 1, $h - 1, $bg);
imagecopy($prev, $im, 0, 0, 0, 0, $w, $h);
imagepng($prev, $preview, 6);

echo "edge={$clearedEdge} holes={$clearedHoles} kept_R={$kept}\n";
imagedestroy($im);
imagedestroy($prev);
