import sys

content = open('e:/xampp/htdocs/old_comm2.php', 'r', encoding='utf-8').read()

replacements = {
    'commission_transaction_date': 'commission_piece_transaction_date',
    'commissionItemsContainer': 'commissionPieceItemsContainer',
    'invoice_total_sales': 'piece_invoice_total_sales',
    'invoice_total_commission': 'piece_invoice_total_commission',
    'loadCommissionCalc': 'loadCommissionPieceCalc',
    'resetCommissionForm': 'resetCommissionPieceForm',
    'addCommissionItemRow': 'addCommissionPieceItemRow',
    'calculateItem': 'calculatePieceItem',
    'updateInvoiceTotals': 'updatePieceInvoiceTotals',
    'saveCommission': 'savePieceCommission',
    'activeEditCommId': 'activeEditPieceCommId',
    'employeesList': 'employeesPieceList',
    'defaultAdminRate': 'defaultPieceAdminRate',
    'defaultSalesRate': 'defaultPieceSalesRate',
    'defaultHelperRate': 'defaultPieceHelperRate',
    'commItemIndex': 'commPieceItemIndex',
    'removeItemRow': 'removePieceItemRow',
    'reorderRows': 'reorderPieceRows',
    "switchTab('commission_history')": "switchTab('commission_piece_history')",
    "action: 'save_commission_transaction'": "action: 'save_commission_transaction', commission_type: 'piece'"
}

for k, v in replacements.items():
    content = content.replace(k, v)

open('e:/xampp/htdocs/rongyanghome/payroll/tabs/commission_piece_calc.php', 'w', encoding='utf-8').write(content)

history = open('e:/xampp/htdocs/old_history.php', 'r', encoding='utf-8').read()
h_replacements = {
    'commissionHistoryTableBody': 'commissionPieceHistoryTableBody',
    'loadCommissionHistory': 'loadCommissionPieceHistory',
    "switchTab('commission_calc')": "switchTab('commission_piece_calc')",
    'editCommissionTransaction': 'editPieceCommissionTransaction',
    'deleteCommissionTransaction': 'deletePieceCommissionTransaction',
    "action: 'list_commission_transactions'": "action: 'list_commission_transactions', commission_type: 'piece'"
}

for k, v in h_replacements.items():
    history = history.replace(k, v)
open('e:/xampp/htdocs/rongyanghome/payroll/tabs/commission_piece_history.php', 'w', encoding='utf-8').write(history)

print('Files created successfully')
