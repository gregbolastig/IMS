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

            // Prepare data array for each RIS item
            $data = [
                'ris_no' => trim($_POST['ris_no'][$i]),
                'office' => trim($_POST['office'][$i]),
                'stock_no' => trim($_POST['stock_no'][$i]),
                'responsibility_center_code' => trim($_POST['responsibility_center_code'][$i]),
                'unit_of_measurement' => trim($_POST['unit_of_measurement'][$i]),
                'item_name' => trim($_POST['item_name'][$i]),
                'item_description' => trim($_POST['item_description'][$i]),
                'quantity' => floatval($_POST['quantity'][$i]),
                'remarks' => trim($_POST['remarks'][$i]),
                'purpose' => trim($_POST['purpose'][$i])
            ];

            // Remove empty values
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });

            // Check if RIS number is provided
            if (empty($data['ris_no'])) {
                $errors[] = "RIS Number is required for item: " . $_POST['item_name'][$i];
                continue;
            }

            // Check if RIS number already exists
            if ($db->exists('ris', "ris_no = '" . $db->escape($data['ris_no']) . "'")) {
                $errors[] = "RIS Number already exists: " . $data['ris_no'];
                continue;
            }

            // Insert into RIS table
            $insertId = $db->insert('ris', $data);

            if ($insertId) {
                $successCount++;
            } else {
                $errors[] = "Failed to add RIS item: " . $_POST['item_name'][$i];
            }
        }

        if (empty($errors)) {
            $db->commit();
            $message = $successCount . ' RIS item(s) added successfully!';
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
    $message = $count . ' RIS item(s) added successfully!';
    $messageType = 'success';
    $inventoryItems = []; // Clear items after success
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add RIS (Requisition and Issue Slip)</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Add RIS (Requisition and Issue Slip)</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($inventoryItems)): ?>
            <form method="POST" action="" id="risForm">

                <div id="itemsContainer">
                    <?php foreach ($inventoryItems as $index => $item): ?>
                        <div class="item-group" data-item-index="<?php echo $index; ?>">
                            <div class="item-header">
                                <h3>RIS Item #<?php echo $index + 1; ?></h3>
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                <?php endif; ?>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>RIS Number: <span class="required">*</span></label>
                                    <input type="text" name="ris_no[]" required 
                                           placeholder="e.g., RIS-2025-001">
                                </div>

                                <div class="form-group">
                                    <label>Office:</label>
                                    <input type="text" name="office[]" 
                                           placeholder="e.g., Office of the Mayor">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Stock Number:</label>
                                    <input type="text" name="stock_no[]" 
                                           placeholder="e.g., STK-001">
                                </div>

                                <div class="form-group">
                                    <label>Responsibility Center Code:</label>
                                    <input type="text" name="responsibility_center_code[]" 
                                           placeholder="e.g., RCC-001">
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
                                           value="<?php echo htmlspecialchars($item['quantity']); ?>">
                                </div>

                                <div class="form-group">
                                    <label>Unit of Measurement: <span class="required">*</span></label>
                                    <input type="text" name="unit_of_measurement[]" required
                                           value="<?php echo htmlspecialchars($item['unit_of_measurement']); ?>"
                                           placeholder="pc(s)/box(s)/roll(s)/rem(s)/pack(s)/ml/etc.">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Remarks:</label>
                                <textarea name="remarks[]" rows="3"><?php echo htmlspecialchars($item['remarks_notes'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Purpose:</label>
                                <textarea name="purpose[]" rows="3" 
                                          placeholder="e.g., For office use"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="addNewItem()">+ Add Another RIS Item</button>
                    <button type="submit" class="btn btn-primary">Submit All RIS Items</button>
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
            newItem.querySelector('.item-header h3').textContent = 'RIS Item #' + itemCount;
            newItem.querySelector('.btn-remove').style.display = 'inline-block';
            newItem.setAttribute('data-item-index', itemCount - 1);
            
            // Clear all input values
            newItem.querySelectorAll('input, textarea').forEach(input => {
                input.value = '';
            });
            
            container.appendChild(newItem);
            updateRemoveButtons();
        }

        function removeItem(button) {
            const itemGroup = button.closest('.item-group');
            itemGroup.remove();
            itemCount--;
            
            // Renumber remaining items
            document.querySelectorAll('.item-group').forEach((item, index) => {
                item.querySelector('.item-header h3').textContent = 'RIS Item #' + (index + 1);
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