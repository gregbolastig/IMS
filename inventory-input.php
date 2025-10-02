<?php
require_once 'connection/dbcontroller.php';

$db = new DBController();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $inventory_code_type = $_POST['inventory_code_type'];
        $itemCount = count($_POST['item_name']);
        $successCount = 0;
        $errors = [];
        $insertedIds = [];

        // Begin transaction for multiple inserts
        $db->beginTransaction();

        for ($i = 0; $i < $itemCount; $i++) {
            // Skip empty items
            if (empty(trim($_POST['item_name'][$i]))) {
                continue;
            }

            // Prepare data array for each item
            $data = [
                'item_name' => trim($_POST['item_name'][$i]),
                'item_description' => trim($_POST['item_description'][$i]),
                'quantity' => floatval($_POST['quantity'][$i]),
                'unit_of_measurement' => trim($_POST['unit_of_measurement'][$i]),
                'property_number' => trim($_POST['property_number'][$i]),
                'accountable_person' => trim($_POST['accountable_person'][$i]),
                'date_acquired' => !empty($_POST['date_acquired'][$i]) ? $_POST['date_acquired'][$i] : null,
                'fund_cluster' => trim($_POST['fund_cluster'][$i]),
                'remarks_notes' => trim($_POST['remarks_notes'][$i]),
                'unit_cost' => !empty($_POST['unit_cost'][$i]) ? floatval($_POST['unit_cost'][$i]) : null,
                'total_cost' => !empty($_POST['total_cost'][$i]) ? floatval($_POST['total_cost'][$i]) : null,
                'inventory_code_type' => $inventory_code_type
            ];

            // Remove null values
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });

            // Insert into database
            $insertId = $db->insert('inventory', $data);

            if ($insertId) {
                $successCount++;
                $insertedIds[] = $insertId;
            } else {
                $errors[] = "Failed to add item: " . $_POST['item_name'][$i];
            }
        }

        if (empty($errors)) {
            $db->commit();
            
            // Redirect based on inventory code type
            $ids = implode(',', $insertedIds);
            
            switch ($inventory_code_type) {
                case 'RIS':
                    header('Location: ris-input.php?inventory_ids=' . $ids);
                    exit();
                case 'PAR':
                    header('Location: par-input.php?inventory_ids=' . $ids);
                    exit();
                case 'ICS':
                    header('Location: ics-input.php?inventory_ids=' . $ids);
                    exit();
                default:
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . $successCount);
                    exit();
            }
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

// Check for success message from redirect
if (isset($_GET['success'])) {
    $count = intval($_GET['success']);
    $message = $count . ' inventory item(s) added successfully!';
    $messageType = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Inventory Item(s)</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    zoom: 0.98;
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

h1 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
    font-size: 24px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #0066cc; /* broken line */
}


/* Message Styles */
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

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: Arial, sans-serif;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0066cc;
    background: #f0f8ff;
    box-shadow: 0 0 5px rgba(0, 102, 204, 0.2);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-group select {
    cursor: pointer;
    background: white;
}

/* Form Row - Two columns */
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

/* Item Group Styles */
.item-group {
    border: 1px solid #000;
    padding: 20px;
    margin-bottom: 25px;
    background: #fafafa;
    border-radius: 4px;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #0066cc;
}

.item-header h3 {
    color: #0066cc;
    font-size: 18px;
    margin: 0;
}

/* Required Field Indicator */
.required {
    color: #dc3545;
    margin-left: 3px;
}

/* Button Styles */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
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
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 102, 204, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
}

.btn-remove {
    background: #dc3545;
    color: white;
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
}

.btn-remove:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

/* Form Actions */
.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
    padding-top: 20px;
    border-top: 2px solid #e0e0e0;
}

/* Input Placeholder Styles */
input::placeholder,
textarea::placeholder {
    color: #999;
    font-style: italic;
}

