import sys

path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add addAdditionalDeduction() after resetRowIndices()
add_func = """                    function resetRowIndices() {
                        $('#payment-items-body tr').each(function(idx) {
                            $(this).find('td:first-child').text(idx + 1);
                        });
                    }

                    function addAdditionalDeduction() {
                        const html = `
                            <div class="flex items-center justify-between gap-2 additional-deduction-row">
                                <div class="flex items-center gap-1.5 flex-1">
                                    <input type="checkbox" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500 deduction-checkbox" onchange="calculateAmounts()" checked>
                                    <input type="text" class="text-xs font-semibold text-slate-600 border-b border-slate-200 outline-none flex-1 deduction-name" placeholder="รายการหักเพิ่มเติม.....">
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.01" class="text-xs font-bold text-rose-500 text-right border-b border-slate-200 outline-none w-20 deduction-amount" value="0" onkeyup="calculateAmounts()" onchange="calculateAmounts()">
                                    <button type="button" onclick="$(this).closest('.additional-deduction-row').remove(); calculateAmounts();" class="text-slate-400 hover:text-rose-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        `;
                        $('#additional-deductions-container').append(html);
                    }"""

content = content.replace("""                    function resetRowIndices() {
                        $('#payment-items-body tr').each(function(idx) {
                            $(this).find('td:first-child').text(idx + 1);
                        });
                    }""", add_func)


# 2. Update calculateAmounts()
calc_old = """                        let retDeduction = 0;
                        if ($('#f-ret-enabled').is(':checked')) {
                            retDeduction = grossAmount * 0.05;
                        }

                        const netAmount = Math.max(0, grossAmount - taxDeduction - retDeduction);"""

calc_new = """                        let retDeduction = 0;
                        if ($('#f-ret-enabled').is(':checked')) {
                            retDeduction = grossAmount * 0.05;
                        }

                        let otherDeductions = 0;
                        $('.additional-deduction-row').each(function() {
                            if ($(this).find('.deduction-checkbox').is(':checked')) {
                                otherDeductions += parseFloat($(this).find('.deduction-amount').val()) || 0;
                            }
                        });

                        const netAmount = Math.max(0, grossAmount - taxDeduction - retDeduction - otherDeductions);"""

content = content.replace(calc_old, calc_new)


# 3. Update submitPaymentForm()
submit_old = """                            if (type === 'installment') {
                                title = 'จ่ายงวดงานหลัก';
                                details = $('#f-installment option:selected').text().split(' (')[0];
                                baseAmount = amt;
                            } else {
                                title = $(this).find('.item-title').val() || 'งานเพิ่มเติม';
                                details = $(this).find('.item-details').val() || '-';
                                additionalAmount += amt;
                            }

                            items.push({ type: type, title: title, details: details, amount: amt });
                        });

                        const grossAmount = baseAmount + additionalAmount;
                        
                        let taxDeduction = 0;
                        if ($('#f-tax-enabled').is(':checked')) {
                            taxDeduction = grossAmount * 0.03;
                        }

                        let retDeduction = 0;
                        if ($('#f-ret-enabled').is(':checked')) {
                            retDeduction = grossAmount * 0.05;
                        }

                        const netAmount = grossAmount - taxDeduction - retDeduction;"""

submit_new = """                            if (type === 'installment') {
                                title = 'จ่ายงวดงานหลัก';
                                details = $('#f-installment option:selected').text().split(' (')[0];
                                baseAmount = amt;
                            } else {
                                title = $(this).find('.item-title').val() || 'งานเพิ่มเติม';
                                details = $(this).find('.item-details').val() || '-';
                                additionalAmount += amt;
                            }

                            items.push({ type: type, title: title, details: details, amount: amt });
                        });

                        let otherDeductionsTotal = 0;
                        $('.additional-deduction-row').each(function() {
                            if ($(this).find('.deduction-checkbox').is(':checked')) {
                                const title = $(this).find('.deduction-name').val() || 'รายการหักเพิ่มเติม';
                                const amt = parseFloat($(this).find('.deduction-amount').val()) || 0;
                                if (amt > 0) {
                                    items.push({ type: 'other_deduction', title: title, details: 'หักจากยอดชำระ', amount: -amt });
                                    otherDeductionsTotal += amt;
                                }
                            }
                        });

                        const grossAmount = baseAmount + additionalAmount;
                        
                        let taxDeduction = 0;
                        if ($('#f-tax-enabled').is(':checked')) {
                            taxDeduction = grossAmount * 0.03;
                        }

                        let retDeduction = 0;
                        if ($('#f-ret-enabled').is(':checked')) {
                            retDeduction = grossAmount * 0.05;
                        }

                        const netAmount = grossAmount - taxDeduction - retDeduction - otherDeductionsTotal;"""

