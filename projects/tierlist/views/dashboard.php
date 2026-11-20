<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$error = null;

if (!isset($_SESSION['userdata'])) {
    echo 'no userdata in session';
    header('Location: ' . SELF_URL . '?page=login');
    exit;
}

// $userdata should have 'username', 'home_directory', 'user_id' keys
$userdata = $_SESSION['userdata'];

function getListsForUser(){
    global $userdata, $error, $lists;

    $pdo = getPdoObject(); //function imported from dbConnector.php
    if (!$pdo) {
        $error = "Database connection error.";
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM lists WHERE user_id = ?");
    try {
        $res = $stmt->execute([$userdata['user_id']]);
        if (!$res) {
            $error = "Database query failed.";
            return;
        }
        $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (\Throwable $th) {
        $error = "There was a problem contacting the database.";
        error_log($th, 3, APP_ROOT . '/logs/php_errors.log');
    }
}

getListsForUser();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container setup-container">

        <?php if (isset($userdata['username'])): ?>
            <div class="user-info-bar">
                <div></div>
                <button class="list-btn right" onclick="location.href='<?= SELF_URL ?>?action=logout'">
                    Logout <?php echo htmlspecialchars($userdata['username']); ?>
                </button>

            </div>
        <?php endif; ?>

        <h1>Tier List Maker</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="setup-actions">
            <button class="large-button">New List</button>
        </div>

        <h2 class="your-lists-title">Your Lists</h2>

        <div class="lists-panel">
            <!-- <div class="list-row">
                <span class="list-name">Cool Movies</span>
                <div class="list-actions">
                    <button class="list-btn">edit</button>
                    <button class="list-btn danger">delete</button>
                </div>
            </div> -->

            <?php if (isset($lists)): ?>

                <?php if (count($lists) === 0): ?>
                    <p>You have no tier lists yet. Create one to get started!</p>
                <?php endif; ?>

                <?php foreach ($lists as $list): ?>
                    <div class="list-row">
                        <span class="list-name"><?php echo htmlspecialchars($list['list_name']); ?></span>
                        <div class="list-actions">
                            <button class="list-btn"
                                onclick="location.href='<?= SELF_URL ?>?page=listEditor&list_id=<?php echo $list['list_id']; ?>'"
                            >
                                edit
                            </button>
                            <button
                                class="list-btn danger"
                                listId="<?= $list['list_id'] ?>"
                                listName="<?= htmlspecialchars($list['list_name']) ?>"
                                onclick="deleteList(this)"
                            >
                                delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="listModal" class="form-modal">
        <div class="form-modal-content">
            <button class="list-btn left" id="closeModalBtn">
                cancel
            </button>
            <h2>Create New Tier List</h2>
            <form id="createListForm" enctype="multipart/form-data">
                <label for="listName">List Name</label><br>
                <input type="text" id="listName" name="listName" required>
                <input type="file" id="filesInput" name="filesInput[]" accept="image/*,video/*" multiple><br>
                <button type="submit" class="large-button">Save</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('createListForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            try {
                const response = await fetch('<?= SELF_URL ?>?action=createList', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();
                console.log(result)
                if(result.success){
                    let totalNumFiles = result.uploaded.length - result.skipped.length;

                    let message =
                        `Successfully added list '${formData.get('listName')}'\n` +
                        `Uploaded ${result.uploaded.length} out of ${totalNumFiles} files.`;

                    alert(message);
                    window.location.reload();
                }

            } catch (err) {
                console.error('Create list failed:', err);
            }
        });

        function showModal(){
            document.getElementById("listModal").style.display = "flex";
        }

        function closeModal(){
            document.getElementById("listModal").style.display = "none";
        }


        async function deleteList(button) {
            const listId = button.getAttribute('listId');
            const listName = button.getAttribute('listName');

            if (!confirm(`Are you sure you want to delete '${listName}'?`)) {
                return;
            }

            try {
                const response = await fetch('<?= SELF_URL ?>?action=deleteList', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ list_id: listId })
                });

                const result = await response.json();
                // const text = await response.text();
                // console.log(text);

                if (result.success) {
                    // Remove the row from page without reload
                    // button.closest('.list-row').remove();
                    // alert("Tier list deleted successfully");
                    location.reload();
                } else {
                    alert("Failed to delete: " + (result.error || "Unknown error"));
                }
            } catch (err) {
                console.error(err);
                alert("Something went wrong while deleting the list");
            }
        }


        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('.large-button').addEventListener('click', showModal);
            document.getElementById('closeModalBtn').addEventListener('click', closeModal);
            document.addEventListener('keydown', e => {
                const modalOpen = document.getElementById('listModal').style.display === 'flex';
                if (!modalOpen) return;

                if (e.key === 'Escape') {
                    closeModal();
                }
            });
            document.getElementById('listModal').addEventListener('click', e => {
                if (e.target.id === 'listModal') closeModal();
            });
        });
    </script>
</body>
</html>
