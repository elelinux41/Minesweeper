<fieldset>
    <legend><?php echo $trans["settings"];?></legend>
    <form method="post">
    <table border="0" align="center">
        <tr>
            <td>
                <?php echo $trans["fields"];?>
            </td>
            <td>
                <input type="range" min="16" max="256" step="1" name="fields" value="<?php echo $_SESSION['field']->cells;?>"/>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $trans["bombs"];?>
            </td>
            <td>
                <input type="range" min="0.05" max="0.4" step="any" value="<?php echo $_SESSION['field']->bombs/$_SESSION["field"]->cells;?>" name="bombs"/>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $trans["shape"];?>
            </td>
            <td>
                <?php
                foreach (MineField::$FORMNAMES as $i => $name) {
                    echo '<label><input type="radio" name="form" value="'
                    . (string)$i
                    . '" '
                    . ($_SESSION["field"]->form == $i ? "checked='checked'" : "")
                    . ">"
                    . $trans[$name]
                    . "</label><br/>";
                }?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $trans["roman"];?>
            </td>
            <td>
                <input type="checkbox" name="roman" <?php echo $_SESSION["field"]->roman ? "checked='checked'" : "";?>/>
            </td>
        </tr>
    </table>
    <input type="submit" name="new_game" value="<?php echo $trans["new_game"];?>"/>
    </form>
</fieldset>