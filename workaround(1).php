<?php
  require_once('dbconnect.php');
  require_once('format.php');

 ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Attendance Management System</title>
  </head>
  <body>

  </body>
</html>

<?php
  set_time_limit(0);
  $j=0;
  $file=fopen("rawdata jeavio.csv","r");
  while(($f=fgetcsv($file,1000,","))!==false)
  {
      $num=count($f);
      for($i=0;$i<$num;$i++)
      {
        $q[$j]=$f[$i];                    // $q stores the each cell collected from the excel file
        //echo $q[$j]."<br/>";
        $j++;

      }
  }
  fclose($file);

  //Drop the existing table as everytime it uses the same table with new content.
  //$drop="DROP TABLE IF EXISTS `attendance`.`Employee_data`";
  $drop="DROP TABLE IF EXISTS `attendance`.`attendaance_sheet`";
  //$mysqli->query($drop);
  $mysqli->query($drop);

  //creating table for data collected from excel file
  //$sql="CREATE TABLE `attendance`.`Employee_data` ( `Employee_id` INT(100) UNSIGNED NOT NULL , `Emp_Name` VARCHAR(100) NOT NULL , `Date` DATE NOT NULL , `Time` TIME NOT NULL)";

  //$mysqli->query($sql);

  //creating table of attendance_sheet
  $attendance_table="CREATE TABLE `attendance`.`attendaance_sheet` ( `Id` INT NOT NULL AUTO_INCREMENT , `Emp_ID` INT NOT NULL , `Emp_Name` VARCHAR(100) NOT NULL , `Date` DATE NOT NULL , `In_Time` TIME NOT NULL , `Out_Time` TIME NOT NULL , `Duration` BIGINT NOT NULL , PRIMARY KEY (`Id`))";
  $mysqli->query($attendance_table);
  // Regular expression /[0-9][0-9][0-9 ]?[,][A-Z a-z]+/g for matching name

  $i=0;
  while(!preg_match("/[0-9][0-9][0-9]?[,][A-Z a-z]+/",$q[$i]))
  {
    $i++;
  }

  while($i<$j)
  {
    if(preg_match("/[0-9][0-9][0-9]?[,][A-Z a-z]+/",$q[$i]))
    {
        $piece=$q[$i];

        $id_name=explode(",",$piece);
        $id=intval($id_name[0]);
        $name=$id_name[1];
        $name=trim($name);  //removing white space if present from front
        //echo $id.$name."<br/>";
        $i++;
        while($i<$j && (!preg_match("/[0-9][0-9][0-9]?[,][A-Z a-z]+/",$q[$i])))
        {
          if(preg_match("/(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d/",$q[$i]))
          {
            $date=$q[$i];
            $i++;
            $time=$q[$i];
            //echo $date.$time."</br>";
            $date=dtformat($date);
            $time=timeformat($time);


            $attendanceData="Select `In_Time` from `attendance`.`attendaance_sheet` WHERE `Emp_ID`='$id' AND `Date`='$date'";
            $result2=$mysqli->query($attendanceData);
            if($result2->num_rows > 0)
            {
                //echo $name.$date."inside<br/>";
                $row=$result2->fetch_assoc();
                $inTime=$row['In_Time'];
                //echo $inTime;
                $dur=duration($inTime,$time);
                echo "before update";
                $updateQuery="UPDATE `attendance`.`attendaance_sheet` SET `Out_Time` = '$time',`Duration`=$dur WHERE `Emp_ID`='$id' AND `Date`='$date'";
                $mysqli->query($updateQuery);

            }
            else
            {
              echo "before insert";
              //echo $name.$date."<br/>";
              $dur=duration($time,'21:00:00');
              $insertQuery="INSERT INTO `attendance`.`attendaance_sheet`(`Id`,`Emp_ID`,`Emp_Name`,`Date`,`In_Time`,`Out_Time`,`Duration`) VALUES (NULL,$id,'$name','$date','$time','21:00:00',$dur)";
              if($mysqli->query($insertQuery)===TRUE)
              {
                //echo "inserted <br/>";
              }
              else
              {
                echo "failed <br/>";
              }
            }

          }
          $i++;
        }
    }
  }
  //echo "end";

  $num = 0;
  $sql = "SELECT * FROM `attendance`.`attendaance_sheet`";
  if($result = $mysqli->query($sql)) {
       while($p = $result->fetch_array()) {
           $prod[$num]['Emp_ID']          = $p['Emp_ID'];
           $prod[$num]['Emp_Name']        = $p['Emp_Name'];
           $prod[$num]['Date']            = $p['Date'];
           $prod[$num]['In_Time']         =$p['In_Time'];
           $prod[$num]['Out_Time']        =$p['Out_Time'];
           $prod[$num]['Duration']        =secondsToHms($p['Duration']);
           $num++;
      }
   }
  $output = fopen("php://output",'w') or die("Can't open php://output");
  header("Content-Type:application/csv");
  header("Content-Disposition:attachment;filename=pressurecsv.csv");
  fputcsv($output, array('Id','Name','Date','In_Time','Out_Time','Duration'));
  foreach($prod as $product) {
      fputcsv($output, $product);
  }
  fclose($output) or die("Can't close php://output");

  $mysqli->close();

 ?>
