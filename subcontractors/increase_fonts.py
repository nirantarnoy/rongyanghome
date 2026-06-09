import sys

path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Increase sidebar text size
content = content.replace('class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all', 'class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-base font-medium"')

# Increase settings page text size
content = content.replace('text-sm text-center font-bold text-slate-700', 'text-base text-center font-bold text-slate-700')
content = content.replace('text-sm hover:bg-blue-50', 'text-base hover:bg-blue-50')
content = content.replace('px-4 py-1.5 text-sm">', 'px-4 py-1.5 text-base">')

# Also if they meant the projects page filter dropdowns
content = content.replace('text-sm focus:border-emerald-500', 'text-base focus:border-emerald-500')
content = content.replace('text-sm text-slate-600 focus:border-emerald-500', 'text-base text-slate-600 focus:border-emerald-500')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("SUCCESS!")
