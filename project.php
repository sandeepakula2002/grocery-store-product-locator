<?php
// Database configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'sandeep@123';
$dbName = 'shopping';

// Establish database connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check for connection errors
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
// Check if form is submitted for removing an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_submit'])) {
    $removeItemId = $_POST['remove_item'];

    // Call the function to remove the item by its ID
    removeItemBySerialNum($conn, $removeItemId);
}

// Function to remove an item by its serial number
function removeItemBySerialNum($conn, $serialNum) {
    $serialNum = $conn->real_escape_string($serialNum);
    $sql = "DELETE FROM details WHERE serial_num = '$serialNum'";

    if ($conn->query($sql) === TRUE) {
        echo "Item removed successfully";
    } else {
        echo "Error removing item: " . $conn->error;
    }
}




// Check if form is submitted for updating items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if form is submitted for updating items
    if (isset($_POST['update_item_submit'])) {
        $updateSerialNum = $_POST['update_serial_num'];
        $updateItem = $_POST['update_item'];
        $updateStorageLocation = $_POST['update_storage_location'];

        updateItemById($conn, $updateSerialNum, $updateItem, $updateStorageLocation);
    }
    // Check if form is submitted for inserting a new item
    elseif (isset($_POST['new_item_submit'])) {
        $newSerialNum = $_POST['new_serial_num'];
        $newItem = $_POST['new_item'];
        $newStorageLocation = $_POST['new_storage_location'];

        insertNewItem($conn, $newSerialNum, $newItem, $newStorageLocation);
    }
}

// Retrieve item data from the database table
$sql = 'SELECT serial_num, GROUP_CONCAT(Item) AS Item, GROUP_CONCAT(Storage_Location) AS Storage_Location FROM details GROUP BY serial_num';

$result = $conn->query($sql);

// Check if retrieval was successful
if (!$result) {
    die('Failed to retrieve item data from the database: ' . $conn->error);
}

// Fetch the items into an array
$items = array();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Check if form is submitted for finding common and individual locations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_locations_submit'])) {
    // Retrieve selected item IDs from the form
    $selectedItems = $_POST['items'] ?? array();

    // Find the common storage locations
    $commonLocations = getLocationsByItems($conn, $selectedItems);

    // Combine items and update storage locations
    $combinedItems = array();
    foreach ($selectedItems as $itemId) {
        $item = getItemById($conn, $itemId);
        if ($item) {
            $combinedItems[$item['Item']][] = $item['Storage_Location'];
        }
    }

    $combinedLocations = array();
    foreach ($combinedItems as $item => $locations) {
        $combinedLocations[] = implode(',', $locations);
    }

    $commonLocations = array_intersect($commonLocations, $combinedLocations);
}

// Function to retrieve an item by its ID
function getItemById($conn, $itemId) {
    $itemId = $conn->real_escape_string($itemId);
    $sql = "SELECT serial_num, GROUP_CONCAT(Item) AS Item, GROUP_CONCAT(Storage_Location) AS Storage_Location FROM details WHERE serial_num = '$itemId' GROUP BY serial_num";
    $result = $conn->query($sql);

    if (!$result) {
        die('Failed to retrieve item data from the database: ' . $conn->error);
    }

    return $result->fetch_assoc();
}

// Function to update an item by its ID
function updateItemById($conn, $itemId, $newItem, $newLocation) {
    $itemId = $conn->real_escape_string($itemId);
    $newItem = $conn->real_escape_string($newItem);
    $newLocation = $conn->real_escape_string($newLocation);

    $sql = "UPDATE details SET Item = '$newItem', Storage_Location = '$newLocation' WHERE serial_num = '$itemId'";

    if ($conn->query($sql) === TRUE) {
        echo "Item updated successfully";
    } else {
        echo "Error updating item: " . $conn->error;
    }
}

// Function to insert a new item
function insertNewItem($conn, $serialNum, $item, $storageLocation) {
    $serialNum = $conn->real_escape_string($serialNum);
    $item = $conn->real_escape_string($item);
    $storageLocation = $conn->real_escape_string($storageLocation);

    $sql = "INSERT INTO details (serial_num, Item, Storage_Location) VALUES ('$serialNum', '$item', '$storageLocation')";

    if ($conn->query($sql) === TRUE) {
        echo "New item inserted successfully";
    } else {
        echo "Error inserting item: " . $conn->error;
    }
}

