import sys

history = open('e:/xampp/htdocs/rongyanghome/payroll/tabs/commission_piece_history.php', 'r', encoding='utf-8').read()
replacements = {
    'activeEditCommId': 'activeEditPieceCommId',
    "$('#commission_transaction_date').val(res.commission.transaction_date);": "$('#commission_piece_transaction_date').val(res.commission.transaction_date);",
    "$('#commissionItemsContainer').html('');": "$('#commissionPieceItemsContainer').html('');",
    'addCommissionItemRow': 'addCommissionPieceItemRow',
    'updateInvoiceTotals': 'updatePieceInvoiceTotals'
}

for k, v in replacements.items():
    history = history.replace(k, v)

open('e:/xampp/htdocs/rongyanghome/payroll/tabs/commission_piece_history.php', 'w', encoding='utf-8').write(history)

print('History fixed')
