import sys

def replace_in_file(filepath, replacements):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    for k, v in replacements.items():
        content = content.replace(k, v)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

replacements_calc = {
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

replace_in_file('e:/xampp/htdocs/rongyanghome/payroll/tabs/commission_piece_calc.php', replacements_calc)

replacements_history = {
    'commissionHistoryTableBody': 'commissionPieceHistoryTableBody',
    'loadCommissionHistory': 'loadCommissionPieceHistory',
    "switchTab('commission_calc')": "switchTab('commission_piece_calc')",
    'editCommissionTransaction': 'editPieceCommissionTransaction',
    'deleteCommissionTransaction': 'deletePieceCommissionTransaction',
    "action: 'list_commission_transactions'": "action: 'list_commission_transactions', commission_type: 'piece'",
    'activeEditCommId': 'activeEditPieceCommId',
    "#commission_transaction_date.val": "#commission_piece_transaction_date.val",
    "#commissionItemsContainer.html": "#commissionPieceItemsContainer.html",
    'addCommissionItemRow': 'addCommissionPieceItemRow',
    'updateInvoiceTotals': 'updatePieceInvoiceTotals'
}

replace_in_file('e:/xampp/htdocs/rongyanghome/payroll/tabs/commission_piece_history.php', replacements_history)

print('Replacement completed successfully')
