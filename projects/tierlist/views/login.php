<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$error = null;

function runApp(){
    global $error;

    $username = trim($_POST['username'] ?? ''); //use an empty string if not set
    if(!isValidUsername($username)) {
        // errors are set in isValidUsername
        return;
    }

    //create user directory after validating username
    $home_directory = 'public/users/' . $username;
    if (!is_dir($home_directory)) {
        if (!mkdir($home_directory, 0777, true)) {

            $error = "Failed to create user directory. Please try again.";
            return;
        }
    }

    // initialize the database connection
    $pdo = getPdoObject(); //function imported from dbConnector.php
    if (!$pdo) {
        $error = "Database connection error.";
        return;
    }

    // if username already exists, set session and redirect to dashboard
    $userObject = getUser($username);
    if ($userObject) {
        echo "User already exists. Logging in.";
        session_regenerate_id(true);
        setSessionUserData(
            $userObject['username'],
            $userObject['home_directory'],
            $userObject['user_id']
        );
        header('Location: ' . SELF_URL . '?page=dashboard');
        exit;
    }

    // otherwise, register new user
    $stmt = $pdo->prepare("INSERT INTO users (username, home_directory) VALUES (?, ?)");
    try {
        $res = $stmt->execute([$username, $home_directory]);
        if (!$res) {
            $error = "Registration failed. Database rejected.";
            return;
        }

        session_regenerate_id(true);
        setSessionUserData($username, $home_directory, $pdo->lastInsertId());
        header('Location: ' . SELF_URL . '?page=dashboard');
        exit;

    } catch (\Throwable $th) {
        $error = "Registration failed. Server error.";
        error_log($th, 3, APP_ROOT . '/logs/php_errors.log');
    }
}

function setSessionUserData($username, $home_directory, $user_id) {
    $_SESSION['userdata'] = [
        'username' => $username,
        'home_directory' => $home_directory,
        'user_id' => $user_id
    ];
}

function getUser($username) {
    $pdo = getPdoObject();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isValidUsername($username) {
    global $error;
    // Validate username: only allow letters, numbers, underscore, and hyphen
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $error = "Username can only contain letters, numbers, underscores, and hyphens.";
        return false;
    }

    if (strlen($username) < 3 || strlen($username) > 30) {
        $error = "Username must be between 3 and 30 characters.";
        return false;
    }

    if ($username[0] === '-' || $username[0] === '_') {
        $error = "Username cannot start with a hyphen or underscore.";
        return false;
    }

    return true;
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    runApp();

}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        #listModal {
            display: flex;
            background-color: #0f172a
        }
    </style>
</head>
<body>


    <div id="listModal" class="form-modal">
        <div class="form-modal-content">
            <!-- <button class="list-btn left" id="closeModalBtn">
                cancel
            </button> -->
            <div style="width: 100%;  display: block; padding: 20px;">
                <h3>Enter a username to organize your lists</h3>
                <p>Choose a unique username or enter an existing one.</p>
                <p>Your username can only contain letters, numbers, underscores, and hyphens.</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= SELF_URL ?>?page=login">
                <label for="username">Username</label>
                <br>
                <input type="text" id="username" name="username" required>

                <!-- <input type="file" id="filesInput" name="filesInput" accept="image/*,video/*" multiple> -->

                <br>
                <button type="submit" class="large-button" value="Save">
                    Continue
                </button>


            </form>
        </div>
    </div>

    <script>

    </script>
</body>
</html>
