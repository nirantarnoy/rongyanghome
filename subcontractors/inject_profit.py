import sys
import re

path_proj = r'e:\xampp\htdocs\rongyanghome\projects\index.php'
with open(path_proj, 'r', encoding='utf-8') as f:
    proj_content = f.read()

with open('profit_block.html', 'r', encoding='utf-8') as f:
    profit_block = f.read()

# Fix AJAX URL
profit_block = profit_block.replace("url: 'action.php',", "url: '../subcontractors/action.php',")

# Style tweaks to match projects/index.php
profit_block = profit_block.replace('custom-card', 'bg-white rounded-3xl shadow-sm border border-gray-100')
profit_block = profit_block.replace('text-slate-800', 'text-gray-800')
profit_block = profit_block.replace('text-slate-100', 'text-gray-100')
profit_block = profit_block.replace('bg-emerald-50', 'bg-emerald-50/50')
profit_block = profit_block.replace('bg-rose-50', 'bg-red-50/50')
profit_block = profit_block.replace('text-rose-800', 'text-red-800')
profit_block = profit_block.replace('border-rose-100', 'border-red-100')
profit_block = profit_block.replace('text-rose-600', 'text-red-600')

header = """
            <!-- ================== ADDED FROM SUBCONTRACTORS PROFIT SUMMARY ================== -->
            <div class="mb-10 mt-12">
                <h2 class="text-2xl font-bold text-[#4a3f35] mb-6">สรุปต้นทุนและการจ่ายงานโครงการ (ผู้รับเหมา)</h2>
"""
footer = """
            </div>
"""

full_block = header + profit_block.replace("<!-- ================== VIEW: PROJECT PROFIT SUMMARY ================== -->", "") + footer

target_str = "<?php elseif ($view == 'list'): ?>"

if target_str in proj_content and "loadProfitSummaryData" not in proj_content:
    proj_content = proj_content.replace(target_str, full_block + "\n        " + target_str)
    with open(path_proj, 'w', encoding='utf-8') as f:
        f.write(proj_content)
    print("Injected profit summary into projects dashboard")
else:
    print("Could not find target or already injected")
