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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Product
    if (isset($_POST['addProduct'])) {
        $name = $_POST['productName'];
        $price = $_POST['productPrice'];
        $category = $_POST['productCategory'];
        $imagePath = '';

        if (isset($_FILES['imagePath']) && $_FILES['imagePath']['error'] == 0) {
            $imageDir = 'images/';
            $imagePath = $imageDir . basename($_FILES['imagePath']['name']);
            move_uploaded_file($_FILES['imagePath']['tmp_name'], $imagePath);
        }

        $query = "INSERT INTO products (product_name, price, product_category, image_path) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("sdss", $name, $price, $category, $imagePath);
            $stmt->execute();
            $productId = $stmt->insert_id;

            if (!empty($_POST['toppings'])) {
                $query = "INSERT INTO product_toppings (product_id, topping_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                foreach ($_POST['toppings'] as $toppingId) {
                    $stmt->bind_param("ii", $productId, $toppingId);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }
    }

    // Update Product
    if (isset($_POST['updateProduct'])) {
        $id = $_POST['productId'];
        $name = $_POST['productName'];
        $price = $_POST['productPrice'];
        $category = $_POST['productCategory'];
        $imagePath = $_POST['existingImage'];

        if (isset($_FILES['imagePath']) && $_FILES['imagePath']['error'] == 0) {
            $imageDir = 'images/';
            $imagePath = $imageDir . basename($_FILES['imagePath']['name']);
            move_uploaded_file($_FILES['imagePath']['tmp_name'], $imagePath);
        }

        $query = "UPDATE products SET product_name=?, price=?, product_category=?, image_path=? WHERE product_id=?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("sdssi", $name, $price, $category, $imagePath, $id);
            $stmt->execute();
            $stmt->close();

            $conn->query("DELETE FROM product_toppings WHERE product_id = $id");
            if (!empty($_POST['toppings'])) {
                $query = "INSERT INTO product_toppings (product_id, topping_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                foreach ($_POST['toppings'] as $toppingId) {
                    $stmt->bind_param("ii", $id, $toppingId);
                    $stmt->execute();
                }
            }
        }
    }

    // Delete Product
    if (isset($_POST['deleteProduct'])) {
        $id = $_POST['productId'];
        $query = "DELETE FROM products WHERE product_id=?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$products = $conn->query("SELECT * FROM products");
$categories = ['cones', 'Sundaes', 'Cakes', 'Shakes'];
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

        #add-product-form {
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
        <a href="addToppings.php">Toppings</a>
        <button onclick="toggleAddProductForm()">Add Product</button>
        
        <!-- Add Product Form -->
        <?php $toppings = $conn->query("SELECT * FROM toppings"); ?>
        <form id="add-product-form" method="post" enctype="multipart/form-data">
            <label>Product Name: <input type="text" name="productName" required></label>
            <label>Product Price: <input type="number" step="0.01" name="productPrice" required></label>
            <label>Category:
                <select name="productCategory" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Upload Image: <input type="file" name="imagePath" accept="image/*" required></label>

            <!-- Multi-select for Toppings -->
            <label>Select Toppings:</label>
            <select name="toppings[]" multiple>
                <?php while ($topping = $toppings->fetch_assoc()): ?>
                    <option value="<?= $topping['topping_id'] ?>"><?= htmlspecialchars($topping['topping_name']) ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit" name="addProduct">Add Product</button>
        </form>

        <h3>Products List</h3>
        <ul class="product-list">
            <?php while ($product = $products->fetch_assoc()): ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars($product['product_name']) ?></strong> - 
                        $<?= htmlspecialchars($product['price']) ?> - 
                        <?= htmlspecialchars($product['product_category']) ?>
                        <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" width="50">
                    </div>
                    <button onclick="toggleEditForm(<?= $product['product_id'] ?>)">Edit</button>
                    
                    <div id="edit-form-<?= $product['product_id'] ?>" class="edit-form">
                    <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="productId" value="<?= $product['product_id'] ?>">
                            <label>Product Name: <input type="text" name="productName" value="<?= htmlspecialchars($product['product_name']) ?>" required></label>
                            <label>Product Price: <input type="number" step="0.01" name="productPrice" value="<?= htmlspecialchars($product['price']) ?>" required></label>
                            <label>Category:
                                <select name="productCategory" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>" <?= $category == $product['product_category'] ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Current Image: <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" width="50"></label>
                            <label>Upload New Image: <input type="file" name="imagePath" accept="image/*"></label>
                            <input type="hidden" name="existingImage" value="<?= $product['image_path'] ?>">

                            <!-- Fetch associated toppings for this product -->
                            <?php 
                                $toppings = $conn->query("SELECT * FROM toppings");
                                $productToppings = $conn->query("SELECT topping_id FROM product_toppings WHERE product_id = " . $product['product_id']);
                                $selectedToppings = array_column($productToppings->fetch_all(MYSQLI_ASSOC), 'topping_id');
                            ?>

                            <label>Select Toppings:</label>
                            <select name="toppings[]" multiple>
                                <?php while ($topping = $toppings->fetch_assoc()): ?>
                                    <option value="<?= $topping['topping_id'] ?>" 
                                            <?= in_array($topping['topping_id'], $selectedToppings) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($topping['topping_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>


                            <button type="submit" name="updateProduct">Update</button>
                            <button type="submit" name="deleteProduct" class="delete-button" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

    <script>
        function toggleAddProductForm() {
            const form = document.getElementById('add-product-form');
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }

        function toggleEditForm(productId) {
            const form = document.getElementById('edit-form-' + productId);
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }
    </script>
</body>
</html>
