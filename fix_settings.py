import sys

filepath = 'e:/xampp/htdocs/rongyanghome/subcontractors/index.php'
with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

settings_start = -1
settings_end = -1
for i, line in enumerate(lines):
    if '<!-- SETTINGS SECTION -->' in line:
        settings_start = i
    if '<!-- Projects List Grid -->' in line:
        settings_end = i - 1
        break

if settings_start != -1 and settings_end != -1:
    settings_block = lines[settings_start:settings_end]
    # Remove from original location
    del lines[settings_start:settings_end]
    
    # Find <?php endif; ?>
    endif_idx = -1
    for i, line in enumerate(lines):
        if '<?php endif; ?>' in line:
            endif_idx = i
            break
            
    if endif_idx != -1:
        new_block = ['\n            <?php elseif ( === \'settings\'): ?>\n'] + settings_block + ['\n']
        lines = lines[:endif_idx] + new_block + lines[endif_idx:]
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.writelines(lines)
        print('Moved settings block successfully.')
    else:
        print('Could not find endif;')
else:
    print('Could not find settings block.')
