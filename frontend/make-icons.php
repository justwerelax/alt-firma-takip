<?php
// PNG ikon üretici — bir kez çalıştır, sonra sil
// Tarayıcıdan: http://localhost/alt-firma-takip/frontend/make-icons.php

function makeIcon(int $size, string $path): void {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

    // Gradient arka plan (köşeden köşeye mor)
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $t = ($x + $y) / ($size * 2);
            $r = (int)(102 + (118 - 102) * $t);
            $g = (int)(126 + (75  - 126) * $t);
            $b = (int)(234 + (162 - 234) * $t);
            imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
        }
    }

    $s = $size / 192.0;

    // Yardımcı: dolu dikdörtgen
    $w   = imagecolorallocate($img, 255, 255, 255);
    $pur = imagecolorallocate($img, 102, 126, 234);

    // Çatı (dolu üçgen)
    $roof = [
        (int)(96*$s),  (int)(28*$s),
        (int)(26*$s),  (int)(82*$s),
        (int)(166*$s), (int)(82*$s),
    ];
    imagefilledpolygon($img, $roof, $w);

    // Bina gövdesi
    imagefilledrectangle($img,
        (int)(34*$s), (int)(78*$s),
        (int)(158*$s),(int)(168*$s),
        $w
    );

    // Sol pencere
    imagefilledrectangle($img,
        (int)(50*$s), (int)(96*$s),
        (int)(84*$s), (int)(120*$s),
        $pur
    );

    // Sağ pencere
    imagefilledrectangle($img,
        (int)(108*$s),(int)(96*$s),
        (int)(142*$s),(int)(120*$s),
        $pur
    );

    // Kapı
    imagefilledrectangle($img,
        (int)(76*$s), (int)(122*$s),
        (int)(116*$s),(int)(168*$s),
        $pur
    );

    imagepng($img, $path);
    imagedestroy($img);
}

$dir = __DIR__ . '/assets/icons/';

makeIcon(192, $dir . 'icon-192x192.png');
makeIcon(512, $dir . 'icon-512x512.png');

echo '<h2 style="font-family:sans-serif;color:green">✅ İkonlar oluşturuldu!</h2>';
echo '<p style="font-family:sans-serif">'.
     '<b>icon-192x192.png</b> ve <b>icon-512x512.png</b> '.
     'dosyaları <code>assets/icons/</code> klasörüne kaydedildi.</p>';
echo '<img src="assets/icons/icon-192x192.png" style="border-radius:20px;box-shadow:0 4px 16px rgba(0,0,0,.2)"><br><br>';
echo '<p style="font-family:sans-serif;color:#ef4444">⚠️ Bu dosyayı şimdi silebilirsin: <code>make-icons.php</code></p>';
