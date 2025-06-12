# migrate_constants_to_function.py
# Usage: python migrate_constants_to_function.py <directory_with_php_files>

import os
import re
import sys

if len(sys.argv) < 2:
    print("Usage: python migrate_constants_to_function.py <directory_with_php_files>")
    sys.exit(1)

root_dir = sys.argv[1]

# Regex for <?=lang_xxx?>
short_echo_re = re.compile(r"<\?=\s*(lang_[a-zA-Z0-9_]+)\s*\?>")
# Regex for <?php echo lang_xxx; ?>
long_echo_re = re.compile(r"<\?php\s+echo\s+(lang_[a-zA-Z0-9_]+)\s*;\s*\?>")
# Regex for bare usage in PHP code (lang_xxx)
bare_re = re.compile(r"\b(lang_[a-zA-Z0-9_]+)\b")

for subdir, dirs, files in os.walk(root_dir):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(subdir, file)
            with open(path, encoding='utf-8') as f:
                content = f.read()

            # Replace <?=lang_xxx?> with <?= __('lang_xxx') ?>
            content = short_echo_re.sub(r"<?= __('\1') ?>", content)
            # Replace <?php echo lang_xxx; ?> with <?php echo __('lang_xxx'); ?>
            content = long_echo_re.sub(r"<?php echo __('\1'); ?>", content)

            # Optionally, replace bare lang_xxx in PHP code (not in strings or comments)
            # This is risky, so only do it if you are sure!
            # content = bare_re.sub(r"__('\1')", content)

            with open(path, 'w', encoding='utf-8') as f:
                f.write(content)

            print(f"Processed {path}")

print("Migration complete! All language constants replaced with __('key').")
