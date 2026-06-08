import sys

path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

old_html = """                                <!-- Deductions controls -->
                                <div class="pt-3 space-y-2 border-t border-slate-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <input type="checkbox" id="f-tax-enabled" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500" onchange="calculateAmounts()" checked>
                                            <label for="f-tax-enabled" class="text-xs font-semibold text-slate-600">หักภาษี ณ ที่จ่าย (3%)</label>
                                        </div>
                                        <span class="font-bold text-rose-500" id="calc-tax-deduction">-0.00 บาท</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <input type="checkbox" id="f-ret-enabled" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500" onchange="calculateAmounts()">
                                            <label for="f-ret-enabled" class="text-xs font-semibold text-slate-600">หักเงินประกันผลงาน (5%)</label>
                                        </div>
                                        <span class="font-bold text-rose-500" id="calc-ret-deduction">-0.00 บาท</span>
                                    </div>
                                </div>"""

new_html = """                                <!-- Deductions controls -->
                                <div class="pt-3 space-y-2 border-t border-slate-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <input type="checkbox" id="f-tax-enabled" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500" onchange="calculateAmounts()" checked>
                                            <label for="f-tax-enabled" class="text-xs font-semibold text-slate-600">หักภาษี ณ ที่จ่าย (3%)</label>
                                        </div>
                                        <span class="font-bold text-rose-500" id="calc-tax-deduction">-0.00 บาท</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <input type="checkbox" id="f-ret-enabled" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500" onchange="calculateAmounts()">
                                            <label for="f-ret-enabled" class="text-xs font-semibold text-slate-600">หักเงินประกันผลงาน (5%)</label>
                                        </div>
                                        <span class="font-bold text-rose-500" id="calc-ret-deduction">-0.00 บาท</span>
                                    </div>

                                    <!-- Additional Deductions Container -->
                                    <div id="additional-deductions-container" class="space-y-2 pt-2 border-t border-slate-50"></div>
                                    <button type="button" onclick="addAdditionalDeduction()" class="text-xs text-indigo-600 font-bold hover:text-indigo-800 flex items-center gap-1 mt-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        เพิ่มรายการหักอื่นๆ
                                    </button>
                                </div>"""

if old_html in content:
    content = content.replace(old_html, new_html)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print('HTML REPLACED')
else:
    print('HTML NOT FOUND')
