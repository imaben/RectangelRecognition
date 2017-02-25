<?php
define('RGB_BLACK', 0);
define('RGB_WHITE', 16777215);

// 边长为多少算一个矩形，暂定30，可调
define('MIN_LINE', 30);

$filename = 'xxx1268.jpg';
if (!file_exists($filename)) {
    echo "file $filename does not exists!";
    exit;
}

$img = imagecreatefromjpeg($filename);
list($width, $height, $type, $attr) = getimagesize($filename);

// 二值化
for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $t = round(($r * 0.333 + $g * 0.333 + $b * 0.333) / 255);
        if ($t) {
            imagesetpixel($img, $x, $y, RGB_WHITE);
        } else {
            imagesetpixel($img, $x, $y, RGB_BLACK);
        }
    }
}

// 裁剪四个边的空白，后面减少不必要的计算
$left_null = $right_null = $top_null = $bottom_null = 0;
// 取上边
for ($y = 0; $y < $height; $y++) {
    $hit = false;
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        if ($rgb != RGB_WHITE) {
            $hit = true;
            break;
        }
    }
    if ($hit) {
        $top_null = $y;
        break;
    }
}
// 取左边
for ($x = 0; $x < $width; $x++) {
    $hit = false;
    for ($y = 0; $y < $height; $y++) {
        $rgb = imagecolorat($img, $x, $y);
        if ($rgb != RGB_WHITE) {
            $hit = true;
            break;
        }
    }
    if ($hit) {
        $left_null = $x;
        break;
    }
}
// 取下边
for ($y = $height - 1; $y >= 0; $y--) {
    $hit = false;
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        if ($rgb != RGB_WHITE) {
            $hit = true;
            break;
        }
    }
    if ($hit) {
        $bottom_null = $height - $y - 1;
        break;
    }
}
// 取右边
for ($x = $width - 1; $x >= 0; $x--) {
    $hit = false;
    for ($y = 0; $y < $height ; $y++) {
        $rgb = imagecolorat($img, $x, $y);
        if ($rgb != RGB_WHITE) {
            $hit = true;
            break;
        }
    }
    if ($hit) {
        $right_null = $width - $x - 1;
        break;
    }
}

// 裁剪
$new_width = $width - $left_null - $right_null + 2;
$new_height = $height - $top_null - $bottom_null + 2;
$new_img = imagecreatetruecolor($new_width, $new_height);
imagecopy($new_img, $img, 1, 1, $left_null, $top_null, $new_width, $new_height);
imagedestroy($img);
$img = $new_img;
$width = $new_width;
$height = $new_height;

$result = [];
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        if ($rgb == RGB_WHITE) {
            continue;
        }
        // 检测到黑点
        if (($width - $x) < MIN_LINE) {
            break;
        }
        // 向右逐点扫描，判断连接黑点长度是否大于MIN_LINE
        $match = false;
        for ($xx = $x; $xx < $width; $xx++) {
            $rgb = imagecolorat($img, $xx, $y);
            if ($rgb == RGB_WHITE) { //黑点结束
                if (($xx - $x - 1) > MIN_LINE) { // 上边框符合最小边长
                    $match = true;
                }
                break;
            }
        }
        if ($match) { // 上边框符合条件，检查左边框
            $match = false;
            for ($yy = $y; $yy < $height; $yy++) {
                $rgb = imagecolorat($img, $x, $yy);
                if ($rgb == RGB_WHITE) { //黑点结束
                    if (($yy - $y - 1) > MIN_LINE) { // 左边框符合最小边长
                        $match = true;
                    }
                    break;
                }
            }
        }
        if ($match) {
            // 如果左边框符合，检查底边框，
            // 根据上边框算好底边框所有像素点并逐点扫描
            // 如果检查到像素点有白色，即非矩形
            for ($xxx = $x; $xxx < $xx; $xxx++) {
                $rgb = imagecolorat($img, $xxx, $yy - 1);
                if ($rgb == RGB_WHITE) { //检测到白点
                    $match = false;
                    break;
                }
            }
        }
        if ($match) { // 检查右边框，算法同检查底边框
            for ($yyy = $y; $yyy < $yy; $yyy++) {
                $rgb = imagecolorat($img, $xx - 1, $yyy);
                if ($rgb == RGB_WHITE) { //检测到白点
                    $match = false;
                    break;
                }
            }
        }
        if ($match) { // 检测到矩形
            $result[] = [$x, $y, $xxx, $yyy];
        }
    }
}

$i = 0;
foreach ($result as $r) {
    $w = $r[2] - $r[0];
    $h = $r[3] - $r[1];
    $im= imagecreatetruecolor($w, $h);
    imagecopy($im, $img, 0, 0, $r[0], $r[1], $w, $h);
    $file = "$i.jpg";
    imagejpeg($im, $file);
    echo "output:$file\n";
    imagedestroy($im);
    $i++;
}
imagedestroy($img);

