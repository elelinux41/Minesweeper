<div class="game-container">
<?php
// start new game
if (isset($_POST['new_game']) || isset($_GET["new_game"]) || !isset($_SESSION["field"])) {
    session_unset();
    $_SESSION["field"] = new MineField(
        (int)($_POST["fields"] ?? 144),
        (float)($_POST["bombs"] ?? 0.12),
        (int)($_POST["form"] ?? 4),
        isset($_POST["roman"])
    );
}

// process cell actions
if (isset($_GET['row']) && isset($_GET['col'])) {
    $row = (int)$_GET['row'];
    $col = (int)$_GET['col'];
    
    if (isset($_GET['flag'])) {
        $_SESSION["field"]->flag($row, $col);
    } else {
        if (!$_SESSION["field"]->reveal($row, $col) && !isset($_POST["close_warning"])) {
            $_SESSION["field"]::print_warning($trans);
        }
    }
}

// show game
echo $_SESSION["field"];
if (isset($_SESSION["field"]->outcome) && !isset($_POST['close_outcome'])) {
    $_SESSION["field"]->print_outcome($trans);
}
?>
</div>