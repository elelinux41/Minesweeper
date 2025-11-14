<?php
require "inc/translation.php";
require "inc/minefield.php";

session_start();
$trans = load_translation("inc/dictionary.csv", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
?>
<!DOCTYPE html>
<head>
    <title><?php echo $trans["minesweeper"];?></title>
    <link rel="icon" type="image/x-icon" href="assets/logo.png">
    <link rel="stylesheet" href="assets/minesweeper.css">
    <meta charset="UTF-8">
    <meta name="keywords" content="minesweeper, tesselation, modifiable, game, bomb, strategy, mines">
    <meta name="description" content="<?php echo $trans["meta_description"];?>">
    <meta name="autor" content="Linus Hollmann">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="assets/fucking_js.js"></script>
</head>
<body>
    <h1><?php echo $trans["minesweeper"];?></h1>
    <?php
    require "inc/game.php";
    require "inc/settings.php";
    include "inc/rules.php";
    ?>
    <footer>
        © Linus Hollmann
    </footer>
</body>
</html>