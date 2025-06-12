# migrate_lang_constants_to_array.py
# Usage: python migrate_lang_constants_to_array.py input_lang.php output_lang.php

import sys
import re

if len(sys.argv) < 3:
    print("Usage: python migrate_lang_constants_to_array.py input_lang.php output_lang.php")
    sys.exit(1)

input_file = sys.argv[1]
output_file = sys.argv[2]

translations = {}

with open(input_file, encoding='utf-8') as f:
    lines = f.readlines()

# Extract define('key','value');
define_re = re.compile(r"define\s*\(\s*'([^']+)'\s*,\s*'((?:[^'\\]|\\.)*)'\s*\)\s*;")
for line in lines:
    m = define_re.search(line)
    if m:
        key = m.group(1)
        value = m.group(2).encode('utf-8').decode('unicode_escape')
        translations[key] = value

# Extract array at the end (if exists)
in_array = False
array_re = re.compile(r"'([^']+)'\s*=>\s*'((?:[^'\\]|\\.)*)'")
for line in lines:
    if re.search(r'return\s*\[', line):
        in_array = True
        continue
    if in_array:
        if re.search(r'\];', line):
            in_array = False
            continue
        m = array_re.search(line)
        if m:
            key = m.group(1)
            value = m.group(2).encode('utf-8').decode('unicode_escape')
            translations[key] = value

# Write new PHP array file
with open(output_file, 'w', encoding='utf-8') as f:
    f.write("<?php\nreturn [\n")
    for k, v in translations.items():
        # Escape single quotes
        v = v.replace("'", "\\'")
        f.write(f"    '{k}' => '{v}',\n")
    f.write("];\n")

print(f"Migration complete! New file: {output_file}")
