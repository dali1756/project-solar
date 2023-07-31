<?php 
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include('head.php'); 

    $area = isset($_POST['area']) ? $_POST['area'] : null;
  
    if(isset($_POST['year'])){
        $year = $_POST['year'];
    }else{
        $year = null;
    }
    // 區域
    $sql_area = "SELECT id,name,capacity FROM area";
    $stmt = $db->prepare($sql_area);
    $stmt->execute();
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(isset($_POST['area'])){
        // 區域資訊 1.案場名稱  2.容量(kWp)
        if ($_POST['area'] == 'all') {
            $areaIds = []; 
            $total_capacity = 0;
            for($i=1;$i<=count($areas);$i++ ){
                array_push($areaIds, $i);  // 區域id
            }
            $areaList = [];  // 存儲查詢的陣列
            foreach ($areaIds as $areaId) {
                $sql_area_list = "SELECT id, name,capacity FROM area WHERE id = ?";
                $stmt_list = $db->prepare($sql_area_list);
                $stmt_list->bindParam(1, $areaId, PDO::PARAM_INT);
                $stmt_list->execute();
                $areaData = $stmt_list->fetch(PDO::FETCH_ASSOC);
                $total_capacity = $total_capacity+$areaData['capacity'];
                
                if ($areaData) {
                    $areaList[] = $areaData;  // 將查詢結果加入陣列
                }
            }
        } else {
            $sql_area_list = "SELECT id,name,capacity FROM area WHERE id = ?";
            $stmt_list = $db->prepare($sql_area_list);
            $stmt_list->bindParam(1, $_POST['area'], PDO::PARAM_INT);
        }
        $stmt_list->execute();
        $areas_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
    }
    if(isset($year)){
        $add_date_count = "SELECT max(DATE_FORMAT(add_date, '%Y-%m-%d')) AS add_date
                            FROM invertor_log
                            WHERE DATE_FORMAT(add_date, '%Y') = :st_date
                            GROUP BY DATE_FORMAT(add_date, '%Y')
                           "; // 搜尋筆數
        $stmt_invertor_count = $db->prepare($add_date_count);
        $stmt_invertor_count->bindParam(':st_date', $year, PDO::PARAM_STR);
        $stmt_invertor_count->execute();
        $add_date_count = $stmt_invertor_count->fetch(PDO::FETCH_ASSOC);
    }
    function Power_GenerationRows($year, $area_id)
    {
        global $db;
        $startDateTime = $year.'-01-01';
        $endDateTime = $year.'-12-31';
        //計算每一天的最後一筆發電量
        $sql_power_list =  "SELECT subquery.total_energy
                            FROM ( 
                                SELECT SUM(il.Energy_today) AS total_energy, il.add_date FROM invertor_log il JOIN sensor s ON il.invertor_id = s.sensor_type_id WHERE s.sensor_type = 'invertor' 
                                AND s.area_id = :area_id 
                                AND DATE(il.add_date) 
                                BETWEEN :startDate
                                AND :endDate 
                                AND TIME(il.add_date) <= '23:59:59' 
                                GROUP BY il.add_date 
                                ORDER BY il.add_date DESC ) 
                            AS subquery 
                            GROUP BY DATE(subquery.add_date) ";
        $stmt_power_list = $db->prepare($sql_power_list);
        $stmt_power_list->bindParam(':startDate', $startDateTime, PDO::PARAM_STR);
        $stmt_power_list->bindParam(':endDate', $endDateTime, PDO::PARAM_STR);
        $stmt_power_list->bindParam(':area_id', $area_id, PDO::PARAM_INT);
        $stmt_power_list->execute();
        $result = $stmt_power_list->fetchAll(PDO::FETCH_ASSOC);
        //$total_energy = isset($result['total_energy']) ? $result['total_energy'] : 0;
        $total_energy=0;
        if(count($result)>0){
            foreach ($result as $result_row) {
                $total_energy += $result_row['total_energy'];
            }
        }
        return $total_energy;
    }
    function Cumulative_SunshineRows($i, $date_day_hour, $area_id, $ex_sunshine, $ex_sunshine_add_date, $total_energy, $area_capacity)
    {
        global $db;
        $ex_date_day_hour = date("Y-m-d",strtotime($date_day_hour));
        $time = '23:59:59'; // 特定时间
        $date_day_hour = date('Y-m-d H:i:s', strtotime($date_day_hour . ' ' . $time ));
        $sql_power_list = " SELECT sum(pl.solar_irradiance) as solar_irradiance , pl.add_date
                            FROM pyranometer_log pl
                            JOIN sensor s ON s.sensor_type_id = pl.pyranometer_id
                            WHERE s.area_id = :area_id
                            AND pl.data_validity = 1
                            AND s.sensor_type='pyranometer'
                            AND pl.add_date <= :date_hour
                            AND pl.add_date >= :ex_date
                            GROUP by pl.add_date
                            ";
        $stmt_power_list = $db->prepare($sql_power_list);
        $stmt_power_list->bindParam(':date_hour', $date_day_hour, PDO::PARAM_STR);
        $stmt_power_list->bindParam(':ex_date', $ex_date_day_hour, PDO::PARAM_STR);
        $stmt_power_list->bindParam(':area_id', $area_id, PDO::PARAM_INT);
        $stmt_power_list->execute();
        $cumulative_sunshine_list = $stmt_power_list->fetchAll(PDO::FETCH_ASSOC);
        $ans = 0;   //總量
        $DMY = 0;   //日平均發電
        $PR = 0;
        $ex_sunshine = 0; //前一個日照量
        $ex_sunshine_add_date = 0;  //前一個時間
        $all_total_sunshine =0; //總日照量
        if(count($cumulative_sunshine_list)>0){
            foreach ($cumulative_sunshine_list as $sunshine_row) {
                if ($sunshine_row['solar_irradiance'] == 0 || $ex_sunshine==0) {
                    $total_sunshine = 0;
                } else {
                    $all_total_sunshine = ($sunshine_row['solar_irradiance']+$ex_sunshine-8)*125;
                    $total_sunshine = $all_total_sunshine*(strtotime($sunshine_row['add_date'])-strtotime($ex_sunshine_add_date))/2/3600/1000;
                }
                $ans = $ans + $total_sunshine;
                
                $ex_sunshine = $sunshine_row['solar_irradiance'];
                $ex_sunshine_add_date = $sunshine_row['add_date'];
            }
            return [$ans,$ex_sunshine,$ex_sunshine_add_date];
        }else{
            return [$ans,$ex_sunshine,$ex_sunshine_add_date];
        }
    }
