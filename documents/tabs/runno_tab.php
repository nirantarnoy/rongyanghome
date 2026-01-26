<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6">ตั้งค่าเลขที่เอกสาร</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Quotation Settings -->
        <div class="border rounded-xl p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-emerald-50 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">ใบเสนอราคา</h3>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">รูปแบบ (Prefix)</label>
                    <input type="text" value="QT-" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">ตัวอย่าง</label>
                    <div class="text-sm font-mono text-gray-500 bg-gray-100 p-2 rounded">QT-20240126-001</div>
                </div>
                <button class="w-full mt-2 bg-emerald-600 text-white py-2 rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>

        <!-- Invoice Settings -->
        <div class="border rounded-xl p-6 hover:shadow-md transition-shadow opacity-60">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">ใบแจ้งหนี้</h3>
            </div>
            <div class="text-center py-8 text-gray-400 text-sm">เร็วๆ นี้</div>
        </div>

        <!-- Receipt Settings -->
        <div class="border rounded-xl p-6 hover:shadow-md transition-shadow opacity-60">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-purple-50 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">ใบเสร็จรับเงิน</h3>
            </div>
            <div class="text-center py-8 text-gray-400 text-sm">เร็วๆ นี้</div>
        </div>
    </div>
</div>
