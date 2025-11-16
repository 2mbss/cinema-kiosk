<?php
/**
 * Extras Management System
 * Add, edit, delete snacks and drinks
 */

require_once 'includes/auth.php';
requireAuth();

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO extras (name, description, price, category, image, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['category'],
                $_POST['image'],
                $_POST['status']
            ]);
            $message = 'Extra item added successfully!';
            
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("
                UPDATE extras 
                SET name = ?, description = ?, price = ?, category = ?, image = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['category'],
                $_POST['image'],
                $_POST['status'],
                $_POST['extra_id']
            ]);
            $message = 'Extra item updated successfully!';
            
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM extras WHERE id = ?");
            $stmt->execute([$_POST['extra_id']]);
            $message = 'Extra item deleted successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get extra for editing
$editExtra = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM extras WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editExtra = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error loading extra data';
    }
}

// Get all extras
try {
    $stmt = $pdo->query("SELECT * FROM extras ORDER BY category, name");
    $extras = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading extras';
    $extras = [];
}

// Get extras statistics
try {
    $stmt = $pdo->query("
        SELECT 
            e.category,
            COUNT(*) as total_items,
            AVG(e.price) as avg_price,
            COALESCE(SUM(se.quantity), 0) as total_sold
        FROM extras e
        LEFT JOIN sales_extras se ON e.id = se.extra_id
        WHERE e.status = 'active'
        GROUP BY e.category
    ");
    $stats = $stmt->fetchAll();
} catch (PDOException $e) {
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extras - Cinema Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>🍿 Extras Management</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <?php foreach ($stats as $stat): ?>
                    <div class="card">
                        <h3><?php echo ucfirst($stat['category']); ?>s</h3>
                        <div class="number"><?php echo $stat['total_items']; ?></div>
                        <div class="label">
                            Avg: ₱<?php echo number_format($stat['avg_price'], 2); ?> | 
                            Sold: <?php echo $stat['total_sold']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="card">
                    <h3>Total Revenue</h3>
                    <div class="number">
                        ₱<?php 
                        try {
                            $stmt = $pdo->query("
                                SELECT COALESCE(SUM(e.price * se.quantity), 0) as total_revenue
                                FROM extras e
                                JOIN sales_extras se ON e.id = se.extra_id
                            ");
                            echo number_format($stmt->fetch()['total_revenue'], 2);
                        } catch (PDOException $e) {
                            echo "0.00";
                        }
                        ?>
                    </div>
                    <div class="label">From Extras</div>
                </div>
            </div>
            
            <!-- Add/Edit Extra Form -->
            <div class="form-container">
                <h3><?php echo $editExtra ? 'Edit Extra Item' : 'Add New Extra Item'; ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $editExtra ? 'edit' : 'add'; ?>">
                    <?php if ($editExtra): ?>
                        <input type="hidden" name="extra_id" value="<?php echo $editExtra['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="name">Item Name:</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($editExtra['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₱):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo htmlspecialchars($editExtra['price'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($editExtra['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="snack" <?php echo ($editExtra['category'] ?? '') === 'snack' ? 'selected' : ''; ?>>Snack</option>
                                <option value="drink" <?php echo ($editExtra['category'] ?? '') === 'drink' ? 'selected' : ''; ?>>Drink</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image Filename:</label>
                            <input type="text" id="image" name="image" 
                                   value="<?php echo htmlspecialchars($editExtra['image'] ?? ''); ?>"
                                   placeholder="item.jpg">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo ($editExtra['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editExtra['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editExtra ? 'Update Item' : 'Add Item'; ?>
                    </button>
                    
                    <?php if ($editExtra): ?>
                        <a href="extras.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Extras List -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Snacks -->
                <div class="table-container">
                    <h3 style="padding: 20px; margin: 0; background: #e67e22; color: white;">🍿 Snacks</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $snacks = array_filter($extras, function($extra) { return $extra['category'] === 'snack'; });
                            foreach ($snacks as $extra): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($extra['name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars(substr($extra['description'], 0, 40)) . '...'; ?></small>
                                    </td>
                                    <td>₱<?php echo number_format($extra['price'], 2); ?></td>
                                    <td>
                                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; 
                                                     background: <?php echo $extra['status'] === 'active' ? '#27ae60' : '#e74c3c'; ?>; 
                                                     color: white;">
                                            <?php echo ucfirst($extra['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $extra['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="extra_id" value="<?php echo $extra['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($snacks)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                                        No snacks found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Drinks -->
                <div class="table-container">
                    <h3 style="padding: 20px; margin: 0; background: #3498db; color: white;">🥤 Drinks</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $drinks = array_filter($extras, function($extra) { return $extra['category'] === 'drink'; });
                            foreach ($drinks as $extra): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($extra['name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars(substr($extra['description'], 0, 40)) . '...'; ?></small>
                                    </td>
                                    <td>₱<?php echo number_format($extra['price'], 2); ?></td>
                                    <td>
                                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; 
                                                     background: <?php echo $extra['status'] === 'active' ? '#27ae60' : '#e74c3c'; ?>; 
                                                     color: white;">
                                            <?php echo ucfirst($extra['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $extra['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="extra_id" value="<?php echo $extra['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($drinks)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                                        No drinks found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>