content = content.replace(submit_old, submit_new)

# 4. Update the save success callback and add openExpenseModalForPayment
save_old = """                            success: function(res) {
                                Swal.close();
                                if (res.status === 'success') {
                                    Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false})
                                    .then(() => {
                                        window.location.href = 'index.php?view=payments';
                                    });
                                } else {
                                    Swal.fire('ผิดพลาด', res.message, 'error');
                                }
                            }
                        });
                    }
                </script>"""

save_new = """                            success: function(res) {
                                Swal.close();
                                if (res.status === 'success') {
                                    Swal.fire({
                                        title: 'สำเร็จ',
                                        text: res.message,
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonText: 'บันทึกค่าใช้จ่ายโครงการ',
                                        cancelButtonText: 'ไม่บันทึก',
                                        confirmButtonColor: '#10b981',
                                        cancelButtonColor: '#f43f5e'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            openExpenseModalForPayment(pid, grossAmount, 'ค่าแรงผู้รับเหมา ' + $('#f-project option:selected').text());
                                        } else {
                                            window.location.href = 'index.php?view=payments';
                                        }
                                    });
                                } else {
                                    Swal.fire('ผิดพลาด', res.message, 'error');
                                }
                            }
                        });
                    }

                    function openExpenseModalForPayment(pid, defaultAmount, paymentName) {
                        const date = new Date().toISOString().split('T')[0];
                        
                        Swal.fire({
                            title: 'บันทึกค่าใช้จ่ายโครงการ',
                            html: `
                                <form id="exp-form-payment" class="text-left space-y-4 p-2 text-sm" enctype="multipart/form-data">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-1">รายการค่าใช้จ่าย *</label>
                                        <input type="text" name="item_name" class="swal2-input !m-0 !w-full" placeholder="ระบุรายการ" value="${paymentName}">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-500 mb-1">ประเภทค่าใช้จ่าย</label>
                                            <select name="expense_type" class="swal2-input !m-0 !w-full select-style">
                                                <option value="ค่าแรงเพิ่ม" selected>ค่าแรงเพิ่ม</option>
                                                <option value="วัสดุ">วัสดุ</option>
                                                <option value="ค่าเครื่องมือ">ค่าเครื่องมือ</option>
                                                <option value="ค่าขนส่ง">ค่าขนส่ง</option>
                                                <option value="ค่าแก้ไขงาน">ค่าแก้ไขงาน</option>
                                                <option value="สาธารณูปโภค">สาธารณูปโภค</option>
                                                <option value="อื่นๆ">อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-500 mb-1">วันที่เกิดรายการ *</label>
                                            <input type="date" name="expense_date" class="swal2-input !m-0 !w-full" value="${date}">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-1">จำนวนเงิน (บาท) *</label>
                                        <input type="number" step="0.01" name="amount" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${defaultAmount}">
                                    </div>
                                    <input type="hidden" name="status" value="อนุมัติแล้ว">
                                    <input type="hidden" name="reference_task" value="">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-1">แนบหลักฐาน (รูปภาพ หรือ PDF)</label>
                                        <input type="file" name="attachment" class="swal2-input !m-0 !w-full !p-1.5" accept="image/*,application/pdf">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-1">บันทึกเพิ่มเติม</label>
                                        <input type="text" name="note" class="swal2-input !m-0 !w-full" placeholder="ระบุรายละเอียดเพิ่มเติม" value="">
                                    </div>
                                </form>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'บันทึกค่าใช้จ่าย',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#10b981',
                            preConfirm: () => {
                                const form = document.getElementById('exp-form-payment');
                                const formData = new FormData(form);
                                
                                if (!formData.get('item_name') || !formData.get('expense_date') || !formData.get('amount')) {
                                    Swal.showValidationMessage('กรุณากรอกข้อมูลรายการ วันที่ และจำนวนเงิน');
                                    return false;
                                }

                                formData.append('action', 'expense_save');
                                formData.append('project_id', pid);
                                formData.append('id', 0); // New expense

                                return formData;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: result.value,
                                    processData: false,
                                    contentType: false,
                                    success: function(res) {
                                        Swal.close();
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false})
                                            .then(() => {
                                                window.location.href = 'index.php?view=payments';
                                            });
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error').then(() => {
                                                window.location.href = 'index.php?view=payments';
                                            });
                                        }
                                    }
                                });
                            } else {
                                window.location.href = 'index.php?view=payments';
                            }
                        });
                    }
                </script>"""

content = content.replace(save_old, save_new)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("SUCCESS!")
