import sys
import re

path_sub = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path_sub, 'r', encoding='utf-8') as f:
    sub_content = f.read()

# Find profit_summary block
start_str = "<?php elseif ($view === 'profit_summary'): ?>"
end_str = "<?php endif; ?>\n\n        </div>\n    </main>"

start_idx = sub_content.find(start_str)
end_idx = sub_content.rfind("<?php endif; ?>")

if start_idx != -1 and end_idx != -1:
    profit_block = sub_content[start_idx + len(start_str):end_idx]
    
    # Remove from subcontractors/index.php
    sub_content = sub_content[:start_idx] + sub_content[end_idx:]
    
    # Remove sidebar menu
    sidebar_str = """<a href="index.php?view=profit_summary" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-base font-medium <?= $view === 'profit_summary' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>สรุปกำไรโปรเจค</span>
                </a>"""
    
    sub_content = sub_content.replace(sidebar_str, "")
    
    with open(path_sub, 'w', encoding='utf-8') as f:
        f.write(sub_content)
    print("Removed profit_summary from subcontractors")

    # Now let's save the profit_block to a file so we can inspect it and inject it correctly
    with open('profit_block.html', 'w', encoding='utf-8') as f:
        f.write(profit_block)
else:
    print("profit_summary block not found!")
