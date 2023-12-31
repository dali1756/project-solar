<?php
    header("Refresh: 300");   // 設定每五分鐘更新畫面一次
    include("head.php");

    
?>

<div class="content">
    <div class="container">
        <div class="page-title">
            <h3>變流器-<?php echo $area_name; ?></h3>
        </div>
        <div class="col-lg-12">
        <div> <span class="ps">提醒:下列表格超出範圍時可橫向/直向移動</span><br>    
                <i class="fas fa-circle gray"></i> 斷線 <i class=" fas fa-circle green"></i> 正常 <i
                    class=" fas fa-circle orange"></i> 告警 <i class=" fas fa-circle red"></i> 錯誤 <i
                    class=" fas fa-circle pink"></i> 狀態異常
            </div>
            <div class="row">
                <div class="invertor">
                    <table>
                        <thead>
                            <tr>
                                <th>變流器編號</th>
                                <?php
                                    foreach ($maintain_device as $index => $row) {
                                        $two_note_error = $statuses[$index]["Error_message_1"] | $statuses[$index]["Error_message_2"];
                                        $notes = [
                                            strrev(decbin($statuses[$index]["Operation_mode"])),
                                            strrev(decbin($two_note_error)),
                                            strrev(decbin($statuses[$index]["Warning_code"])),
                                            strrev(decbin($statuses[$index]["Load_de_rating_message"])),
                                            $statuses[$index]["data_validity"] == 1 ? "" : "1"
                                        ];
                                        $colors = ["green", "red", "orange", "pink", "gray"];
                                        $error = [];                                
                                        foreach ($notes as $note_index => $note) {
                                            for ($i = 0; $i < strlen($note); $i++) {
                                                if ($note[$i] == "1") {
                                                    if ($note_index == 0) {
                                                        $note1 = $notes[0];
                                                        for ($i = 0; $i < strlen($note1); $i++) {
                                                            if ($note1[$i] == "1") {
                                                                if ($i == 4) {
                                                                    $error[] = "<i class='fas fa-circle red'></i>";
                                                                } else if ($i != 4) {
                                                                    $error[] = "<i class='fas fa-circle " . $colors[$note_index] . "'></i>";
                                                                }
                                                            }
                                                        }
                                                        $error = array_unique($error);
                                                        // var_dump($error);
                                                    } else {
                                                        $error[] = "<i class='fas fa-circle " . $colors[$note_index] . "'></i>";
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                        $errors = implode(" ", $error);
                                        echo "<th>". $errors . $row["name"]. "</th>";
                                        // var_dump(strlen($note));
                                    }
                                    ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>廠牌型號 </td>
                                <?php
                                    foreach ($maintain_device as $row) {
                                        echo "<td>". $row["hardware_device"]. "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>今日發電量kWh </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["Energy_today"]) ? number_format($row["Energy_today"], 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>總發電量kWh </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["Energy_total"]) ? number_format($row["Energy_total"], 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>即時發電量kW </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["totally_active_power"]) ? number_format($row["totally_active_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>轉換效益% </td>
                                <?php
                                    // foreach ($invertor_maintain as $row) {
                                    //     $dc_input_power = (($row["1st_input_power"] * 0.1 / 1000) + ($row["2nd_input_power"] * 0.1 / 1000) + ($row["3rd_input_power"] * 0.1 / 1000) + ($row["4th_input_power"] * 0.1 / 1000));
                                    //     $ac_output_power = (($row["L1_power"] * 0.1 / 1000) + ($row["L2_power"] * 0.1 / 1000) + ($row["L3_power"] * 0.1 / 1000));
                                    //     $conversion_efficiency = number_format(($ac_output_power / $dc_input_power) * 100, 2);
                                    //     echo "<td>$conversion_efficiency</td>";
                                    // }

                                    foreach ($invertor_maintain as $row) {
                                        $dc_input_power = (($row["1st_input_power"] * 0.1 / 1000) + ($row["2nd_input_power"] * 0.1 / 1000) + ($row["3rd_input_power"] * 0.1 / 1000) + ($row["4th_input_power"] * 0.1 / 1000));
                                        $ac_output_power = (($row["L1_power"] * 0.1 / 1000) + ($row["L2_power"] * 0.1 / 1000) + ($row["L3_power"] * 0.1 / 1000));
                                        if($dc_input_power != 0) {
                                            $conversion_efficiency = number_format(($ac_output_power / $dc_input_power) * 100, 2);
                                        } else {
                                            $conversion_efficiency = 0; 
                                        }
                                        echo "<td>$conversion_efficiency</td>";
                                    }
                                    
                                ?>
                            </tr>
                            <tr>
                                <td>內部溫度&#176;C </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["internal_temperature"]) ? number_format($row["internal_temperature"], 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>1串直流電壓V </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["1st_input_voltage"]) ? number_format($row["1st_input_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>1串直流電流A </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["1st_input_current"]) ? number_format($row["1st_input_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>1串輸入功率kW </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["1st_input_power"]) ? number_format($row["1st_input_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>2串直流電壓V </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["2nd_input_voltage"]) ? number_format($row["2nd_input_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>2串直流電流A </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["2nd_input_current"]) ? number_format($row["2nd_input_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>2串輸入功率kW </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["2nd_input_power"]) ? number_format($row["2nd_input_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>3串直流電壓V </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["3rd_input_voltage"]) ? number_format($row["3rd_input_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>3串直流電流A </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["3rd_input_current"]) ? number_format($row["3rd_input_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>3串輸入功率kW </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["3rd_input_power"]) ? number_format($row["3rd_input_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>4串直流電壓V </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["4th_input_voltage"]) ? number_format($row["4th_input_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>4串直流電流A </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["4th_input_current"]) ? number_format($row["4th_input_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>4串輸入功率kW </td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["4th_input_power"]) ? number_format($row["4th_input_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L1交流電壓V</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L1_phase_voltage"]) ? number_format($row["L1_phase_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L1交流電流A</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L1_phase_current"]) ? number_format($row["L1_phase_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L1交流功率kW</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L1_power"]) ? number_format($row["L1_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L1交流頻率Hz</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L1_AC_frequency"]) ? number_format($row["L1_AC_frequency"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L2交流電壓V</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L2_phase_voltage"]) ? number_format($row["L2_phase_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L2交流電流A</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L2_phase_current"]) ? number_format($row["L2_phase_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L2交流功率kW</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L2_power"]) ? number_format($row["L2_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L2交流頻率Hz</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L2_AC_frequency"]) ? number_format($row["L2_AC_frequency"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L3交流電壓V</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L3_phase_voltage"]) ? number_format($row["L3_phase_voltage"] * 0.1, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L3交流電流A</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L3_phase_current"]) ? number_format($row["L3_phase_current"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L3交流功率kW</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L3_power"]) ? number_format($row["L3_power"] * 0.1 / 1000, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td>L3交流頻率Hz</td>
                                <?php
                                    foreach ($invertor_maintain as $row) {
                                        echo "<td>". (isset($row["L3_AC_frequency"]) ? number_format($row["L3_AC_frequency"] * 0.01, 2) : ""). "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td class="warning">Note1</td>
                                <?php
                                    $modes = [
                                        "0x00","0x01","0x02","0x03","0x05",
                                        "0x07","0x08"
                                    ];
                                    foreach ($statuses as $status) {
                                        echo "<td>";
                                        $note1 = strrev(decbin($status["Operation_mode"]));
                                        for ($i = 0; $i < strlen($note1); $i++) {
                                            if ($note1[$i] == "1") {
                                                echo $modes[$i];
                                                if ($i < strlen($note1) - 1) {
                                                    echo "<br>";
                                                }
                                            }
                                        }
                                        echo "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td class="warning">Note2</td>
                                <?php
                                    $modes_note2 = [
                                        "0","1","2","3","4",
                                        "5","6","7","8","9",
                                        "10","11","12","13","14",
                                        "15"
                                    ];
                                        foreach ($statuses as $status) {
                                            echo "<td>";
                                            $note2 = strrev(decbin($status["Error_message_1"]));
                                            for ($i = 0; $i < strlen($note2); $i++) {
                                                if ($note2[$i] == "1") {
                                                    echo $modes_note2[$i];
                                                    if ($i < strlen($note2) - 1) {
                                                        echo "<br>";
                                                    }
                                                }
                                            }
                                            echo "</td>";
                                        }
                                ?>
                            </tr>
                            <tr>
                                <td class="warning">Note3</td>
                                <?php
                                    $modes_note3 = [
                                        "0","1","2","3","4",
                                        "5","6","7","8","9",
                                        "10","11","12","13","14",
                                        "15"
                                    ];
                                    foreach ($statuses as $status) {
                                        echo "<td>";
                                        $note3 = strrev(decbin($status["Error_message_2"]));
                                        for ($i = 0; $i < strlen($note3); $i++) {
                                            if ($note3[$i] == "1") {
                                                echo $modes_note3[$i];
                                                if ($i < strlen($note3) - 1) {
                                                    echo "<br>";
                                                }
                                            }
                                        }
                                        echo "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td class="warning">Note4</td>
                                <?php
                                    $modes_note4 = [
                                        "0","1","2","3","4",
                                        "5","6","7","8","9",
                                        "10","11","12","13","14",
                                        "15"
                                    ];
                                    foreach ($statuses as $status) {
                                        echo "<td>";
                                        $note4 = strrev(decbin($status["Warning_code"]));
                                        for ($i = 0; $i < strlen($note4); $i++) {
                                            if ($note4[$i] == "1") {
                                                echo $modes_note4[$i];
                                                if ($i < strlen($note4) - 1) {
                                                    echo "<br>";
                                                }
                                            }
                                        }
                                        echo "</td>";
                                    }
                                ?>
                            </tr>
                            <tr>
                                <td class="warning">Note5</td>
                                <?php
                                    $modes_note5 = [
                                        "0","1","2","3","4",
                                        "5","6","7","8","9",
                                        "10","11","12","13","14",
                                        "15"
                                    ];
                                    foreach ($statuses as $status) {
                                        echo "<td>";
                                        $note5 = strrev(decbin($status["Load_de_rating_message"]));
                                        for ($i = 0; $i < strlen($note5); $i++) {
                                            if ($note5[$i] == "1") {
                                                echo $modes_note5[$i];
                                                if ($i < strlen($note5) - 1) {
                                                    echo "<br>";
                                                }
                                            }
                                        }
                                        echo "</td>";
                                    }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>
