<?php
require_once 'connection/dbcontroller.php';

$db = new DBController();
$message = '';
$messageType = '';
$inventoryItems = [];

// Get inventory items from the previous page
if (isset($_GET['inventory_ids'])) {
    $inventoryIds = explode(',', $_GET['inventory_ids']);
    
    foreach ($inventoryIds as $id) {
        $id = intval($id);
        $item = $db->getRow('inventory', "id = $id");
        if ($item) {
            $inventoryItems[] = $item;
        }
    }
    
    if (empty($inventoryItems)) {
        $message = 'No inventory items found!';
        $messageType = 'error';
    }
} else {
    $message = 'No inventory items specified!';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $par_no = trim($_POST['par_no']);
        $fund_cluster = trim($_POST['fund_cluster']);
        
        // New fields
        $received_by = trim($_POST['received_by']);
        $received_by_position = trim($_POST['received_by_position']);
        $received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
        $issued_by = trim($_POST['issued_by']);
        $issued_by_position = trim($_POST['issued_by_position']);
        $issued_date = !empty($_POST['issued_date']) ? $_POST['issued_date'] : null;
        $reference = trim($_POST['reference']);
        
        $itemCount = count($_POST['item_name']);
        $successCount = 0;
        $errors = [];

        if (empty($par_no)) {
            throw new Exception('PAR Number is required!');
        }

        if ($db->exists('par', "par_no = '" . $db->escape($par_no) . "'")) {
            throw new Exception('PAR Number already exists: ' . $par_no);
        }

        $db->beginTransaction();

        for ($i = 0; $i < $itemCount; $i++) {
            if (empty(trim($_POST['item_name'][$i]))) {
                continue;
            }

            $data = [
                'par_no' => $par_no,
                'fund_cluster' => $fund_cluster,
                'quantity' => floatval($_POST['quantity'][$i]),
                'unit_of_measurement' => trim($_POST['unit_of_measurement'][$i]),
                'item_name' => trim($_POST['item_name'][$i]),
                'item_description' => trim($_POST['item_description'][$i]),
                'property_number' => trim($_POST['property_number'][$i]),
                'date_acquired' => !empty($_POST['date_acquired'][$i]) ? $_POST['date_acquired'][$i] : null,
                'total_cost' => !empty($_POST['total_cost'][$i]) ? floatval($_POST['total_cost'][$i]) : null,
                'received_by' => $received_by,
                'received_by_position' => $received_by_position,
                'received_date' => $received_date,
                'issued_by' => $issued_by,
                'issued_by_position' => $issued_by_position,
                'issued_date' => $issued_date,
                'reference' => $reference
            ];

            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });

            $insertId = $db->insert('par', $data);

            if ($insertId) {
                $successCount++;
            } else {
                $errors[] = "Failed to add PAR item: " . $_POST['item_name'][$i];
            }
        }

        if (empty($errors)) {
            $db->commit();
            $message = $successCount . ' PAR item(s) added successfully under PAR Number: ' . $par_no;
            $messageType = 'success';
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . $successCount . '&par_no=' . urlencode($par_no));
            exit();
        } else {
            $db->rollback();
            $message = 'Some items failed to add: ' . implode(', ', $errors);
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $db->rollback();
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

if (isset($_GET['success'])) {
    $count = intval($_GET['success']);
    $par_no = isset($_GET['par_no']) ? htmlspecialchars($_GET['par_no']) : '';
    $message = $count . ' PAR item(s) added successfully under PAR Number: ' . $par_no;
    $messageType = 'success';
    $inventoryItems = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Acknowledgement Receipt (PAR)</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Official PAR Header */
        .par-header {
            text-align: center;
            margin-bottom: 20px;
            border: 2px solid #000;
            padding: 15px;
        }

        .par-header-logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .par-header h3 {
            font-size: 11px;
            margin: 3px 0;
            font-weight: normal;
        }

        .par-header h2 {
            font-size: 13px;
            margin: 8px 0;
            font-weight: bold;
        }

        .par-header h1 {
            font-size: 14px;
            margin: 10px 0;
            font-weight: bold;
            text-decoration: underline;
        }

        .appendix {
            position: absolute;
            right: 30px;
            top: 30px;
            font-size: 11px;
        }

        /* Entity and PAR Info Section */
        .par-info-section {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 0;
        }

        .info-row {
            display: flex;
            border-bottom: 1px solid #000;
            min-height: 35px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            padding: 8px;
            width: 120px;
            border-right: 1px solid #000;
            font-size: 12px;
        }

        .info-value {
            padding: 8px;
            flex: 1;
            display: flex;
            align-items: center;
        }

        .info-value input {
            border: none;
            width: 100%;
            font-size: 12px;
            padding: 2px;
            background: transparent;
        }

        .info-value input:focus {
            outline: 1px solid #0066cc;
            background: #f0f8ff;
        }

        .split-info {
            display: flex;
            flex: 1;
        }

        .split-info .info-label {
            width: 100px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 0;
            font-size: 11px;
        }

        .items-table th {
            background: #e0e0e0;
            border: 1px solid #000;
            padding: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }

        .items-table input,
        .items-table textarea {
            width: 100%;
            border: none;
            font-size: 11px;
            padding: 2px;
            font-family: Arial, sans-serif;
            background: transparent;
        }

        .items-table input:focus,
        .items-table textarea:focus {
            outline: 1px solid #0066cc;
            background: #f0f8ff;
        }

        .items-table textarea {
            resize: vertical;
            min-height: 50px;
        }

        .items-table input[type="number"] {
            text-align: center;
        }

        .items-table input[type="date"] {
            text-align: center;
        }

        .col-qty {
            width: 60px;
            text-align: center;
        }

        .col-unit {
            width: 80px;
        }

        .col-description {
            width: 250px;
        }

        .col-property {
            width: 120px;
        }

        .col-date {
            width: 100px;
        }

        .col-amount {
            width: 100px;
            text-align: right;
        }

        /* Signature Section */
        .signature-section {
            border: 2px solid #000;
            border-top: none;
            display: flex;
            min-height: 200px;
        }

        .signature-box {
            flex: 1;
            border-right: 1px solid #000;
            padding: 10px;
            display: flex;
            flex-direction: column;
        }

        .signature-box:last-child {
            border-right: none;
        }

        .signature-box strong {
            font-size: 11px;
            margin-bottom: 5px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin: 25px 0 3px 0;
            position: relative;
        }

        .signature-line input {
            border: none;
            width: 100%;
            text-align: center;
            font-size: 11px;
            padding: 2px;
            background: transparent;
        }

        .signature-line input:focus {
            outline: 1px solid #0066cc;
            background: #f0f8ff;
        }

        .signature-text {
            font-size: 10px;
            text-align: center;
            margin-top: 2px;
        }

        .position-text {
            font-size: 10px;
            text-align: center;
            margin-top: 5px;
            font-style: italic;
        }

        .position-input {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            text-align: center;
            font-size: 10px;
            padding: 2px;
            background: transparent;
            margin: 5px 0;
        }

        .position-input:focus {
            outline: 1px solid #0066cc;
            background: #f0f8ff;
        }

        .date-line {
            margin-top: auto;
            padding-top: 10px;
        }

        .date-line .signature-line {
            margin: 5px 0 3px 0;
        }

        .date-line input[type="date"] {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            text-align: center;
            font-size: 10px;
            padding: 2px;
            background: transparent;
        }

        .date-line input[type="date"]:focus {
            outline: 1px solid #0066cc;
            background: #f0f8ff;
        }

        /* Reference Section */
        .reference {
            font-size: 10px;
            margin-top: 10px;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .reference span {
            font-weight: bold;
        }

        .reference input {
            border: none;
            border-bottom: 1px solid #000;
            flex: 1;
            font-size: 10px;
            padding: 2px;
            font-style: italic;
        }

        .reference input:focus {
            outline: 1px solid #0066cc;
            background: #f0f8ff;
        }

        /* Action Buttons */
        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #0066cc;
            color: white;
        }

        .btn-primary:hover {
            background: #0052a3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-add {
            background: #28a745;
            color: white;
        }

        .btn-add:hover {
            background: #218838;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .required {
            color: red;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .form-actions {
                display: none;
            }
            .container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($inventoryItems)): ?>
            <form method="POST" action="" id="parForm">
                <div style="position: relative;">
                    <div class="appendix">Appendix 71</div>
                    
                    <!-- Official PAR Header -->
                    <div class="par-header">
                        <h3>Republic of the Philippines</h3>
                        <h2>TECHNICAL EDUCATION AND SKILLS DEVELOPMENT AUTHORITY</h2>
                        <h2>JACOBO Z. GONZALES MEMORIAL SCHOOL OF ARTS AND TRADES</h2>
                        <h3>Brgy. San Antonio, Biñan City, Laguna</h3>
                        <h1>PROPERTY ACKNOWLEDGEMENT RECEIPT (PAR)</h1>
                    </div>

                    <!-- Entity Name and PAR Info -->
                    <div class="par-info-section">
                        <div class="info-row">
                            <div class="info-label">Entity Name:</div>
                            <div class="info-value">
                                <strong>JACOBO Z. GONZALES MEMORIAL SCHOOL OF ARTS AND TRADES</strong>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Fund Cluster:</div>
                            <div class="info-value">
                                <input type="text" name="fund_cluster" placeholder="e.g., IGP" required>
                            </div>
                            <div class="split-info">
                                <div class="info-label">PAR No.:</div>
                                <div class="info-value">
                                    <input type="text" name="par_no" placeholder="e.g., 2025-09-006" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th class="col-qty">Quantity</th>
                                <th class="col-unit">Unit</th>
                                <th class="col-description">Description</th>
                                <th class="col-property">Property No.</th>
                                <th class="col-date">Date Acquired</th>
                                <th class="col-amount">Amount</th>
                                <th style="width: 40px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <?php foreach ($inventoryItems as $index => $item): ?>
                                <tr class="item-row">
                                    <td class="col-qty">
                                        <input type="number" name="quantity[]" step="0.01" min="0" 
                                               value="<?php echo htmlspecialchars($item['quantity']); ?>" required>
                                    </td>
                                    <td class="col-unit">
                                        <input type="text" name="unit_of_measurement[]" 
                                               value="<?php echo htmlspecialchars($item['unit_of_measurement']); ?>" 
                                               placeholder="Unit/s" required>
                                    </td>
                                    <td class="col-description">
                                        <input type="text" name="item_name[]" 
                                               value="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                               placeholder="Item Name" required style="margin-bottom: 4px; font-weight: bold;">
                                        <textarea name="item_description[]" 
                                                  placeholder="Description (Model, S/N, etc.)"><?php echo htmlspecialchars($item['item_description'] ?? ''); ?></textarea>
                                    </td>
                                    <td class="col-property">
                                        <input type="text" name="property_number[]" 
                                               value="<?php echo htmlspecialchars($item['property_number'] ?? ''); ?>"
                                               placeholder="IGP-NM-09-05-25">
                                    </td>
                                    <td class="col-date">
                                        <input type="date" name="date_acquired[]" 
                                               value="<?php echo htmlspecialchars($item['date_acquired'] ?? ''); ?>">
                                    </td>
                                    <td class="col-amount">
                                        <input type="number" name="total_cost[]" step="0.01" min="0" 
                                               value="<?php echo htmlspecialchars($item['total_cost'] ?? ''); ?>"
                                               placeholder="0.00">
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($index > 0): ?>
                                            <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="signature-box">
                            <strong>Received by:</strong>
                            <div class="signature-line">
                                <input type="text" name="received_by" placeholder="Name" required>
                            </div>
                            <div class="signature-text">Signature over Printed Name of</div>
                            <div class="signature-text">End User</div>
                            <input type="text" name="received_by_position" class="position-input" 
                                   placeholder="Position (e.g., INSTRUCTOR II)" required>
                            
                            <div class="position-text">Position/Office</div><br>
                            <div class="date-line">
                                <input type="date" name="received_date" required>
                                <div class="signature-text">Date</div>
                            </div>
                        </div>
                        <div class="signature-box">
                            <strong>Issued by:</strong>
                            <div class="signature-line">
                                <input type="text" name="issued_by" placeholder="Name" required>
                            </div>
                            <div class="signature-text">Signature over Printed Name of Supply and/or</div>
                            <div class="signature-text">Property Custodian</div>
                            <input type="text" name="issued_by_position" class="position-input" 
                                   placeholder="Position (e.g., INSTRUCTOR II/ SUPPLY OFFICER)" required>
                            
                            <div class="position-text">Official Workstation</div><br>
                            <div class="date-line">
                                <input type="date" name="issued_date" required>
                                <div class="signature-text">Date</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reference">
                    <span>Reference:</span>
                    <input type="text" name="reference" placeholder="e.g., PO 25-08-182/ AUG. 18, 2025" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-add" onclick="addNewRow()">+ Add Item Row</button>
                    <button type="submit" class="btn btn-primary">Submit PAR</button>
                    <a href="inventory-input.php" class="btn btn-secondary">Back to Inventory</a>
                </div>
            </form>
        <?php else: ?>
            <div class="form-actions">
                <a href="inventory-input.php" class="btn btn-primary">Go to Inventory Input</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addNewRow() {
            const tbody = document.getElementById('itemsTableBody');
            const firstRow = tbody.querySelector('.item-row');
            const newRow = firstRow.cloneNode(true);
            
            // Clear all inputs
            newRow.querySelectorAll('input, textarea').forEach(input => {
                input.value = '';
            });
            
            // Show remove button
            const removeBtn = newRow.querySelector('.btn-remove');
            if (removeBtn) {
                removeBtn.style.display = 'inline-block';
            } else {
                const actionCell = newRow.querySelector('td:last-child');
                actionCell.innerHTML = '<button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>';
            }
            
            tbody.appendChild(newRow);
        }

        function removeRow(button) {
            const row = button.closest('.item-row');
            const tbody = document.getElementById('itemsTableBody');
            
            if (tbody.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                alert('Cannot remove the last item row!');
            }
        }

        // Auto-hide success message
        const messageDiv = document.querySelector('.message.success');
        if (messageDiv) {
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transition = 'opacity 0.5s';
                setTimeout(() => messageDiv.style.display = 'none', 500);
            }, 5000);
        }
    </script>
</body>
</html>