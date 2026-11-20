<?php
include_once 'sessionConfig.php';
if (!isset($_SESSION['userdata'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<button onclick="saveState()" class="large-button">Run Save State()</button>


<script>
const list_id = '30'
const tierArrays = {
    S: [
        "public/users/barry/13ff808d62cf068475c7dcbc772056eb.jpg",
        "public/users/barry/a807fe009d9f4b5b31b6a626cf5afc29.mp4"
    ],
    A: [
        "public/users/barry/993c70a5532bdc7a16dfb48db0df4cf8.jpg"
    ],
    B: [],
    C: [
        "public/users/barry/eca9b3faed1eeb38e77502cfa792f394.jpg",
        "public/users/barry/989a725ab4d7350b34f62a36d706aa52.jpg"
    ],
    D: [
        "public/users/barry/52264b2c63fb87861fcbcdbc61310ba5.jpg"
    ],
    L: [
        "public/users/barry/399db5d988bfb0c8c4cf2c6d97cf69b2.jpg",
        "public/users/barry/9f50213e9aaa658215716b28edff48b9.jpg"
    ]
};


async function saveState() {
    
    try {
        const response = await fetch('saveTierData.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ list_id: list_id, tierData: tierArrays })
        });

        if (!response.ok) throw new Error('Failed to save tier data');
        
        console.log('Tier data saved to server');
        
    } catch (error) {
        console.error('Error saving tier data to server:', error);
    }

}

</script>
</body>
</html>