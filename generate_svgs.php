<?php
/**
 * Browser-runnable SVG Assets Generator
 * Creates all default and school-specific SVG logos and backgrounds.
 */

require_once __DIR__ . '/config/config.php';

$imgDir = __DIR__ . '/assets/images';
$schoolDir = $imgDir . '/schools';

// Create directories if missing
if (!is_dir($imgDir)) mkdir($imgDir, 0777, true);
if (!is_dir($schoolDir)) mkdir($schoolDir, 0777, true);

$generated = [];

// Helper to write SVG
function createSvg($path, $svgContent) {
    global $generated;
    file_put_contents($path, $svgContent);
    $generated[] = str_replace(__DIR__ . '/', '', $path);
}

// 1. Core Default Placeholders
createSvg($imgDir . '/default_logo.svg', '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
  <defs>
    <linearGradient id="g1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#3b82f6" />
      <stop offset="100%" stop-color="#10b981" />
    </linearGradient>
  </defs>
  <circle cx="50" cy="50" r="48" fill="url(#g1)" />
  <text x="50" y="55" font-family="system-ui, sans-serif" font-size="36" font-weight="900" fill="white" text-anchor="middle" dominant-baseline="middle">SM</text>
</svg>');

createSvg($imgDir . '/default_product.svg', '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" width="400" height="300">
  <rect width="400" height="300" fill="#f1f5f9" rx="10" />
  <rect x="150" y="70" width="100" height="120" fill="#cbd5e1" rx="5" />
  <line x1="170" y1="100" x2="230" y2="100" stroke="#94a3b8" stroke-width="6" stroke-linecap="round" />
  <line x1="170" y1="130" x2="210" y2="130" stroke="#94a3b8" stroke-width="6" stroke-linecap="round" />
  <text x="200" y="240" font-family="system-ui, sans-serif" font-size="16" font-weight="700" fill="#64748b" text-anchor="middle">Student Product Placeholder</text>
</svg>');

createSvg($imgDir . '/default_teacher_product.svg', '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" width="400" height="300">
  <rect width="400" height="300" fill="#ecfdf5" rx="10" />
  <path d="M200 70 L280 110 L200 150 L120 110 Z" fill="#34d399" />
  <path d="M140 125 L140 170 Q200 210 260 170 L260 125" fill="none" stroke="#059669" stroke-width="8" stroke-linecap="round" />
  <text x="200" y="240" font-family="system-ui, sans-serif" font-size="16" font-weight="700" fill="#047857" text-anchor="middle">Teacher Resource Placeholder</text>
</svg>');

createSvg($imgDir . '/default_bg.svg', '
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600">
  <rect width="800" height="600" fill="#f8fafc" />
  <circle cx="200" cy="150" r="300" fill="#eff6ff" filter="blur(50px)" />
  <circle cx="600" cy="450" r="250" fill="#ecfdf5" filter="blur(50px)" />
</svg>');


// 2. School Visual Assets Data mapping
$schools = [
    1  => ['name' => 'Las Vegas',      'short' => 'JLV', 'p' => '#1B365D', 's' => '#D9A441'],
    2  => ['name' => 'Montessori',     'short' => 'MON', 'p' => '#0F5132', 's' => '#C5A880'],
    3  => ['name' => 'Colegio UPB',    'short' => 'UPB', 'p' => '#C8102E', 's' => '#111827'],
    4  => ['name' => 'Cumbres',        'short' => 'CUM', 'p' => '#0A2540', 's' => '#D1A153'],
    5  => ['name' => 'Benedictino',    'short' => 'BEN', 'p' => '#002855', 's' => '#4682B4'],
    6  => ['name' => 'Calasanz',       'short' => 'CAL', 'p' => '#0B3C5D', 's' => '#F5A623'],
    7  => ['name' => 'INEM José Félix','short' => 'INEM','p' => '#1F5A3B', 's' => '#FFD700'],
    8  => ['name' => 'La Salle',       'short' => 'LAS', 'p' => '#002C6C', 's' => '#D21245'],
    9  => ['name' => 'Colombo Brit.',  'short' => 'CCB', 'p' => '#1D4ED8', 's' => '#EF4444'],
    10 => ['name' => 'San Ignacio',    'short' => 'CSI', 'p' => '#1E3A8A', 's' => '#3B82F6']
];

$logos = [
    1 => 'vegas_logo.svg', 2 => 'montessori_logo.svg', 3 => 'upb_logo.svg', 4 => 'cumbres_logo.svg', 5 => 'benedictino_logo.svg',
    6 => 'calasanz_logo.svg', 7 => 'inem_logo.svg', 8 => 'lasalle_logo.svg', 9 => 'colombo_logo.svg', 10 => 'sanignacio_logo.svg'
];

$bgs = [
    1 => 'vegas_bg.svg', 2 => 'montessori_bg.svg', 3 => 'upb_bg.svg', 4 => 'cumbres_bg.svg', 5 => 'benedictino_bg.svg',
    6 => 'calasanz_bg.svg', 7 => 'inem_bg.svg', 8 => 'lasalle_bg.svg', 9 => 'colombo_bg.svg', 10 => 'sanignacio_bg.svg'
];

foreach ($schools as $id => $s) {
    // Generate logo SVG
    $logoSvg = '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
  <circle cx="50" cy="50" r="46" fill="' . $s['p'] . '" stroke="' . $s['s'] . '" stroke-width="4" />
  <text x="50" y="55" font-family="system-ui, sans-serif" font-size="28" font-weight="900" fill="white" text-anchor="middle" dominant-baseline="middle">' . $s['short'] . '</text>
</svg>';
    createSvg($schoolDir . '/' . $logos[$id], trim($logoSvg));

    // Generate Background Banner SVG
    $bgSvg = '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 300" width="800" height="300">
  <defs>
    <linearGradient id="bgG' . $id . '" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="' . $s['p'] . '" />
      <stop offset="100%" stop-color="' . $s['s'] . '" />
    </linearGradient>
  </defs>
  <rect width="800" height="300" fill="url(#bgG' . $id . ')" opacity="0.15" />
  <circle cx="150" cy="80" r="140" fill="' . $s['p'] . '" opacity="0.25" />
  <circle cx="700" cy="220" r="160" fill="' . $s['s'] . '" opacity="0.2" />
  <text x="400" y="150" font-family="system-ui, sans-serif" font-size="38" font-weight="900" fill="' . $s['p'] . '" text-anchor="middle" opacity="0.3">' . $s['name'] . '</text>
</svg>';
    createSvg($schoolDir . '/' . $bgs[$id], trim($bgSvg));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SVG Assets Compiler Report</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; max-width: 800px; margin: 0 auto; }
        .card { background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); border: 1px solid #334155; }
        h1 { color: #3b82f6; border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; font-weight: 800; }
        ul { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 8px; font-family: monospace; font-size: 13px; }
        li { background: #0f172a; padding: 10px 15px; border-radius: 6px; display: flex; justify-content: space-between; border: 1px solid #1e293b; }
        .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; text-align: center; margin-top: 30px; transition: background 0.2s; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🎨 School Market SVG Assets Compiler</h1>
        <p>All institutional and layout visual resources were compiled and written successfully:</p>
        <ul>
            <?php foreach ($generated as $file): ?>
                <li>
                    <span>📄 <?php echo htmlspecialchars($file); ?></span>
                    <span style="color:#10b981;">[CREATED]</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div style="text-align:center;">
            <a href="select-school.html" class="btn">Proceed to Marketplace selection screen</a>
        </div>
    </div>
</body>
</html>
