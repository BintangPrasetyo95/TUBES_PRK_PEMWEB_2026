<?php
// Cek aturan lokal sebelum PR. Jalankan dari folder kelompok_XX: php tools/cek_aturan.php
// Alat bantu kasar: peringatan perlu diperiksa manual. Tidak menggantikan review.
$root = dirname(__DIR__);
$bad_files = ['composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'yarn.lock'];
$bad_dirs = ['vendor', 'node_modules'];
$bad_pat = '/Illuminate\\\\|CodeIgniter|react(-dom)?(\.min)?\.js|vue(\.global)?(\.min)?\.js|createApp\(|alpinejs|htmx|tailwindcss/i';
$errors = [];
$checked = 0;

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    $path = $f->getPathname();
    $rel = substr($path, strlen($root) + 1);
    if (preg_match('#(^|[\\\\/])(' . implode('|', $bad_dirs) . ')[\\\\/]#', $rel)) {
        $errors[] = "Folder terlarang: $rel";
        continue;
    }
    $checked++;
    if (in_array($f->getFilename(), $bad_files, true)) {
        $errors[] = "File terlarang: $rel";
    }
    if ($f->getSize() > 5 * 1024 * 1024) {
        $errors[] = "Lebih dari 5 MB: $rel";
    }
    $ext = strtolower($f->getExtension());
    if (in_array($ext, ['php', 'js', 'html', 'css'], true) && $f->getFilename() !== 'cek_aturan.php') {
        if (preg_match($bad_pat, (string) file_get_contents($path), $m)) {
            $errors[] = "Indikasi framework ($m[0]), periksa manual: $rel";
        }
    }
    if ($ext === 'php') {
        // Pemeriksaan sintaks tanpa exec (tidak bergantung PATH). CI tetap memakai php -l.
        try {
            token_get_all((string) file_get_contents($path), TOKEN_PARSE);
        } catch (ParseError $e) {
            $errors[] = "Syntax error: $rel (baris " . $e->getLine() . "): " . $e->getMessage();
        }
    }
}

echo "PHP " . PHP_VERSION . " | file diperiksa: $checked" . PHP_EOL;
if (PHP_VERSION_ID >= 80300) {
    echo "Peringatan: CI memakai PHP 8.2, hindari sintaks 8.3 ke atas." . PHP_EOL;
}
if ($errors) {
    echo implode(PHP_EOL, $errors) . PHP_EOL . "GAGAL" . PHP_EOL;
    exit(1);
}
echo "OK, siap PR" . PHP_EOL;
