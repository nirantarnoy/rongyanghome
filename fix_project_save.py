import sys

filepath = 'e:/xampp/htdocs/rongyanghome/subcontractors/action.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace in project_save
insertion = """
    if (mysqli_query(, )) {
         =  > 0 ?  : mysqli_insert_id();
        
        // Handle assigned subcontractors
        if (isset(['assigned_subs_json'])) {
             = json_decode(['assigned_subs_json'], true);
            mysqli_query(, "DELETE FROM project_assigned_subcontractors WHERE project_id = ");
            if (is_array()) {
                foreach ( as ) {
                     = mysqli_real_escape_string(, ['job_type']);
                     = (int)['subcontractor_id'];
                     = (float)['contract_amount'];
                    if ( > 0) {
                         = "INSERT INTO project_assigned_subcontractors (company_id, project_id, job_type, subcontractor_id, contract_amount) 
                                    VALUES (, , '', , )";
                        mysqli_query(, );
                    }
                }
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลโปรเจ็คเรียบร้อยแล้ว', 'id' => ]);
"""

old_code = """
    if (mysqli_query(, )) {
         =  > 0 ?  : mysqli_insert_id();
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลโปรเจ็คเรียบร้อยแล้ว', 'id' => ]);
"""

content = content.replace(old_code.strip(), insertion.strip())

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated project_save in action.php')
