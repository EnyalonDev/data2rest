<?php
$es = include __DIR__ . '/../src/I18n/es.php';
$en = include __DIR__ . '/../src/I18n/en.php';
$pt = include __DIR__ . '/../src/I18n/pt.php';

function flatten($array, $prefix = '')
{
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flatten($value, $prefix . $key . '.'));
        } else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}

$flatEs = flatten($es);
$flatEn = flatten($en);
$flatPt = flatten($pt);

$keysEs = array_keys($flatEs);
$keysEn = array_keys($flatEn);
$keysPt = array_keys($flatPt);

$missingEn = array_diff($keysEs, $keysEn);
$missingPt = array_diff($keysEs, $keysPt);

$extraEn = array_diff($keysEn, $keysEs);
$extraPt = array_diff($keysPt, $keysEs);

echo "Reporte de Sincronización de Idiomas:\n";
echo "=====================================\n";
echo "Total Llaves ES: " . count($keysEs) . "\n";
echo "Total Llaves EN: " . count($keysEn) . "\n";
echo "Total Llaves PT: " . count($keysPt) . "\n\n";

if (empty($missingEn)) {
    echo "[OK] EN tiene todas las llaves de ES.\n";
} else {
    echo "[WARN] Faltan " . count($missingEn) . " llaves en EN:\n";
    foreach ($missingEn as $k)
        echo " - $k\n";
}

if (empty($missingPt)) {
    echo "[OK] PT tiene todas las llaves de ES.\n";
} else {
    echo "[WARN] Faltan " . count($missingPt) . " llaves en PT:\n";
    foreach ($missingPt as $k)
        echo " - $k\n";
}

if (!empty($extraEn)) {
    echo "\n[INFO] EN tiene " . count($extraEn) . " llaves extra (posiblemente obsoletas):\n";
    // foreach ($extraEn as $k) echo " - $k\n";
}
if (!empty($extraPt)) {
    echo "\n[INFO] PT tiene " . count($extraPt) . " llaves extra (posiblemente obsoletas):\n";
    // foreach ($extraPt as $k) echo " - $k\n";
}