/* Number Input Styles */
input[type="number"] {
    -moz-appearance: textfield;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Date Input Styles */
input[type="date"] {
    cursor: pointer;
}

/* Select Dropdown Styles */
select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 20px;
    padding-right: 40px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 20px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .form-row .form-group {
        margin-bottom: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    h1 {
        font-size: 20px;
    }
    
    .item-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

/* Print Styles */
@media print {
    body {
        background: white;
        padding: 0;
    }
    
    .container {
        box-shadow: none;
        padding: 0;
    }
    
    .form-actions,
    .btn-remove {
        display: none;
    }
    
    .message {
        display: none;
    }
}

/* Animation for success message */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message {
    animation: fadeIn 0.3s ease-in;
}

/* Hover effects for form inputs */
.form-group input:not(:focus):hover,
.form-group select:not(:focus):hover,
.form-group textarea:not(:focus):hover {
    border-color: #999;
}

/* Disabled state */
input:disabled,
select:disabled,
textarea:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Focus visible for accessibility */
*:focus-visible {
    outline: 2px solid #0066cc;
    outline-offset: 2px;
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Inventory Code</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="inventoryForm">

            <div class="form-group">
                <label for="inventory_code_type">Inventory Code Type: <span class="required">*</span></label>
                <select id="inventory_code_type" name="inventory_code_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="ICS">ICS (Inventory Custodian Slip)</option>
                    <option value="PAR">PAR (Property Acknowledgement Receipt)</option>
                    <option value="RIS">RIS (Requisition and Issue Slip)</option>
                </select>
            </div>

            <div id="itemsContainer">
                <div class="item-group" data-item-index="0">
                    <div class="item-header">
                        <h3>Item #1</h3>
                        <button type="button" class="btn-remove" onclick="removeItem(this)" style="display:none;">Remove</button>
                    </div>

                    <div class="form-group">
                        <label>Item Name: <span class="required">*</span></label>
                        <input type="text" name="item_name[]" required>
                    </div>

                    <div class="form-group">
                        <label>Item Description:</label>
                        <textarea name="item_description[]" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity: <span class="required">*</span></label>
                            <input type="number" name="quantity[]" step="0.01" min="0" required class="quantity-input">
                        </div>

                        <div class="form-group">
                            <label>Unit of Measurement: <span class="required">*</span></label>
                            <input type="text" name="unit_of_measurement[]" 
                                   placeholder="pc(s)/box(s)/roll(s)/rem(s)/pack(s)/ml/etc." required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Property Number:</label>
                            <input type="text" name="property_number[]">
                        </div>

                        <div class="form-group">
                            <label>Accountable Person:</label>
                            <input type="text" name="accountable_person[]">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date Acquired:</label>
                            <input type="date" name="date_acquired[]">
                        </div>

                        <div class="form-group">
                            <label>Fund Cluster:</label>
                            <input type="text" name="fund_cluster[]">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit Cost:</label>
                            <input type="number" name="unit_cost[]" step="0.01" min="0" class="unit-cost-input">
                        </div>

                        <div class="form-group">
                            <label>Total Cost:</label>
                            <input type="number" name="total_cost[]" step="0.01" min="0" class="total-cost-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Remarks/Notes:</label>
                        <textarea name="remarks_notes[]" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="addNewItem()">+ Add Another Item</button>
                <button type="submit" class="btn btn-primary">Submit All Items</button>
                
            </div>
        </form>
    </div>

    <script>
        let itemCount = 1;

        function addNewItem() {
            itemCount++;
            const container = document.getElementById('itemsContainer');
            const firstItem = document.querySelector('.item-group');
            const newItem = firstItem.cloneNode(true);
            
            // Update header
            newItem.querySelector('.item-header h3').textContent = 'Item #' + itemCount;
            newItem.querySelector('.btn-remove').style.display = 'inline-block';
            newItem.setAttribute('data-item-index', itemCount - 1);

            
            container.appendChild(newItem);
            
            // Setup event listeners for the new item
            setupItemCalculation(newItem);
            
            // Show remove buttons on all items except the first one
            updateRemoveButtons();
        }

        function removeItem(button) {
            const itemGroup = button.closest('.item-group');
            itemGroup.remove();
            itemCount--;
            
            // Renumber remaining items
            document.querySelectorAll('.item-group').forEach((item, index) => {
                item.querySelector('.item-header h3').textContent = 'Item #' + (index + 1);
            });
            
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const items = document.querySelectorAll('.item-group');
            items.forEach((item, index) => {
                const removeBtn = item.querySelector('.btn-remove');
                if (items.length > 1 && index > 0) {
                    removeBtn.style.display = 'inline-block';
                } else {
                    removeBtn.style.display = 'none';
                }
            });
        }

        function setupItemCalculation(itemGroup) {
            const unitCostInput = itemGroup.querySelector('.unit-cost-input');
            const quantityInput = itemGroup.querySelector('.quantity-input');
            const totalCostInput = itemGroup.querySelector('.total-cost-input');
            
            function calculateTotal() {
                const unitCost = parseFloat(unitCostInput.value) || 0;
                const quantity = parseFloat(quantityInput.value) || 0;
                const totalCost = unitCost * quantity;
                
                if (unitCost > 0 && quantity > 0) {
                    totalCostInput.value = totalCost.toFixed(2);
                }
            }
            
            unitCostInput.addEventListener('input', calculateTotal);
            quantityInput.addEventListener('input', calculateTotal);
        }

        // Setup calculation for the first item
        setupItemCalculation(document.querySelector('.item-group'));

        // Auto-hide success message after 5 seconds
        const messageDiv = document.querySelector('.message.success');
        if (messageDiv) {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>