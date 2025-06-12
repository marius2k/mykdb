<?php
// migrate_lang_constants_to_array.php
// Usage: php migrate_lang_constants_to_array.php assets/lang/en.php assets/lang/en_array.php

if ($argc < 3) {
    echo "Usage: php migrate_lang_constants_to_array.php <input_file.php> <output_file.php>\n";
    exit(1);
}

$input = $argv[1];
$output = $argv[2];

if (!file_exists($input)) {
    echo "Input file not found: $input\n";
    exit(1);
}

$lines = file($input);
$translations = [];

foreach ($lines as $line) {
    if (preg_match("/define\s*\(\s*'([^']+)'\s*,\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)\s*;/", $line, $matches)) {
        $key = $matches[1];
        $value = stripcslashes($matches[2]);
        $translations[$key] = $value;
    }
}

// Optional: also try to extract array at the end (if exists)
$inArray = false;
foreach ($lines as $line) {
    if (preg_match('/return\s*\[\s*/', $line)) {
        $inArray = true;
        continue;
    }
    if ($inArray) {
        if (preg_match('/\];/', $line)) {
            $inArray = false;
            continue;
        }
        if (preg_match("/'([^']+)'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'\s*,?/", $line, $matches)) {
            $translations[$matches[1]] = stripcslashes($matches[2]);
        }
    }
}

// Write new array-based language file
$out = "<?php\nreturn [\n";
foreach ($translations as $k => $v) {
    $out .= "    '$k' => '" . addslashes($v) . "',\n";
}
$out .= "];\n";

file_put_contents($output, $out);

echo "Migration complete! New file: $output\n";

?>
