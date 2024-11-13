<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = 'localhost';
$username = 'root';
$password = 'root';
$dbname = 'ice_shop';

function getDatabaseConnection() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

$conn = getDatabaseConnection();

// Handle topping actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['addTopping'])) {
        $toppingName = $_POST['toppingName'];
        $toppingPrice = $_POST['toppingPrice'];
        
        $query = "INSERT INTO toppings (topping_name, price) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sd", $toppingName, $toppingPrice);
        $stmt->execute();
    }
    
    if (isset($_POST['updateTopping'])) {
        $toppingId = $_POST['toppingId'];
        $toppingName = $_POST['toppingName'];
        $toppingPrice = $_POST['toppingPrice'];
        
        $query = "UPDATE toppings SET topping_name=?, price=? WHERE topping_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sdi", $toppingName, $toppingPrice, $toppingId);
        $stmt->execute();
    }

    if (isset($_POST['deleteTopping'])) {
        $toppingId = $_POST['toppingId'];
        
        $query = "DELETE FROM toppings WHERE topping_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $toppingId);
        $stmt->execute();
    }
}

// Fetch toppings
$toppings = $conn->query("SELECT * FROM toppings");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }

        h2, h3 {
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #add-topping-form {
            display: none; /* Initially hidden */
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f8ff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        form {
            margin-bottom: 20px;
        }

        form label {
            display: block;
            margin: 10px 0 5px;
            color: #555;
        }

        form input[type="text"],
        form input[type="number"],
        form select,
        form input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }

        button:hover {
            background-color: #218838;
        }

        .product-list {
            list-style: none;
            padding: 0;
        }

        .product-list li {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-list img {
            max-width: 50px;
            border-radius: 4px;
            margin-left: 20px;
        }

        .edit-form {
            display: none; /* Hidden initially */
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .edit-form label {
            font-size: 14px;
        }

        .edit-form button {
            background-color: #007bff;
        }

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Panel - Manage Products</h2>
        
        <a href="admin.php">Products</a>

        <!-- Add Topping Button and Form -->
        <button onclick="toggleAddToppingForm()">Add Topping</button>

        <div id="add-topping-form" style="display: none;">
            <h3>Add Topping</h3>
            <form method="post">
                <label>Topping Name: <input type="text" name="toppingName" required></label>
                <label>Topping Price: <input type="number" step="0.01" name="toppingPrice" required></label>
                <button type="submit" name="addTopping">Add Topping</button>
            </form>
        </div>

        <h3>Toppings List</h3>
        <ul class="product-list">
            <?php while ($topping = $toppings->fetch_assoc()): ?>
                <li>
                    <strong><?= htmlspecialchars($topping['topping_name']) ?></strong> - 
                    $<?= htmlspecialchars($topping['price']) ?>
                    <button onclick="toggleEditToppingForm(<?= $topping['topping_id'] ?>)">Edit</button>
                    
                    <div id="edit-topping-form-<?= $topping['topping_id'] ?>" class="edit-form" style="display: none;">
                        <form method="post">
                            <input type="hidden" name="toppingId" value="<?= $topping['topping_id'] ?>">
                            <label>Topping Name: <input type="text" name="toppingName" value="<?= htmlspecialchars($topping['topping_name']) ?>" required></label>
                            <label>Topping Price: <input type="number" step="0.01" name="toppingPrice" value="<?= htmlspecialchars($topping['price']) ?>" required></label>
                            <button type="submit" name="updateTopping">Update</button>
                            <button type="submit" name="deleteTopping" class="delete-button" onclick="return confirm('Are you sure you want to delete this topping?')">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>

        <script>
            function toggleAddToppingForm() {
                const form = document.getElementById('add-topping-form');
                form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
            }

            function toggleEditToppingForm(toppingId) {
                const form = document.getElementById('edit-topping-form-' + toppingId);
                form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
            }
        </script>
</body>
</html>
