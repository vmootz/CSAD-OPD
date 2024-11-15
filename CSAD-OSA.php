<?php
session_start();

$eventsFile = 'events.json';

// Load events from JSON file
function loadEvents($eventsFile) {
    if (file_exists($eventsFile)) {
        $data = file_get_contents($eventsFile);
        $events = json_decode($data, true);
        if ($events === null) {
            echo "Error: Could not decode JSON.";
        }
        return $events ?? [];
    } else {
        echo "Error: File $eventsFile not found.";
    }
    return [];
}

// Save events to JSON file
function saveEvents($eventsFile, $events) {
    file_put_contents($eventsFile, json_encode($events, JSON_PRETTY_PRINT));
}

$events = loadEvents($eventsFile);
$eventMessage = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username == "admin" && $password == "password") {
        $_SESSION['admin'] = true;
    } else {
        $loginError = "Invalid credentials.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    session_destroy();
    header("Location: CSAD-OSA.php");
    exit;
}

// Handle adding an event (Admin only)
if (isset($_SESSION['admin']) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addEvent'])) {
    $newEvent = [
        'eventId' => count($events) + 1,
        'eventName' => $_POST['eventName'],
        'organization' => $_POST['organization'],
        'activityType' => $_POST['activityType'],
        'eventDate' => $_POST['eventDate'],
        'venue' => $_POST['venue'],
        'status' => $_POST['status']
    ];
    $events[] = $newEvent;
    saveEvents($eventsFile, $events);
    $eventMessage = "New event added successfully!";
}

// Handle editing an event (Admin only)
if (isset($_SESSION['admin']) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editEvent'])) {
    $eventId = $_POST['eventId'];
    foreach ($events as $key => $event) {
        if ($event['eventId'] == $eventId) {
            $events[$key]['eventName'] = $_POST['eventName'];
            $events[$key]['organization'] = $_POST['organization'];
            $events[$key]['activityType'] = $_POST['activityType'];
            $events[$key]['eventDate'] = $_POST['eventDate'];
            $events[$key]['venue'] = $_POST['venue'];
            $events[$key]['status'] = $_POST['status'];
            break;
        }
    }
    saveEvents($eventsFile, $events);
    $eventMessage = "Event updated successfully!";
}

// Handle deleting an event (Admin only)
if (isset($_SESSION['admin']) && isset($_GET['delete'])) {
    $eventId = $_GET['delete'];
    $events = array_filter($events, function($event) use ($eventId) {
        return $event['eventId'] != $eventId;
    });
    $events = array_values($events);
    saveEvents($eventsFile, $events);
    header("Location: CSAD-OSA.php");
    exit;
}

// Handle editing an event form (Admin only)
if (isset($_GET['edit'])) {
    $eventId = $_GET['edit'];
    $editEvent = null;
    foreach ($events as $event) {
        if ($event['eventId'] == $eventId) {
            $editEvent = $event;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSAD - OSA Event Management System</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .navbar { background-color: #000; padding: 15px; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { margin: 0; color: #ffd700; } /* Mapúa Yellow */
        .login-form { display: flex; align-items: center; gap: 5px; }
        .login-form input { padding: 5px; }
        .login-form button { background-color: #ffd700; color: #000; border: none; padding: 5px 10px; cursor: pointer; }
        .login-form button:hover { background-color: #ffcc00; }
        h1 { color: #000; }
        h2, h3 { color: #d30000; } /* Mapúa Red */
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; color: #000; }
        input[type="text"], input[type="date"], input[type="password"], select { width: 100%; padding: 8px; box-sizing: border-box; margin-top: 5px; }
        button { background-color: #d30000; color: #fff; padding: 10px 15px; border: none; cursor: pointer; }
        button:hover { background-color: #a80000; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; }
        .table th { background-color: #d30000; color: #fff; }
        .success { color: green; margin-top: 10px; }
        .error { color: red; }
        .action-buttons { display: flex; gap: 5px; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>CSAD - OSA Event Management System</h1>
    <?php if (!isset($_SESSION['admin'])): ?>
        <form class="login-form" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    <?php else: ?>
        <a href="?logout" style="color: #fff; text-decoration: none;">Logout</a>
    <?php endif; ?>
</div>

<div class="container">

    <?php if (isset($_SESSION['admin'])): ?>
        <h2>Welcome, Admin</h2>
        <?php if (!empty($eventMessage)) echo "<p class='success'>$eventMessage</p>"; ?>

        <!-- Add New Event Form -->
        <h3>Add New Event</h3>
        <form method="POST">
            <input type="hidden" name="addEvent" value="1">
            <div class="form-group">
                <label>Event Name</label>
                <input type="text" name="eventName" required>
            </div>
            <div class="form-group">
                <label>Organization</label>
                <input type="text" name="organization" required>
            </div>
            <div class="form-group">
                <label>Activity Type</label>
                <select name="activityType" required>
                    <option value="Co-Curricular">Co-Curricular</option>
                    <option value="Extra-Curricular">Extra-Curricular</option>
                </select>
            </div>
            <div class="form-group">
                <label>Event Date</label>
                <input type="date" name="eventDate" required>
            </div>
            <div class="form-group">
                <label>Venue</label>
                <input type="text" name="venue" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="Accepted">Accepted</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Done">Done</option>
                </select>
            </div>
            <button type="submit">Add Event</button>
        </form>

        <!-- Edit Event Form -->
        <?php if (isset($editEvent)): ?>
            <h3>Edit Event: <?php echo htmlspecialchars($editEvent['eventName']); ?></h3>
            <form method="POST">
                <input type="hidden" name="eventId" value="<?php echo $editEvent['eventId']; ?>">
                <input type="hidden" name="editEvent" value="1">
                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="eventName" value="<?php echo htmlspecialchars($editEvent['eventName']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Organization</label>
                    <input type="text" name="organization" value="<?php echo htmlspecialchars($editEvent['organization']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Activity Type</label>
                    <select name="activityType" required>
                        <option value="Co-Curricular" <?php if ($editEvent['activityType'] == 'Co-Curricular') echo 'selected'; ?>>Co-Curricular</option>
                        <option value="Extra-Curricular" <?php if ($editEvent['activityType'] == 'Extra-Curricular') echo 'selected'; ?>>Extra-Curricular</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Event Date</label>
                    <input type="date" name="eventDate" value="<?php echo $editEvent['eventDate']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" name="venue" value="<?php echo htmlspecialchars($editEvent['venue']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Accepted" <?php if ($editEvent['status'] == 'Accepted') echo 'selected'; ?>>Accepted</option>
                        <option value="Cancelled" <?php if ($editEvent['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        <option value="Done" <?php if ($editEvent['status'] == 'Done') echo 'selected'; ?>>Done</option>
                    </select>
                </div>
                <button type="submit">Save Changes</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <h1>Event List</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Organization</th>
                <th>Activity Type</th>
                <th>Event Date</th>
                <th>Venue</th>
                <th>Status</th>
                <?php if (isset($_SESSION['admin'])): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['eventName']); ?></td>
                    <td><?php echo htmlspecialchars($event['organization']); ?></td>
                    <td><?php echo htmlspecialchars($event['activityType']); ?></td>
                    <td><?php echo htmlspecialchars($event['eventDate']); ?></td>
                    <td><?php echo htmlspecialchars($event['venue']); ?></td>
                    <td><?php echo htmlspecialchars($event['status']); ?></td>
                    <?php if (isset($_SESSION['admin'])): ?>
                        <td class="action-buttons">
                            <a href="?edit=<?php echo $event['eventId']; ?>">Edit</a>
                            <a href="?delete=<?php echo $event['eventId']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>