?>
<style>
  .table{
    word-break: keep-all;
  }
</style>
<div class="content">
    <div class="container">
        <div class="page-title">
            <h3>統計報表-每年</h3>
        </div>       
        <!-- 切換按鈕 -->
        <div class="row justify-content-between justify-content-md-start m-0">
          <a type="button"  href="history-total-d.php" class="btn btn-outline-primary fs-5 col-auto">每日</a>
          <a type="button"  href="history-total-m.php" class="btn btn-outline-primary fs-5 col-auto">每月</a>
          <a type="button"  href="history-total-y.php" class="btn btn-outline-primary fs-5 col-auto active">每年</a>
        </div>
        <div class="col-lg-12">
              <div class="card-body">
                <!-- 查詢 -->
                <form method="post" action="history-total-y.php">
                      <div class="row">
                            <div class="col-md-4 mb-3 ">
                              <label for="area" class="form-label">區域</label>
                              <select name="area" id = "area" class="form-select" required>
                                  <option value="" selected>請選擇...</option>
                                  <option value="all" <?php echo $area == 'all' ? "selected" : ""; ?>>全部</option>
                                  <?php
                                    foreach ($areas as $area_row) {
                                      $selected = $area == $area_row["id"] ? "selected" : "";
                                      echo "<option value='{$area_row['id']}' $selected>{$area_row['name']}</option>";
                                    }
                                  ?>
                              </select>
                            </div>
                            <div class="col-md-4 mb-3 ">
                                    <label for="year" class="form-label"> 年份 </label>
                                    <select name="year" class="form-select" required>
                                        <option value="" selected>請選擇...</option>
                                        <?php
                                        $currentYear = date('Y');
                                        $startYear = 2023;  
                                        for ($selectedYear = $startYear; $selectedYear <= $currentYear; $selectedYear++) {
                                            $selected = ($selectedYear == $year) ? 'selected' : ''; // 檢查是否為所選年分
                                            echo '<option value="' . $selectedYear . '" ' . $selected . '>' . $selectedYear . '</option>';
                                        }
                                        ?>
                                    </select>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type='submit' class='btn btn-primary action_btn' id='search-btn'><i class="fas fa-search"></i> 查詢</button>
                        </div>
                </form>
                <?php if(!empty($area) && !empty($year) && !empty($add_date_count)){?>
                <?php //查詢結果資料 ?>
                <div class="page-title">
                    <h3>查詢結果</h3>
                </div>
                <div class="box box-primary">
                    <!-- 匯出  -->
                    <div class="text-end">
                      <button type='button' onclick="callPHPFunction()" class='btn btn-primary action_btn'><i class="fas fa-download"></i> 匯出</button> 
                    </div>
                    <?php //查詢結果資料 ?>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table  width="100%" class="table">
                                <tr>
                                      <th>#</th>
                                      <th>年份</th>
                                      <th>案場名稱</th>
                                      <th>容量(kWp)</th>
                                      <th>發電量(kWh)</th>
                                      <th>累積日照量(kWh/m<sup>2</sup>)</th>
                                      <th>PR(%)</th>
                                </tr>
                                <?php
                                    $ex_total_energy = 0;
                                    if ($_POST['area'] == 'all') {
                                    ?>
                                        <tr>
                                            <th>1</th>
                                            <th><?= $year ?></th>
                                            <th>全部</th>
                                            <th><?= $total_capacity ?></th>
                                            <th>
                                                <?php
                                                // 發電量(kWh)資訊
                                                $all_total_energy = 0;
                                                for ($ii = 0; $ii < count($areas); $ii++) {
                                                    $total_energy = Power_GenerationRows($year,($ii+1));
                                                    $all_total_energy =$all_total_energy + $total_energy;
                                                }
                                                ?>
                                                <?= $all_total_energy ?>
                                            </th>
                                            <th>
                                                <?php
                                                    $ex_sunshine =0;
                                                    $ex_sunshine_add_date =0;
                                                    $all_total_sunshine =0;
                                                    $all_total_sunshine1= 0;
                                                    $daysInYear =date('z');
                                                    for ($ii = 0; $ii < count($areas); $ii++) {
                                                        for ($i = 0; $i < $daysInYear; $i++) {
                                                            $startDateTime = $year.'-01-01';
                                                            $time = '23:59:59'; // 特定时间
                                                            $date_day_show = date('Y-m-d', strtotime('+' . $i . ' day', strtotime($startDateTime)));
                                                            $total_sunshine = Cumulative_SunshineRows($i, $date_day_show, ($ii+1), $ex_sunshine, $ex_sunshine_add_date, $total_energy, $total_capacity);
                                                            $ex_sunshine = $total_sunshine[1];
                                                            $ex_sunshine_add_date = $total_sunshine[2];
                                                            $all_total_sunshine =$all_total_sunshine + $total_sunshine[0];
                                                        }
                                                    }
                                                    $all_total_sunshine1 = $all_total_sunshine1 + $all_total_sunshine;
                                                    $all_total_sunshine1 = !empty($all_total_sunshine1) ? number_format(round($all_total_sunshine1, 4), 2) : 0;
                                                    $all_total_sunshine1 = !empty($all_total_sunshine1) ? str_replace(",","",$all_total_sunshine1) : 0;
                                                ?>
                                                <?= $all_total_sunshine1 ?>

                                            </th>
                                            <th>
                                                <?php
                                                $PR = 0;
                                                if(!empty($all_total_energy) && !empty($all_total_sunshine1)){
                                                    $PR = ((int)$all_total_energy/(int)$daysInYear/(float)$total_capacity/(float)$all_total_sunshine1)*100;
                                                    $PR  = number_format(round($PR, 4) , 2);
                                                    $PR = $PR!=0 ? $PR : 0;
                                                }
                                                ?>
                                                <?= $PR ?>
                                            </th>
                                        </tr>
                                <?php
                                } else {
                                ?>
                                        <tr>
                                            <th><?=  1 ?></th>
                                            <th><?= $year ?></th>
                                            <?php
                                            foreach ($areas_list as $area_row) {
                                            ?>
                                                <th><?= $area_row['name'] ?></th>
                                                <th><?= $area_row['capacity'] ?></th>
                                            <?php
                                            }
                                            ?>
                                            <th>
                                                <?php
                                                // 發電量(kWh)資訊
                                                $total_energy = Power_GenerationRows($year,$area);
                                                ?>
                                                <?= $total_energy ?>
                                            </th>
                                            <th>
                                                <?php
                                                    $ex_sunshine =0;
                                                    $ex_sunshine_add_date =0;
                                                    $all_total_sunshine =0;
                                                    $daysInYear =date('z');
                                                    for ($i = 0; $i < $daysInYear; $i++) {
                                                        $startDateTime = $year.'-01-01';
                                                        $date_day_show = date('Y-m-d', strtotime('+' . $i . ' day', strtotime($startDateTime)));
                                                        $total_sunshine = Cumulative_SunshineRows($i, $date_day_show, $area, $ex_sunshine, $ex_sunshine_add_date, $total_energy, $area_row['capacity']);
                                                        $ex_sunshine = $total_sunshine[1];
                                                        $ex_sunshine_add_date = $total_sunshine[2];
                                                        $all_total_sunshine =$all_total_sunshine + $total_sunshine[0];
                                                    }
                                                    $all_total_sunshine = !empty($all_total_sunshine) ? number_format(round($all_total_sunshine, 4), 2) : 0;
                                                    $all_total_sunshine = !empty($all_total_sunshine) ? str_replace(",","",$all_total_sunshine) : 0;
                                                ?>
                                                <?= $all_total_sunshine ?>
                                            </th>
                                            <th><?php
                                                $PR = 0;
                                                if(!empty($total_energy) && !empty($all_total_sunshine)){
                                                    $PR = (float)$total_energy/(float)$daysInYear/(float)$area_row['capacity']/(float)$all_total_sunshine*100;
                                                    $PR  = number_format(round($PR, 4) , 2);
                                                    $PR = $PR!=0 ? $PR : 0;
                                                }
                                                ?>
                                                <?= $PR ?>
                                            </th>
                                        </tr>
                                <?php
                                    }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <?php  
                    }elseif(!empty($area) && !empty($year) && empty(($add_date_count))){
                ?>
                    <div class="alert alert-danger" role="alert">查無資料，請重新查詢！</div>
                <?php
                    }
                ?>
              </div>
        </div>
        
    </div>