// Function to retrieve items by storage location
function getItemsByLocation($conn, $location) {
    $location = $conn->real_escape_string($location);
    $sql = "SELECT serial_num, Item, Storage_Location FROM details WHERE Storage_Location = '$location'";
    $result = $conn->query($sql);

    if (!$result) {
        die('Failed to retrieve item data from the database: ' . $conn->error);
    }

    $items = array();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
}

// Function to retrieve storage locations by selected item IDs
function getLocationsByItems($conn, $selectedItems) {
    $locations = array();
    foreach ($selectedItems as $itemId) {
        $item = getItemById($conn, $itemId);
        if ($item) {
            $itemLocations = explode(',', $item['Storage_Location']);
            $locations[] = $itemLocations;
        }
    }
    if (!empty($locations)) {
        $commonLocations = call_user_func_array('array_intersect', $locations);
        return $commonLocations;
    }
    return array();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Grocery Store Item Location Finder</title>
    <style>
        table {
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid black;
        }
    </style>
</head>
<body>
    <h1>Grocery Store Item Location Finder</h1>

    <?php if (isset($commonLocations) && !empty($commonLocations)): ?>
        <h2>Common Storage Locations:</h2>
        <table>
            <thead>
                <tr>
                    <th>Storage Location</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commonLocations as $location): ?>
                    <tr>
                        <td><?php echo $location; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($commonLocations) && empty($commonLocations)): ?>
        <p>No common storage locations found for the selected items.</p>
    <?php endif; ?>

    <?php if (isset($selectedItems) && !empty($selectedItems)): ?>
        <h2>Individual Storage Locations:</h2>
        <table>
            <thead>
                <tr>
                    <th>Serial Num</th>
                    <th>Item</th>
                    <th>Storage Location(s)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $combinedLocations = array(); // Initialize array for combined locations
                foreach ($selectedItems as $itemId): ?>
                    <?php $item = getItemById($conn, $itemId); ?>
                    <?php if ($item): ?>
                        <?php
                        // Combine the storage locations for the selected items
                        $itemLocations = explode(',', $item['Storage_Location']);
                        $combinedLocations = array_merge($combinedLocations, $itemLocations);
                        $combinedLocations = array_unique($combinedLocations); // Remove duplicates
                        ?>
                        <tr>
                            <td><?php echo $item['serial_num']; ?></td>
                            <td><?php echo $item['Item']; ?></td>
                            <td><?php echo implode(',', $itemLocations); ?></td> <!-- Display individual locations -->
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!empty($combinedLocations)): ?>
                    <tr>
                        <td colspan="3">
                            <strong>Combined Location:</strong> <?php echo implode(',', $combinedLocations); ?> <!-- Display combined location -->
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No items selected.</p>
    <?php endif; ?>

    <h2>Update Existing Item:</h2>
    <form method="post">
        <label for="update_serial_num">Serial Num:</label>
        <input type="text" name="update_serial_num" id="update_serial_num" required>
        <label for="update_item">Item:</label>
        <input type="text" name="update_item" id="update_item" required>
        <label for="update_storage_location">Storage Location:</label>
        <input type="text" name="update_storage_location" id="update_storage_location" required>
        <input type="submit" name="update_item_submit" value="Update Item">
    </form>

    <h2>Insert New Item:</h2>
    <form method="post">
        <label for="new_serial_num">Serial Num:</label>
        <input type="text" name="new_serial_num" id="new_serial_num" required>
        <label for="new_item">Item:</label>
        <input type="text" name="new_item" id="new_item" required>
        <label for="new_storage_location">Storage Location:</label>
        <input type="text" name="new_storage_location" id="new_storage_location" required>
        <input type="submit" name="new_item_submit" value="Insert Item">
    </form>
<h2>Remove Item:</h2>
<form method="post">
    <label for="remove_item">Select Serial Number to Remove:</label>
    <select name="remove_item" id="remove_item" required>
        <option value="">Select a serial number</option>
        <?php foreach ($items as $item): ?>
            <option value="<?php echo $item['serial_num']; ?>"><?php echo $item['serial_num']; ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" name="remove_item_submit" value="Remove Item">
</form>



    <form method="post">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Serial Num</th>
                    <th>Item</th>
                    <th>Storage Location(s)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="items[]" value="<?php echo $item['serial_num']; ?>">
                        </td>
                        <td><?php echo $item['serial_num']; ?></td>
                        <td><?php echo $item['Item']; ?></td>
                        <td><?php echo $item['Storage_Location']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <input type="submit" name="find_locations_submit" value="Find Common Locations">
    </form>

</body>
</html>
