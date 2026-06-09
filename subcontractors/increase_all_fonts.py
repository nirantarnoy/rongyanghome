import re

path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

mapping = {
    'text-[10px]': 'text-xs',
    'text-[11px]': 'text-xs',
    'text-xs': 'text-sm',
    'text-sm': 'text-base',
    'text-base': 'text-lg',
    'text-lg': 'text-xl'
}

def replacer(match):
    return mapping[match.group(0)]

# Match precisely these utility classes
pattern = re.compile(r'\b(text-xs|text-sm|text-base|text-lg)\b|text-\[10px\]|text-\[11px\]')
content = pattern.sub(replacer, content)

# Write it back
with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS: Font sizes bumped globally in index.php")
