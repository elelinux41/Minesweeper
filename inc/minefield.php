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
    public readonly int $size;
    public readonly int $bombs;
    public readonly int $form;
    public readonly int $cells;
    public readonly bool $roman;
    public readonly DateTime $starttime;
    public array $board; //values: 0 for empty, int for number, INF for bomb
    public array $flag; //values: TRUE for flag, FALSE for revealed, NULL for neither
    public ?bool $outcome;
    public ?DateTime $endtime;
    private bool $bombs_placed;
    public const FORMNAMES = [
        3 => 'triangular',
        4 => 'quadratic',
        # 5 => 'egyptian', //comming soon
        6 => 'hexagonal',
    ];
    private const COLORS = ['', 'blue', 'green', 'red', 'darkblue', 'brown', 'teal', 'black', 'gray'];
    
    /**
     * @param int $fields Aproximate number of fields on the board.
     * @param float $bombs Ratio of bombs to total fields.
     * @param int $form Shape of the board (3: triangular, 4: quadratic, 6: hexagonal).
     */
    public function __construct (int $fields, float $bombs, int $form, bool $roman = false)  {
        //proof params
        if (!isset($this::FORMNAMES[$form])) {
            throw new ValueError("Unknown form.");
        }
        if ($bombs <= 0 || $bombs >= 1) {
            throw new ValueError("Ratio of bombs must be ∈ ]0;1[.");
        }
        if ($fields <= 0) {
            throw new ValueError("Number of fields must be ∈ ℕ^+");
        }
        //readonly props
        $this->form = $form;
        $this->size = round(match ($this->form) {
            3, 4 => $fields**.5,
            6 => .5 + (.25 + ($fields - 1) /3)**.5
        });
        $this->cells = match ($this->form) {
            3, 4 => $this->size ** 2,
            6 => 3 * $this->size * ($this->size - 1) + 1,
        };
        $this->bombs = round($bombs*$this->cells);
        $this->roman = $roman;
        $this->starttime = new DateTime();
        //modifiable props
        $this->board = $this->gen_empty_field($this->form, FALSE);
        $this->flag = $this->gen_empty_field($this->form, NULL);
        $this->bombs_placed = FALSE;
        $this->outcome = NULL;
        $this->endtime = NULL;
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
    public function reveal (int $row, int $col) : bool {
        //check if field is already revealed or flagged
        if (isset($this->flag[$row][$col]) || isset($this->outcome)) {
            return TRUE;
        }
        //check if bombs are placed
        if (!$this->bombs_placed) {
            if (!$this->place_bombs($row,$col)) {
                return FALSE;
            }
        }
        //set value
        $this->flag[$row][$col] = FALSE;
        //reveal neighbors of field if empty
        if ($this->board[$row][$col] == 0) {
            foreach ($this->near_fields($row, $col, FALSE) as $field) {
                if (!$this->flag[$field[0]][$field[1]]) {
                    $this->reveal($field[0], $field[1]);
                }
            }
        }
        $this->check_outcome();
        return TRUE;
    }
    /**
     * Sets or removes a flag at the specified row and column.
     * 
     * If field revealed, no action is taken.
     * 
     * @param int $row
     * @param int $col
     */
    public function flag (int $row, int $col) {
        if (!isset($this->outcome)) {
            $this->flag[$row][$col] = match ($this->flag[$row][$col]) {
                NULL => TRUE,
                TRUE => NULL,
                FALSE => FALSE
            };
        }
    }
    /**
     * Displays the game board in html format.
     * 
     * @param array $trans Translation array for text elements
     */
    public function display (array $trans) {
        switch ($this->form) {
            case 3:
                echo '<div class="triangle-container" style="width: ' . ($this->size * 47.15) . 'px;">';
                foreach ($this->board as $i => $row) {
                    echo '<div class="row3">';
                    foreach ($row as $j => $cell) {
                        // determine orientation of triangle
                        $orientation = $i % 2 == abs($j) % 2 ? 'clip-up' : 'clip-down';
                        if ($this->flag[$i][$j] === FALSE) {
                            $class = $this->flag[$i][$j] == FALSE ? 'revealed' : ''; //NOTWENDIG?
                            //determine content of each revealed cell
                            if ($this->board[$i][$j] == INF) {
                                $class .= ' bomb';
                                $content = '💣';
                            } elseif ($this->board[$i][$j] == 0) {
                                $content = '';
                            } else {
                                $content = $this->roman ? romanise($this->board[$i][$j]) : $this->board[$i][$j];
                                $color = $this::COLORS[$this->board[$i][$j]] ?? 'black';
                                $style = "color: $color;";
                            }
                            echo '<div class="cell cell3 ' . $class .' '. $orientation . '" style="' . ($style ?? '') . '">' . $content . '</div>';
                        } elseif ($this->flag[$i][$j] === TRUE) {
                            echo '<div class="cell cell3 flag '.$orientation.'"><a href="?row=' . $i . '&col=' . $j . '" class="cell">🚩</a></div>';
                        } else {
                            echo '<div class="cell cell3 '.$orientation.'"><a href="?row=' . $i . '&col=' . $j . '" class="cell"></a></div>';
                        }
                    }
                    echo '</div>';
                }
                echo '</div>';
                break;
            case 4:
                echo '<table cellspacing="0" cellpadding="0" style="margin: 0 auto;">';
                for ($i = 0; $i < $this->size; $i++) {
                    echo '<tr>';
                    for ($j = 0; $j < $this->size; $j++) {
                        echo '<td>';
                        if ($this->flag[$i][$j] === FALSE) {
                            $class = $this->flag[$i][$j] == FALSE ? 'revealed' : '';
                            
                            if ($this->board[$i][$j] == INF) {
                                $class .= ' bomb';
                                $content = '💣';
                            } elseif ($this->board[$i][$j] == 0) {
                                $content = '';
                            } else {
                                $content = $this->roman ? romanise($this->board[$i][$j]) : $this->board[$i][$j];
                                // Farben für Zahlen
                                $color = $this::COLORS[$this->board[$i][$j]] ?? 'black';
                                $style = "color: $color;";
                            }
                            echo '<div class="cell cell4 ' . $class . '" style="' . ($style ?? '') . '">' . $content . '</div>';
                        } elseif ($this->flag[$i][$j] === TRUE) {
                            echo '<a href="?row=' . $i . '&col=' . $j . '" class="cell cell4"><div class="cell flag">🚩</div></a>';
                        } else {
                            echo '<a href="?row=' . $i . '&col=' . $j . '" class="cell cell4"></a>';
                        }
                        
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
                break;
            case 6:
                echo '<div class="hexagon-container">';
                foreach ($this->board as $i => $row) {
                    echo '<div class="row6">';
                    foreach ($row as $j => $cell) {
                        if ($this->flag[$i][$j] === FALSE) {
                            $class = $this->flag[$i][$j] == FALSE ? 'revealed' : '';
                            
                            if ($this->board[$i][$j] == INF) {
                                $class .= ' bomb';
                                $content = '💣';
                            } elseif ($this->board[$i][$j] == 0) {
                                $content = '';
                            } else {
                                $content = $this->roman ? romanise($this->board[$i][$j]) : $this->board[$i][$j];
                                $color = $this::COLORS[$this->board[$i][$j]] ?? 'black';
                                $style = "color: $color;";
                            }
                            echo '<div class="cell cell6 ' . $class .'" style="' . ($style ?? '') . '">' . $content . '</div>';
                        } elseif ($this->flag[$i][$j] === TRUE) {
                            echo '<div class="cell cell6 flag"><a href="?row=' . $i . '&col=' . $j . '" class="cell">🚩</a></div>';
                        } else {
                            echo '<div class="cell cell6"><a href="?row=' . $i . '&col=' . $j . '" class="cell"></a></div>';
                        }
                    }
                    echo '</div>';
                }
                echo '</div>';
                break;
        }
        // show remaining flags
        $flags = 0;
        foreach ($this->flag as $i) {
            foreach ($i as $j) {
                if ($j === TRUE) {
                    $flags++;
                }
            }
        }
        echo "<p>{$trans["remaining_flags"]}: ". $this->bombs - $flags;
    }
    /**
     * Displays the game outcome in html format if the game has ended.
     * 
     * @param array $trans Translation array for text elements
     */
    public function print_outcome (array $trans) {
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
        echo "<p>{$trans["size"]}: ". $trans[match (true) {
            $this->cells <= 19 => 'micro',
            $this->cells <= 25 => 'mini',
            $this->cells <= 36 => 'small',
            $this->cells <= 64 => 'moderate',
            $this->cells <= 100 => 'medium',
            $this->cells <= 144 => 'large',
            $this->cells <= 225 => 'immense',
            $this->cells > 225 => 'extreme'
        }].'</p>';
        $bombs = $this->bombs/$this->cells;
        echo "<p>{$trans["bomb_density"]}: ". $trans[match (true) {
            $bombs < 0.1 => 'low',
            $bombs < 0.2 => 'medium',
            $bombs < 0.3 => 'high',
            $bombs < 0.4 => 'bosnia',
            $bombs >= 0.4 => 'berlin'
        }].'</p>';
        echo "<p>{$trans["shape"]}: ". $trans[$this::FORMNAMES[$this->form]].'</p>';
        echo "<p>{$trans["play_time"]}: ". date_diff($this->endtime, $this->starttime)->format('%H:%I:%S') .'</p>';
        echo "<form method=\"post\"><button type=\"submit\" name=\"close_outcome\">{$trans["close"]}</button></form>";
        echo '</center></div>';
    }

    public static function print_warning (array $trans) {
        echo "<div class=\"modal-window\"><center>
        <h3>⚠️ {$trans["warning"]}</h3>
        <p>{$trans["inadmissible"]}</p>
        <form method=\"post\"><button type=\"submit\" name=\"close_warning\">{$trans["close"]}</button></form>
        </center></div>";
    }

    private function place_bombs (int $row, int $col) : bool {
        //determine possible fields for bombs
        $cells = [];
        $near_fields = $this->near_fields($row,$col,TRUE);
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
            return FALSE;
        }
        //place bombs on random fields
        foreach (array_rand($cells, $this->bombs) as $key) {
            $this->board[$cells[$key][0]][$cells[$key][1]] = INF;
        }
        //calculate number of every field
        foreach ($this->board as $i => $row) {
            foreach ($row as $j => $cell) {
                if ($this->board[$i][$j] != INF) {
                    $this->board[$i][$j] = $this->calc_number($i, $j);
                }
            }
        }
        $this->bombs_placed = TRUE;
        return TRUE;
    }

    private function near_fields (int $row, int $col, bool $self) : array {
        //determine surrounding fields
        $fields = match ($this->form) {
            3 => $row % 2 == abs($col) % 2 ? [ //clip-up
                    [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1],
                    [$row, $col-2], [$row, $col-1], [$row, $col+1], [$row, $col+2],
                    [$row+1, $col-2], [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1], [$row+1, $col+2],
                ] : [ //clip-down
                    [$row-1, $col-2], [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1], [$row-1, $col+2],
                    [$row, $col-2], [$row, $col-1], [$row, $col+1], [$row, $col+2],
                    [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1],
                ],
            4 => [
                [$row-1, $col-1], [$row-1, $col], [$row-1, $col+1],
                [$row, $col-1], [$row, $col+1],
                [$row+1, $col-1], [$row+1, $col], [$row+1, $col+1]
            ],
            6 => [
                [$row-1, $col-1], [$row-1, $col+1],
                [$row, $col-2], [$row, $col+2],
                [$row+1, $col-1], [$row+1, $col+1]
            ]
        };
        //add requested field if wanted
        if ($self) {
            $fields[] = [$row,$col];
        }
        //proof if fields are on the board
        foreach ($fields as $key => $field) {
            if (!isset($this->board[$field[0]][$field[1]])) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    private function calc_number ($row, $col) : int {
        $count = 0;
        foreach ($this->near_fields($row, $col, FALSE) as $field) {
            if (isset($this->board[$field[0]][$field[1]]) && is_infinite($this->board[$field[0]][$field[1]])) {
                $count++;
            }
        }
        return $count;
    }

    private function check_outcome () {
        $status = TRUE;
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                //check unrevealed notbomb
                if ($this->board[$i][$j] !== INF && $this->flag[$i][$j] !== FALSE) {
                    $status = NULL;
                    break 2;
                }
            }
        }
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                //check revealed bomb
                if ($this->board[$i][$j] == INF && $this->flag[$i][$j] === FALSE) {
                    $status = FALSE;
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

    private function reveal_all_bombs () {
        foreach ($this->board as $i => $row0) {
            foreach ($row0 as $j => $cell) {
                if ($cell == INF) {
                    $this->flag[$i][$j] = FALSE;
                }
            }
        }
    }

    private function gen_empty_field ($form, $filling) {
        return match ($form) {
            3 => array_map(fn($i) => array_fill(-$i, 1+2*$i, $filling), range(0, $this->size -1)),
            4 => array_fill(0, $this->size, array_fill(0, $this->size, $filling)),
            6 => array_map(fn($i) =>
                    array_map(fn($j) => $filling, array_combine(range(2+abs($i)-$this->size*2, $this->size*2-2-abs($i), 2), range(2+abs($i)-$this->size*2, $this->size*2-2-abs($i), 2))),
                array_combine(range(1-$this->size, $this->size-1), range(1-$this->size,$this->size-1))),
        };
    }
}
?>