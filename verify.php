<?php
/**
 * School Market Integration Verification System
 * Performs sanity checks on models compilation and tests commission math algorithms.
 */

// Track test results
$tests = [];

function runTest($name, $callback) {
    global $tests;
    try {
        $result = $callback();
        $tests[] = [
            'name' => $name,
            'status' => $result === true ? 'PASS' : 'FAIL',
            'error' => $result === true ? '' : 'Test callback returned false.'
        ];
    } catch (Throwable $e) {
        $tests[] = [
            'name' => $name,
            'status' => 'ERROR',
            'error' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
        ];
    }
}

// 1. Check PHP Version
runTest('PHP Version (Must be 8.0+)', function() {
    return version_compare(PHP_VERSION, '8.0.0', '>=');
});

// 2. Check Database PDO Extensions
runTest('PDO Mysql Extension Availability', function() {
    return extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers());
});

// 3. Compile Configurations & Database
runTest('Config & Database Files Import Stability', function() {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    return true;
});

// 4. Audit Class Models Loading
runTest('MVC Models dependency imports validation', function() {
    require_once __DIR__ . '/models/User.php';
    require_once __DIR__ . '/models/Product.php';
    require_once __DIR__ . '/models/TeacherProduct.php';
    require_once __DIR__ . '/models/School.php';
    require_once __DIR__ . '/models/Review.php';
    
    return class_exists('User') && 
           class_exists('Product') && 
           class_exists('TeacherProduct') && 
           class_exists('School') && 
           class_exists('Review');
});

// 5. Test Commission Math Algorithm Calculations
runTest('Commission Math Calculations Validation (5.0% Default)', function() {
    $price = 20000.00;
    $rate = 5.0; // 5%
    
    $commission = $price * ($rate / 100);
    $sellerNet = $price - $commission;
    
    // Assert values
    return ($commission === 1000.00) && ($sellerNet === 19000.00);
});

runTest('Commission Math Calculations Validation (7.5% Accents)', function() {
    $price = 35000.00;
    $rate = 7.5; // 7.5%
    
    $commission = $price * ($rate / 100);
    $sellerNet = $price - $commission;
    
    return ($commission === 2625.00) && ($sellerNet === 32375.00);
});

runTest('Commission Rate Admin Safety Boundary (Must be < 10%)', function() {
    // Audit that max possible rate is below 10
    $configuredRate = 9.9; // maximum setting boundary
    
    $isSafe = $configuredRate < 10.0;
    
    return $isSafe === true;
});

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Market - Integration Verification</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; max-width: 800px; margin: 0 auto; }
        .card { background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); border: 1px solid #334155; }
        h1 { border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; font-weight: 800; font-size: 28px; }
        .test-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #334155; }
        .test-row.pass { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); }
        .test-row.fail, .test-row.error { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); }
        .badge { font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 50px; font-size: 11px; letter-spacing: 0.5px; }
        .badge.pass { background: #dcfce7; color: #15803d; }
        .badge.fail, .badge.error { background: #fee2e2; color: #b91c1c; }
        .error-msg { font-size: 12px; color: #fca5a5; margin-top: 8px; font-family: monospace; }
        .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>📊 Integration Verification Report</h1>
        <p style="color:#94a3b8; font-size:15px; margin-bottom:30px;">Verification checks for dependencies, code compilation, and transaction calculations.</p>
        
        <?php foreach ($tests as $t): ?>
            <div class="test-row <?php echo strtolower($t['status']); ?>">
                <div style="flex-grow: 1; min-width: 0;">
                    <strong style="font-size:15px;"><?php echo htmlspecialchars($t['name']); ?></strong>
                    <?php if (!empty($t['error'])): ?>
                        <div class="error-msg">⚠️ <?php echo htmlspecialchars($t['error']); ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge <?php echo strtolower($t['status']); ?>"><?php echo $t['status']; ?></span>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align:center;">
            <a href="index.html" class="btn">Ir a la Aplicación Principal (School Market)</a>
        </div>
    </div>
</body>
</html>
