<?php
/**
 * School Market - Mock Images Generator
 * Uses PHP GD library to create visual mock files for schools, products, and headers.
 */

$imgDir = __DIR__ . '/assets/images';
$schoolDir = $imgDir . '/schools';

// Ensure directories exist
if (!is_dir($imgDir)) mkdir($imgDir, 0777, true);
if (!is_dir($schoolDir)) mkdir($schoolDir, 0777, true);

// 1. Generate core platform placeholders
generateImage($imgDir . '/default_logo.png', 120, 120, 'SM', [59, 130, 246], [255, 255, 255]);
generateImage($imgDir . '/default_product.png', 400, 300, 'Student Product Mock', [148, 163, 184], [255, 255, 255]);
generateImage($imgDir . '/default_teacher_product.png', 400, 300, 'Teacher Resource Mock', [16, 185, 129], [255, 255, 255]);
generateImage($imgDir . '/default_bg.jpg', 800, 600, 'Default Backdrop', [226, 232, 240], [100, 116, 139]);

// 2. Generate school logos & backdrops
$schoolsData = [
    1  => ['name' => 'Colegio Las Vegas',      'short' => 'JLV', 'color' => [27, 54, 93],     'sec' => [217, 164, 65]],
    2  => ['name' => 'Colegio Montessori',     'short' => 'MON', 'color' => [15, 81, 50],     'sec' => [197, 168, 128]],
    3  => ['name' => 'Colegio UPB',            'short' => 'UPB', 'color' => [200, 16, 46],    'sec' => [17, 24, 39]],
    4  => ['name' => 'Colegio Cumbres',        'short' => 'CUM', 'color' => [10, 37, 64],     'sec' => [209, 161, 83]],
    5  => ['name' => 'Colegio Benedictino',    'short' => 'BEN', 'color' => [0, 40, 85],      'sec' => [70, 130, 180]],
    6  => ['name' => 'Colegio Calasanz',        'short' => 'CAL', 'color' => [11, 60, 93],     'sec' => [245, 166, 35]],
    7  => ['name' => 'INEM José Félix',        'short' => 'INEM','color' => [31, 90, 59],     'sec' => [255, 215, 0]],
    8  => ['name' => 'Colegio La Salle',       'short' => 'LAS', 'color' => [0, 44, 108],     'sec' => [210, 18, 69]],
    9  => ['name' => 'Colombo Británico',      'short' => 'CCB', 'color' => [29, 78, 216],    'sec' => [239, 68, 68]],
    10 => ['name' => 'Colegio San Ignacio',    'short' => 'CSI', 'color' => [30, 58, 138],    'sec' => [59, 130, 246]]
];

$logoNames = [
    1 => 'vegas_logo.png', 2 => 'montessori_logo.png', 3 => 'upb_logo.png', 4 => 'cumbres_logo.png', 5 => 'benedictino_logo.png',
    6 => 'calasanz_logo.png', 7 => 'inem_logo.png', 8 => 'lasalle_logo.png', 9 => 'colombo_logo.png', 10 => 'sanignacio_logo.png'
];

$bgNames = [
    1 => 'vegas_bg.jpg', 2 => 'montessori_bg.jpg', 3 => 'upb_bg.jpg', 4 => 'cumbres_bg.jpg', 5 => 'benedictino_bg.jpg',
    6 => 'calasanz_bg.jpg', 7 => 'inem_bg.jpg', 8 => 'lasalle_bg.jpg', 9 => 'colombo_bg.jpg', 10 => 'sanignacio_bg.jpg'
];

foreach ($schoolsData as $id => $data) {
    // Generate logo
    generateImage($schoolDir . '/' . $logoNames[$id], 150, 150, $data['short'], $data['color'], [255, 255, 255]);
    
    // Generate background (using primary and secondary accents)
    generateImage($schoolDir . '/' . $bgNames[$id], 800, 450, $data['name'], $data['color'], $data['sec']);
}

echo "Image assets generated successfully in assets/images/!\n";

/**
 * Image creator function using GD
 */
function generateImage($filePath, $width, $height, $text, $bgColorRGB, $textColorRGB) {
    if (!extension_loaded('gd')) {
        // Fallback: Create a basic SVG string with the same extension name
        // (Modern browsers can parse SVGs saved as PNG/JPG filenames if they start with <svg>)
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}' style='background:rgb(" . implode(',', $bgColorRGB) . ")'>";
        $svg .= "<text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='rgb(" . implode(',', $textColorRGB) . ")' font-family='Arial, sans-serif' font-weight='bold' font-size='16'>{$text}</text>";
        $svg .= "</svg>";
        file_put_contents($filePath, $svg);
        return;
    }

    $image = imagecreatetruecolor($width, $height);
    
    // Allocate colors
    $bgColor = imagecolorallocate($image, $bgColorRGB[0], $bgColorRGB[1], $bgColorRGB[2]);
    $textColor = imagecolorallocate($image, $textColorRGB[0], $textColorRGB[1], $textColorRGB[2]);
    
    // Draw background
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Draw border
    $borderColor = imagecolorallocate($image, 255, 255, 255);
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

    // Write text (centered)
    $fontSize = 5; // GD Built-in font size (1 to 5)
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Save image
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        imagejpeg($image, $filePath, 85);
    } else {
        imagepng($image, $filePath);
    }
    
    imagedestroy($image);
}
