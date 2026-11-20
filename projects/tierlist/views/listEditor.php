<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

if (!isset($_SESSION['userdata'])) {
    header('Location: ' . SELF_URL . '?page=login');
    exit;
}

$userdata = $_SESSION['userdata'];
$home_directory = $userdata['home_directory'];

$error = null;
$list_name = ''; //initialze to blank in case the list is not found
$list_id = null;

function dbCheck(){
    global $pdo, $list_name, $error, $list_id;

    try{

        $list_id = $_GET['list_id'] ?? null;
        if (!$list_id) {
            $error = 'List ID is required';
            return;
        }

        $pdo = getPdoObject();
        if (!$pdo) {
            $error = 'Database connection error. Could not load content. Try refreshing the page.';
            return;
        }

        $stmt = $pdo->prepare('SELECT list_name FROM lists WHERE list_id = ?');
        $stmt->execute([$list_id]);
        $list_name = $stmt->fetchColumn();

        if (!$list_name) {
            $error = 'List not found';
        }

    } catch (Throwable $th){
        writeToLog($th, APP_ROOT . '/logs/listEditor.php');
        $error = 'Error accessing database or list not found.';
    }
}

dbCheck();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tier List</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="button-container">
        <!-- <button id="downloadBtn" class="large-button">Download Tier Data</button> -->
        <!-- <button id="uploadBtn" class="large-button">Import Tier Data</button> -->
        <button id="resetBtn" class="large-button">Reset Tiers</button>
        <button id="dashboardBtn" class="large-button" onclick="location.href='<?= SELF_URL ?>?page=dashboard'">
            Back to <?php echo htmlspecialchars($userdata['username']); ?>'s Dashboard
        </button>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="container">
        <h1><?php echo htmlspecialchars($list_name); ?></h1>

        <div class="tier-list">
            <div class="tier-row tier-s">
                <div class="tier-label">S</div>
                <div class="tier-content"></div>
            </div>
            <div class="tier-row tier-a">
                <div class="tier-label">A</div>
                <div class="tier-content"></div>
            </div>
            <div class="tier-row tier-b">
                <div class="tier-label">B</div>
                <div class="tier-content"></div>
            </div>
            <div class="tier-row tier-c">
                <div class="tier-label">C</div>
                <div class="tier-content"></div>
            </div>
            <div class="tier-row tier-d">
                <div class="tier-label">D</div>
                <div class="tier-content"></div>
            </div>
        </div>

        <h1>Content</h1>
        <div class="button-container" id="fileManagementControls">
            <button id="addFilesBtn" class="large-button">
                Add Files
            </button>
            <button id="deleteModeBtn" class="large-button">
                Delete Files
            </button>
            <p id="editModeTxt" hidden>
                Select files below to delete...
            </p>

            <!-- wrapper for cancel and delete buttons -->
            <div class="delete-mode-actions">
                <button id="exitDeleteModewBtn" class="large-button" hidden>
                    cancel
                </button>
                <button id="deleteSelectedFilesBtn" class="large-button" hidden>
                    delete
                </button>
            </div>
        </div>

        <!-- display media elements that are unranked (in L) -->
        <div class="image-grid-section">
            <div class="image-grid"></div>
        </div>
    </div>


    <!-- /////////// modals ///////////////// -->

    <!-- Image/Video Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div id="modalMediaContainer"></div>

            <button class="nav-btn prev-btn" id="prevBtn">←</button>
            <button class="nav-btn next-btn" id="nextBtn">→</button>

            <div class="tier-buttons">
                <button class="tier-btn tier-s">S</button>
                <button class="tier-btn tier-a">A</button>
                <button class="tier-btn tier-b">B</button>
                <button class="tier-btn tier-c">C</button>
                <button class="tier-btn tier-d">D</button>
                <button class="tier-btn tier-l">None</button>
            </div>
        </div>
    </div>

    <!-- Add files modal -->
    <div id="listModal" class="form-modal">
        <div class="form-modal-content">
            <button class="list-btn left" id="closeModalBtn">
                cancel
            </button>
            <h2>Add files to <?php echo htmlspecialchars($list_name); ?></h2>
            <form id="addFilesForm" action="<?= SELF_URL ?>?action=addFilesToList" method="POST" enctype="multipart/form-data">

                <input type="file" id="filesInput" name="filesInput[]" accept="image/*,video/*" multiple>
                <input type="hidden" name="list_id" value="<?= htmlspecialchars($list_id); ?>">
                <br>
                <button type="submit" class="large-button">Save</button>
            </form>
        </div>
    </div>


     <script>
        const list_id = '<?= $list_id; ?>';
        const home_directory = '<?= $home_directory; ?>';
        const ENTRY_URL = '<?= SELF_URL ?>';

        const selectedFiles = [];

        function showListModal(){ document.getElementById("listModal").style.display = "flex"; }
        function closeListModal(){ document.getElementById("listModal").style.display = "none"; }

        document.getElementById('addFilesForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            try{
                const response = await fetch('<?= SELF_URL ?>?action=addFilesToList', {
                    method: 'POST',
                    body: formData
                });

                if(!response.ok){
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();
                if(result.success){
                    alert('files added');
                    window.location.reload();
                }

            } catch (err) {
                console.error(`add files failed: ${err}`);
            }
        });

        function startFileDeleteMode(){
            // console.log('start delete mode')
            document.getElementById('editModeTxt').hidden = false;
            document.getElementById('exitDeleteModewBtn').hidden = false;

            const delFilesBtn = document.getElementById('deleteSelectedFilesBtn');
            delFilesBtn.hidden = false;
            const grid = document.querySelector('.image-grid');
            grid.innerHTML = '';

            tierArrays.L.forEach((url, index) => {
                const media = createMediaElement(url, false);
                media.style.cursor = 'pointer';

                // add event listener to select files for deletion
                media.addEventListener('click', (e) => {


                    if(selectedFiles.includes(url)){

                        // console.log('removing item from deletelist')

                        selectedFiles.splice(selectedFiles.indexOf(url), 1);
                        e.target.classList.remove('gi-delete-selected');

                        if(selectedFiles.length === 0){
                            delFilesBtn.textContent = `delete`
                        } else {
                            delFilesBtn.textContent = `delete (${selectedFiles.length})`
                        }

                        return;
                    }

                    // console.log('adding item to delete list')
                    e.target.classList.add('gi-delete-selected');

                    selectedFiles.push(url);
                    delFilesBtn.textContent = `delete (${selectedFiles.length})`
                });

                grid.appendChild(media);

            });

            delFilesBtn.addEventListener('click', handleDeleteButtonClick);
        }

        // define function to send list of files to api endpoint to delete
        async function handleDeleteButtonClick(){
            // console.log('clicked delete button')
            if(selectedFiles.length === 0) return;

            if (!confirm(`Are you sure you want to delete ${selectedFiles.length} files ?`)) {
                // console.log('skipped deletion')
                return;
            }

            // console.log('1. proceeding with deletion')

            let filenames = selectedFiles.map((item) => {
                /* get just the filenames selectedFiles are paths like:
                    public/users/username/filename.png */
                return item.substring(item.lastIndexOf('/') + 1);
            });

            try {
                // console.log('2. starting fetch')
                const response = await fetch('<?= SELF_URL ?>?action=deleteFilesFromList', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ list_id, filenames })
                });

                if (!response.ok) {
                    throw new Error('Failed to delete items.');
                    alert('Failed to delete items, please try again.');
                    return;
                }

                if(response.text()){
                    // console.log('3. fetch completed')
                }


            } catch (error) {
                console.error('Error updating tier data on server:', error);
                return;
            }

            // console.log('4. exiting delete mode')
            exitFileDeleteMode();


            // console.log('5. re rendering')
            // reload and re render data
            loadState();
            populateTierRows();
            populateImageGrid();

        }


        function exitFileDeleteMode(){
            // console.log("exit delete mode");

            document.getElementById('editModeTxt').hidden = true;
            document.getElementById('exitDeleteModewBtn').hidden = true;
            const delFilesBtn = document.getElementById('deleteSelectedFilesBtn');
            delFilesBtn.hidden = true;
            delFilesBtn.textContent = 'delete';
            selectedFiles.length = 0;
            populateImageGrid();

        }




        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('addFilesBtn').addEventListener('click', showListModal);
            document.getElementById('closeModalBtn').addEventListener('click', closeListModal);

            document.getElementById('deleteModeBtn').addEventListener('click', startFileDeleteMode);
            document.getElementById('exitDeleteModewBtn').addEventListener('click', exitFileDeleteMode);

            document.addEventListener('keydown', e => {
                const modalOpen = document.getElementById('listModal').style.display === 'flex';
                if (!modalOpen) return;

                if (e.key === 'Escape') {
                    closeListModal();
                }
            });
            document.getElementById('listModal').addEventListener('click', e => {
                if (e.target.id === 'listModal') closeListModal();
            });
        });

     </script>
    <script src="js/app.js"></script>

</body>
</html>
