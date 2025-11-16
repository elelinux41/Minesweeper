<?php
/**
 * MineField class for Minesweeper game
 * 
 * A MineField represents the game board for a Minesweeper game,
 * it can reveal cells, place flags, and check for win/loss conditions,
 * it supports different board shapes: triangular, quadratic, and hexagonal,
 * it also handles bomb placement and calculates the numbers for each cell.
 * 
 * @package Minesweeper
 * @author Linus Hollmann <linus.l.hollmann@hotmail.com>
 * @version 1.0
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
require_once 'romanise.php';
class MineField {
    public $size;
    public $bombs;
    public $form;
    public $cells;
    public $roman;
    public $starttime;
    public $board; //values: 0 for empty, int for number, INF for bomb
    public $flag; //values: TRUE for flag, FALSE for revealed, NULL for neither
    public $outcome;
    public $endtime;
    private $bombs_placed;
    public static $FORMNAMES = [
        3 => 'triangular',
        4 => 'quadratic',
        5 => 'egyptian',
        6 => 'hexagonal',
    ];    
    
    /**
     * @param int $fields Aproximate number of fields on the board.
     * @param float $bombs Ratio of bombs to total fields.
     * @param int $form Shape of the board (3: triangular, 4: quadratic, 6: hexagonal).
     */
    public function __construct($fields, $bombs, $form, $roman = false) {
        //proof params
        if (!isset(self::$FORMNAMES[$form])) {
            throw new Exception("Unknown form.");
        }
        if ($bombs <= 0 || $bombs >= 1) {
            throw new Exception("Ratio of bombs must be ∈ ]0;1[.");
        }
        if ($fields <= 0) {
            throw new Exception("Number of fields must be ∈ ℕ^+");
        }
        //readonly props
        $this->form = $form;
        
        if ($this->form == 3 || $this->form == 4 || $this->form == 5) {
            $this->size = round(pow($fields, 0.5));
        } else {
            $this->size = round(0.5 + pow(0.25 + ($fields - 1) / 3, 0.5));
        }
        
        if ($this->form == 3 || $this->form == 4 || $this->form == 5) {
            $this->cells = pow($this->size, 2);
        } else {
            $this->cells = 3 * $this->size * ($this->size - 1) + 1;
        }
        
        $this->bombs = round($bombs * $this->cells);
        $this->roman = $roman;
        $this->starttime = new DateTime();
        //modifiable props
        $this->board = $this->gen_empty_field($this->form, false);
        $this->flag = $this->gen_empty_field($this->form, null);
        $this->bombs_placed = false;
        $this->outcome = null;
        $this->endtime = null;
    }
    
    /**
     * Reveals a cell at the specified row and column.
     * 
     * Reveals the cell only if it is not flagged.
     * Checks automatically if the move ends the game.
     * 
     * @param int $row
     * @param int $col
     * @return bool FALSE if bombs could not be placed because board is too small, TRUE otherwise
     */
    public function reveal($row, $col) {
        if (!isset($this->board[$row][$col])) {
            return false;
        }
        //check if field is already revealed or flagged
        if (isset($this->flag[$row][$col]) || isset($this->outcome)) {
            return true;
        }
        //check if bombs are placed
        if (!$this->bombs_placed) {
            if (!$this->place_bombs($row, $col)) {
                return false;
            }
        }
        //set value
        $this->flag[$row][$col] = false;
        //reveal neighbors of field if empty
        if ($this->board[$row][$col] == 0) {
            foreach ($this->near_fields($row, $col, false) as $field) {
                if (!isset($this->flag[$field[0]][$field[1]]) || !$this->flag[$field[0]][$field[1]]) {
                    $this->reveal($field[0], $field[1]);
                }
            }
        }
        $this->check_outcome();
        return true;
    }
    
    /**
     * Sets or removes a flag at the specified row and column.
     * 
     * If field revealed, no action is taken.
     * 
     * @param int $row
     * @param int $col
     */
    public function flag($row, $col) {
        if (!isset($this->outcome)) {
            if ($this->flag[$row][$col] === null) {
                $this->flag[$row][$col] = true;
            } elseif ($this->flag[$row][$col] === true) {
                $this->flag[$row][$col] = null;
            }
        }
    }
    
    /**
     * Displays the game board in html format.
     */
    public function __toString() {
        global $trans;
        if ($this->form == 3) {
            $string = '<div class="form-container triangle-container" style="width: ' . ($this->size * 47.15) . 'px;">' . "\n";
        } elseif ($this->form == 4) {
            $string = '<div class="form-container square-container">' . "\n";
        } elseif ($this->form == 5) {
            $string = '<div class="form-container cairo-container">' . "\n";
        } else {
            $string = '<div class="form-container hexagon-container">' . "\n";
        }
        
        foreach ($this->board as $i => $row) {
            $string .= "<div class=\"row row{$this->form}\">\n";
            foreach ($row as $j => $cell) {
                $class = 'cell cell' . $this->form;
                switch ($this->form) {
                    case 3:
                        // determine orientation of triangle
                        $class .= $i % 2 == abs($j) % 2 ? ' clip-up' : ' clip-down';
                        break;
                    case 5:
                        // determine orientation of pentagon
                        if ($i % 2 == 0 && $j % 2 == 0) {
                            $class .= ' quadrant2';
                        } elseif ($i % 2 == 0 && $j % 2 != 0) {
                            $class .= ' quadrant3';
                        } elseif ($i % 2 != 0 && $j % 2 == 0) {
                            $class .= ' quadrant1';
                        } else {
                            $class .= ' quadrant4';
                        }
                        break;
                }
                if ($this->flag[$i][$j] === false) {
                    $class .= ' revealed';
                    if (is_infinite($this->board[$i][$j])) {
                        $class .= ' bomb';
                        $content = '💣';
                    } elseif ($this->board[$i][$j] == 0) {
                        $content = '';
                    } else {
                        $content = $this->roman ? romanise($this->board[$i][$j]) : $this->board[$i][$j];
                        $class .= " number" . $this->board[$i][$j]; 
                    }
                } elseif ($this->flag[$i][$j] === true) {
                    $class .= ' flagged';
                    $content = '🚩';
                } else {
                    $content = '';
                }
                $link = "href=\"?row={$i}&col={$j}\"";
                $divclass = isset($class) ? ("class=\"$class\"") : '';
                $string .= $this->flag[$i][$j] === false ?
                    "<div $divclass><span>$content</span></div>\n" :
                    "<div $divclass><a $link class=\"cell\"><span>$content</span></a></div>\n";
            }
            $string .= "</div>\n";
        }
        $string .= "</div>\n";
        // show remaining flags
        $flags = 0;
        foreach ($this->flag as $i) {
            foreach ($i as $j) {
                if ($j === true) {
                    $flags++;
                }
            }
        }
        $string .= "<p>{$trans["remaining_flags"]}: ". ($this->bombs - $flags) . "</p>";
        return $string;
    }

    /**
     * Displays the game outcome in html format if the game has ended.
     * 
     * @param array $trans Translation array for text elements
     */
    public function print_outcome() {
        global $trans;
        if (!isset($this->outcome)) {
            return;
        } elseif ($this->outcome) {
            echo '<div class="modal-window" style="box-shadow: 0 4px 8px green;"><center>';
            echo "<h2 style=\"color: green;\">{$trans["win"]} 🎉</h2>";
        } else {
            echo '<div class="modal-window" style="box-shadow: 0 4px 8px red;"><center>';
            echo "<h2 style=\"color: red;\">{$trans["lose"]} 💣</h2>";
            echo '<audio autoplay><source src="assets/explosion.mp3" type="audio/mpeg"></audio>';
        }
        
        if ($this->cells <= 19) {
            $size_key = 'micro';
        } elseif ($this->cells <= 25) {
            $size_key = 'mini';
        } elseif ($this->cells <= 36) {
            $size_key = 'small';
        } elseif ($this->cells <= 64) {
            $size_key = 'moderate';
        } elseif ($this->cells <= 100) {
            $size_key = 'medium';
        } elseif ($this->cells <= 144) {
            $size_key = 'large';
        } elseif ($this->cells <= 225) {
            $size_key = 'immense';
        } else {
            $size_key = 'extreme';
        }
        echo "<p>{$trans["size"]}: ". $trans[$size_key].'</p>';
        
        $bombs = $this->bombs / $this->cells;
        if ($bombs < 0.1) {
            $bomb_key = 'low';
        } elseif ($bombs < 0.2) {
            $bomb_key = 'medium';
        } elseif ($bombs < 0.3) {
            $bomb_key = 'high';
        } elseif ($bombs < 0.4) {
            $bomb_key = 'bosnia';
        } else {
            $bomb_key = 'berlin';
        }
        echo "<p>{$trans["bomb_density"]}: ". $trans[$bomb_key].'</p>';
        echo "<p>{$trans["shape"]}: ". $trans[self::$FORMNAMES[$this->form]].'</p>';
        echo "<p>{$trans["play_time"]}: ". date_diff($this->endtime, $this->starttime)->format('%H:%I:%S') .'</p>';
        echo "<form method=\"post\"><button type=\"submit\" name=\"close_outcome\">{$trans["close"]}</button></form>";
        echo '</center></div>';
    }

    public static function print_warning() {
        global $trans;
        echo "<div class=\"modal-window\"><center>
        <h3>⚠️ {$trans["warning"]}</h3>
        <p>{$trans["inadmissible"]}</p>
        <form method=\"post\"><button type=\"submit\" name=\"close_warning\">{$trans["close"]}</button></form>
        </center></div>";
    }

    private function place_bombs($row, $col) {
        //determine possible fields for bombs
        $cells = [];
        $near_fields = $this->near_fields($row, $col, true);
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                if (!in_array([$i, $j], $near_fields)) {
                    // Alle Zellen in ein Array speichern
                    $cells[] = [$i, $j];
                }
            }
        }
        //is there place for all bombs
        if (count($cells) <= $this->bombs) {
            return false;
        }
        //place bombs on random fields
        $rand_keys = array_rand($cells, $this->bombs);
        foreach ($rand_keys as $key) {
            $this->board[$cells[$key][0]][$cells[$key][1]] = INF;
        }
        //calculate number of every field
        foreach ($this->board as $i => $row) {
            foreach ($row as $j => $cell) {
                if (!is_infinite($this->board[$i][$j])) {
                    $this->board[$i][$j] = $this->calc_number($i, $j);
                }
            }
        }
        $this->bombs_placed = true;
        return true;
    }

    private function near_fields($row, $col, $self) {
        //determine surrounding fields
        if ($this->form == 3) {
            if ($row % 2 == abs($col) % 2) { //clip-up
                $fields = [
                    [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1],
                    [$row, $col-2], [$row, $col-1], [$row, $col+1], [$row, $col+2],
                    [$row+1, $col-2], [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1], [$row+1, $col+2],
                ];
            } else { //clip-down
                $fields = [
                    [$row-1, $col-2], [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1], [$row-1, $col+2],
                    [$row, $col-2], [$row, $col-1], [$row, $col+1], [$row, $col+2],
                    [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1],
                ];
            }
        } elseif ($this->form == 4) {
            $fields = [
                [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1],
                [$row, $col-1], [$row, $col+1],
                [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1]
            ];
        } elseif ($this->form == 5) {
            if ($row % 2 == 0 && $col % 2 == 0) { //quadrant2
                $fields = [
                    [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1],
                    [$row, $col-1], [$row, $col+1],
                    [$row+1, $col-1], [$row+1, $col]
                ];
            } elseif ($row % 2 == 0 && $col % 2 != 0) { //quadrant3
                $fields = [
                    [$row-1, $col-1], [$row-1, $col],
                    [$row, $col-1], [$row, $col+1],
                    [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1]
                ];
            } elseif ($row % 2 != 0 && $col % 2 == 0) { //quadrant1
                $fields = [
                    [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1],
                    [$row, $col-1], [$row, $col+1],
                    [$row+1, $col], [$row+1, $col+1]
                ];
            } else { //quadrant4
                $fields = [
                    [$row-1, $col], [$row-1, $col+1],
                    [$row, $col-1], [$row, $col+1],
                    [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1]
                ];
            }
        } else { // form == 6
            $fields = [
                [$row-1, $col-1], [$row-1, $col+1],
                [$row, $col-2], [$row, $col+2],
                [$row+1, $col-1], [$row+1, $col+1]
            ];
        }
        
        //add requested field if wanted
        if ($self) {
            $fields[] = [$row, $col];
        }
        //proof if fields are on the board
        foreach ($fields as $key => $field) {
            if (!isset($this->board[$field[0]][$field[1]])) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    private function calc_number($row, $col) {
        $count = 0;
        foreach ($this->near_fields($row, $col, false) as $field) {
            if (isset($this->board[$field[0]][$field[1]]) && is_infinite($this->board[$field[0]][$field[1]])) {
                $count++;
            }
        }
        return $count;
    }

    private function check_outcome() {
        if (isset($this->outcome)) {
            return;
        }
        $status = true;
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                //check unrevealed notbomb
                if (!is_infinite($this->board[$i][$j]) && $this->flag[$i][$j] !== false) {
                    $status = null;
                    break 2;
                }
            }
        }
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                //check revealed bomb
                if (is_infinite($this->board[$i][$j]) && $this->flag[$i][$j] === false) {
                    $status = false;
                    break 2;
                }
            }
        }
        if (isset($status)) {
            $this->outcome = $status;
            $this->endtime = new DateTime();
            $this->reveal_all_bombs();
        }
    }

    private function reveal_all_bombs() {
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                if (is_infinite($cell)) {
                    $this->flag[$i][$j] = false;
                }
            }
        }
    }

    private function gen_empty_field($form, $filling) {
        if ($form == 3) {
            $result = [];
            for ($i = 0; $i < $this->size; $i++) {
                $row = array_fill(0, 1+2*$i, $filling);
                $result[$i] = [];
                foreach ($row as $key => $j) {
                    $result[$i][$key - $i] = $j;
                }
            }
            return $result;
        } elseif ($form == 4 || $form == 5) {
            return array_fill(0, $this->size, array_fill(0, $this->size, $filling));
        } else { // form == 6
            $result = [];
            for ($i = 1 - $this->size; $i < $this->size; $i++) {
                $start = 2 + abs($i) - $this->size * 2;
                $end = $this->size * 2 - 2 - abs($i);
                $row = [];
                for ($j = $start; $j <= $end; $j += 2) {
                    $row[$j] = $filling;
                }
                $result[$i] = $row;
            }
            return $result;
        }
    }
}
?>