</div>
<!-- Loading -->
<div id="waitloading" class="w-100 d-none"></div>
<script src="./assets/js/loading.js"></script>
<script>
    $('#search-btn').click(function() {
        // 顯示 LoadingMask
        var  value1 = $('[name="area"]').val();
        var  value2 = $('[name="year"]').val();
        if(value1 !== "" && value2 !== ""){
            // 顯示 LoadingMask
            LoadingMask('#waitloading');
        }
        
    });
    function callPHPFunction() {
        var data = {
            value: 3,
            value1: $('[name="area"]').val(),
            value2: $('[name="year"]').val()
        };
        $.ajax({
            url: 'exceldownload_history.php', // 將請求發送到的 PHP 文件路径
            method: 'POST',
            data: data,
            beforeSend:function(){LoadingMask('#waitloading')},
            complete:function(){ClearMask('#waitloading','')},
            success: function(response) {
                // 创建 Blob 对象
                var blob = new Blob([response], { type: 'text/csv' });
                // 创建数据链接
                var downloadLink = document.createElement('a');
                downloadLink.href = window.URL.createObjectURL(blob);
                downloadLink.download = '統計報表(每年).csv'; // 下载文件的名称

                // 添加到页面并模拟点击
                document.body.appendChild(downloadLink);
                downloadLink.click();

                // 清理数据链接对象
                window.URL.revokeObjectURL(downloadLink.href);
                document.body.removeChild(downloadLink);
            },
            error: function(xhr, status, error) {
                console.error(response);
            }
        });
    }
</script>
<?php include('footer.php'); ?>