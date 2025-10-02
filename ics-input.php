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
        $itemCount = count($_POST['item_name']);
        $successCount = 0;
        $errors = [];

        // Begin transaction for multiple inserts
        $db->beginTransaction();

        for ($i = 0; $i < $itemCount; $i++) {
            // Skip empty items
            if (empty(trim($_POST['item_name'][$i]))) {
                continue;
            }

            // Prepare data array for each ICS item
            $data = [
                'ics_no' => trim($_POST['ics_no'][$i]),
                'fund_cluster' => trim($_POST['fund_cluster'][$i]),
                'quantity' => floatval($_POST['quantity'][$i]),
                'unit_of_measurement' => trim($_POST['unit_of_measurement'][$i]),
                'unit_cost' => !empty($_POST['unit_cost'][$i]) ? floatval($_POST['unit_cost'][$i]) : null,
                'total_cost' => !empty($_POST['total_cost'][$i]) ? floatval($_POST['total_cost'][$i]) : null,
                'item_name' => trim($_POST['item_name'][$i]),
                'item_description' => trim($_POST['item_description'][$i]),
                'inventory_item_no' => trim($_POST['inventory_item_no'][$i]),
                'estimated_useful_life' => trim($_POST['estimated_useful_life'][$i]),
                'date_acquired' => !empty($_POST['date_acquired'][$i]) ? $_POST['date_acquired'][$i] : null
            ];

            // Remove empty values
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });

            // Check if ICS number is provided
            if (empty($data['ics_no'])) {
                $errors[] = "ICS Number is required for item: " . $_POST['item_name'][$i];
                continue;
            }

            // Check if ICS number already exists
            if ($db->exists('ics', "ics_no = '" . $db->escape($data['ics_no']) . "'")) {
                $errors[] = "ICS Number already exists: " . $data['ics_no'];
                continue;
            }

            // Insert into ICS table
            $insertId = $db->insert('ics', $data);

            if ($insertId) {
                $successCount++;
            } else {
                $errors[] = "Failed to add ICS item: " . $_POST['item_name'][$i];
            }
        }

        if (empty($errors)) {
            $db->commit();
            $message = $successCount . ' ICS item(s) added successfully!';
            $messageType = 'success';
            
            // Clear the form by redirecting
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . $successCount);
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

// Check for success message from redirect
if (isset($_GET['success'])) {
    $count = intval($_GET['success']);
    $message = $count . ' ICS item(s) added successfully!';
    $messageType = 'success';
    $inventoryItems = []; // Clear items after success
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add ICS (Inventory Custodian Slip)</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Add ICS (Inventory Custodian Slip)</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($inventoryItems)): ?>
            <form method="POST" action="" id="icsForm">

                <div id="itemsContainer">
                    <?php foreach ($inventoryItems as $index => $item): ?>
                        <div class="item-group" data-item-index="<?php echo $index; ?>">
                            <div class="item-header">
                                <h3>ICS Item #<?php echo $index + 1; ?></h3>
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                <?php endif; ?>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>ICS Number: <span class="required">*</span></label>
                                    <input type="text" name="ics_no[]" required 
                                           placeholder="e.g., ICS-2025-001">
                                </div>

                                <div class="form-group">
                                    <label>Fund Cluster:</label>
                                    <input type="text" name="fund_cluster[]" 
                                           value="<?php echo htmlspecialchars($item['fund_cluster'] ?? ''); ?>"
                                           placeholder="e.g., 01">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Item Name: <span class="required">*</span></label>
                                <input type="text" name="item_name[]" required 
                                       value="<?php echo htmlspecialchars($item['item_name']); ?>">
                            </div>

                            <div class="form-group">
                                <label>Item Description:</label>
                                <textarea name="item_description[]" rows="3"><?php echo htmlspecialchars($item['item_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Quantity: <span class="required">*</span></label>
                                    <input type="number" name="quantity[]" step="0.01" min="0" required 
                                           class="quantity-input"
                                           value="<?php echo htmlspecialchars($item['quantity']); ?>">
                                </div>

                                <div class="form-group">
                                    <label>Unit of Measurement: <span class="required">*</span></label>
                                    <input type="text" name="unit_of_measurement[]" required
                                           value="<?php echo htmlspecialchars($item['unit_of_measurement']); ?>"
                                           placeholder="pc(s)/box(s)/roll(s)/rem(s)/pack(s)/ml/etc.">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Unit Cost:</label>
                                    <input type="number" name="unit_cost[]" step="0.01" min="0" 
                                           class="unit-cost-input"
                                           value="<?php echo htmlspecialchars($item['unit_cost'] ?? ''); ?>"
                                           placeholder="0.00">
                                </div>

                                <div class="form-group">
                                    <label>Total Cost:</label>
                                    <input type="number" name="total_cost[]" step="0.01" min="0" 
                                           class="total-cost-input"
                                           value="<?php echo htmlspecialchars($item['total_cost'] ?? ''); ?>"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Inventory Item Number:</label>
                                    <input type="text" name="inventory_item_no[]" 
                                           value="<?php echo htmlspecialchars($item['property_number'] ?? ''); ?>"
                                           placeholder="e.g., INV-001">
                                </div>

                                <div class="form-group">
                                    <label>Estimated Useful Life:</label>
                                    <input type="text" name="estimated_useful_life[]" 
                                           placeholder="e.g., 5 years">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Date Acquired:</label>
                                <input type="date" name="date_acquired[]" 
                                       value="<?php echo htmlspecialchars($item['date_acquired'] ?? ''); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="addNewItem()">+ Add Another ICS Item</button>
                    <button type="submit" class="btn btn-primary">Submit All ICS Items</button>
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
        let itemCount = <?php echo count($inventoryItems); ?>;

        function addNewItem() {
            itemCount++;
            const container = document.getElementById('itemsContainer');
            const firstItem = document.querySelector('.item-group');
            const newItem = firstItem.cloneNode(true);
            
            // Update header
            newItem.querySelector('.item-header h3').textContent = 'ICS Item #' + itemCount;
            newItem.querySelector('.btn-remove').style.display = 'inline-block';
            newItem.setAttribute('data-item-index', itemCount - 1);
            
            // Clear all input values
            newItem.querySelectorAll('input, textarea').forEach(input => {
                input.value = '';
            });
            
            container.appendChild(newItem);
            
            // Setup event listeners for the new item
            setupItemCalculation(newItem);
            updateRemoveButtons();
        }

        function removeItem(button) {
            const itemGroup = button.closest('.item-group');
            itemGroup.remove();
            itemCount--;
            
            // Renumber remaining items
            document.querySelectorAll('.item-group').forEach((item, index) => {
                item.querySelector('.item-header h3').textContent = 'ICS Item #' + (index + 1);
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

        // Setup calculation for all existing items
        document.querySelectorAll('.item-group').forEach(itemGroup => {
            setupItemCalculation(itemGroup);
        });

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