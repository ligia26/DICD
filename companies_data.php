<?php
include 'includes/head.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;
session_start();
$user_id = $_SESSION['user_id']; 

$user_data = getUserData($conn, $user_id);
$company = $user_data['company'];
$is_admin = $user_data['admin'];

function getCompanyData($conn, $is_admin, $company) {
    $sql = $is_admin ? "SELECT * FROM company_data" : "SELECT * FROM company_data WHERE company = ?";
    $stmt = $conn->prepare($sql);
    if (!$is_admin) {
        $stmt->bind_param("s", $company);
    }
    $stmt->execute();
    return $stmt->get_result();
}

$company_data_result = getCompanyData($conn, $is_admin, $company);
?>

<!doctype html>
<html>
<head>
    <style>
        th, td {
            padding: 12px;
            font-size: 14px;
            word-wrap: break-word;
            white-space: normal;
            overflow-wrap: anywhere;
            vertical-align: top;
            min-width: 200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th:first-child, td:first-child {
            width: 50px !important;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include "includes/side_menu.php"; ?>
        <?php include "includes/header.php"; ?>
        <div class="page-wrapper">
            <div class="page-content">
                <h6 class="mb-0 text-uppercase">Manage Company Data</h6>
                <hr/>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <form id="company-data-form">
                                <table id="companyDataTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Select</th>
                                            <th>Short Initials</th>
                                            <th>Main Domain</th>
                                            <th>Mautic / Front / Backoffice</th>
                                            <th>DNS Type A, Value</th>
                                            <th>Sending Domain</th>
                                            <th>DNS Name</th>
                                            <th>DNS Type</th>
                                            <th>DNS Value</th>
                                            <th>IP Pool SP</th>
                                            <th>Tracking Domain</th>
                                            <th>Tracking DNS Name</th>
                                            <th>Tracking DNS Type</th>
                                            <th>Tracking DNS Value</th>
                                            <th>SPF value</th>
                                            <th>DKIM Name</th>
                                            <th>DKIM Type</th>
                                            <th>DKIM Value</th>
                                            <th>DMARC Name</th>
                                            <th>DMARC Type</th>
                                            <th>DMARC Value</th>
                                            <th>BIMI Name</th>
                                            <th>BIMI Type</th>
                                            <th>BIMI VALUE</th>
                                            <th>HTTPS</th>
                                            <th>Hosting DNS Access</th>
                                            <th>SP_API_KEY</th>
                                            <th>Subaccount</th>
                                            <th>Postmaster Tools DNS Name TXT</th>
                                            <th>Postmaster Tools DNS TXT Value</th>
                                            <th>EmailAnalyst DNS TXT Name</th>
                                            <th>EmailAnalyst DNS Value</th>
                                            <th>Postmaster Tools DNS Name Cname</th>
                                            <th>Postmaster Tools DNS Value Cname</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($row = $company_data_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><input type="checkbox" class="row-select"></td>
                                            <input type="hidden" name="id[]" value="<?= $row['id'] ?>">
                                            <?php foreach ([
                                                'Short_Initials', 'Main_Domain', 'Mautic_Front_Backoffice', 'DNS_TYPE_A_VALUE', 'Sending_Domain', 'DNS_Name', 'DNS_Type', 'DNS_Value',
                                                'IP_Pool_SP', 'Tracking_Domain', 'Tracking_DNS_Name', 'Tracking_DNS_Type', 'Tracking_DNS_Value', 'SPF_value',
                                                'DKIM_Name', 'DKIM_Type', 'DKIM_Value', 'DMARC_Name', 'DMARC_Type', 'DMARC_Value',
                                                'BIMI_Name', 'BIMI_Type', 'BIMI_Value'
                                            ] as $field): ?>
                                                <td><input type="text" name="<?= strtolower($field) ?>[]" value="<?= $row[$field] ?>" class="form-control"></td>
                                            <?php endforeach; ?>
                                            <td><input type="checkbox" name="https[]" value="1" <?= $row['HTTPS'] ? 'checked' : '' ?>></td>
                                            <?php foreach ([
                                                'Hosting_DNS_Access', 'SP_API_KEY', 'Subaccount', 'Postmaster_Tools_DNS_Name_TXT',
                                                'Postmaster_Tools_DNS_TXT_Value', 'EmailAnalyst_DNS_TXT', 'EmailAnalyst_DNS_Value',
                                                'Postmaster_Tools_DNS_Name_Cname', 'Postmaster_Tools_DNS_Value_Cname'
                                            ] as $field): ?>
                                                <td><input type="text" name="<?= strtolower($field) ?>[]" value="<?= $row[$field] ?>" class="form-control"></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="34">
                                                <?php if ($is_admin): ?>
                                                    <button type="button" class="btn btn-primary" id="saveChangesBtn">Save Changes</button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-success" id="exportExcel">Export Selected</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="toast" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#333; color:white; padding:10px; border-radius:5px; z-index:1000;">Changes saved successfully!</div>
            </div>
        </div>

        <footer class="page-footer">
            <p class="mb-0">Copyright © 2024. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function autoFillDerivedValues() {
    $('#companyDataTable tbody tr').each(function () {
        const row = $(this);
        const mainDomain = row.find('input[name="main_domain[]"]').val()?.trim();
        const sendingDomain = row.find('input[name="sending_domain[]"]').val()?.trim();

        if (mainDomain) {
            row.find('input[name="bimi_value[]"]').val(`v=BIMI1; l=https://www.${mainDomain}/img/${mainDomain}-logo.svg;`);
            row.find('input[name="bimi_name[]"]').val(`default._bimi.${mainDomain}`);
            row.find('input[name="dmarc_value[]"]').val(`v=DMARC1; p=reject; rua=mailto:dmarc@${mainDomain};`);
            row.find('input[name="dmarc_name[]"]').val(`_dmarc.crm.${mainDomain}`);
        }

        if (sendingDomain) {
            row.find('input[name="emailanalyst_dns_txt[]"]').val(`_analyst_ng_validation.${sendingDomain}`);
        }
    });
}

// Call on page load and when inputs change
$(document).ready(function () {
    autoFillDerivedValues();
    $('#companyDataTable').on('input', 'input[name="main_domain[]"], input[name="sending_domain[]"]', autoFillDerivedValues);
});

        $(document).ready(function () {
            $('#saveChangesBtn').on('click', function () {
                var formData = $('#company-data-form').serializeArray();
                $.ajax({
                    url: 'save_company_data.php',
                    type: 'POST',
                    data: formData,
                    success: function () {
                        showToast('Changes saved successfully!');
                    },
                    error: function () {
                        showToast('Error saving changes!', true);
                    }
                });
            });

            function showToast(message, isError = false) {
                var toast = $('#toast');
                toast.text(message);
                toast.css('background-color', isError ? '#d9534f' : '#333');
                toast.show();
                setTimeout(() => toast.hide(), 3000);
            }
        });

        document.getElementById('exportExcel').addEventListener('click', function () {
            const table = document.getElementById('companyDataTable');
            const rows = table.querySelectorAll('tbody tr');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim()).slice(1);
            let exportData = [headers];

            rows.forEach(row => {
                if (row.querySelector('.row-select')?.checked) {
                    let rowData = Array.from(row.querySelectorAll('td')).slice(1).map(cell => {
                        const input = cell.querySelector('input');
                        return input ? (input.type === 'checkbox' ? (input.checked ? 'Yes' : 'No') : input.value) : cell.textContent.trim();
                    });
                    exportData.push(rowData);
                }
            });

            if (exportData.length === 1) {
                alert("Please select at least one row to export.");
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(exportData);
            XLSX.utils.book_append_sheet(wb, ws, "Selected Data");
            XLSX.writeFile(wb, 'selected_company_data.xlsx');
        });
    </script>
</body>
</html>
