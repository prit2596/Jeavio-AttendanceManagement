<?php
require_once('dbconnect.php');
require_once('format.php');

  $Err="";
  if(isset($_POST['submitButton']))
  {
    $allowed=array('csv');
    $filename=$_FILES['csv_file']['name'];
    $ext=pathinfo($filename,PATHINFO_EXTENSION);
    if(!in_array($ext,$allowed))
    {
      $Err="File Format Not Supported!";
    }
    else
    {
      $targetDir="uploads/";
      $targetFile=$targetDir.basename($_FILES['csv_file']['name']);
      if(move_uploaded_file($_FILES['csv_file']['tmp_name'],$targetFile))
      {
        set_time_limit(0);
        $j=0;
        $file=fopen("uploads/".$filename,"r");
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
        $drop="DROP TABLE IF EXISTS `attendance`.`Employee_data`";
        $drop2="DROP TABLE IF EXISTS `attendance`.`attendaance_sheet`";
        $mysqli->query($drop);
        $mysqli->query($drop2);

        //creating table for data collected from excel file
        $sql="CREATE TABLE `attendance`.`Employee_data` ( `Employee_id` INT(100) UNSIGNED NOT NULL , `Emp_Name` VARCHAR(100) NOT NULL , `Date` DATE NOT NULL , `Time` TIME NOT NULL)";

        $mysqli->query($sql);

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
                  $dt=dtformat($date);
                  $tm=timeformat($time);
                  //echo $tm;
                  $raw_insertion="INSERT INTO `attendance`.`Employee_data` (`Employee_id`,`Emp_name`,`Date`,`Time`) VALUES ($id,'$name','$dt','$tm')";
                  if($mysqli->query($raw_insertion)===TRUE)
                  {
                    //echo "inserted";
                  }
                  else
                  {
                    echo "failed";
                  }
                }
                $i++;
              }
          }
        }
        //echo "end";
        // fetch the data from Employee_data so that insert  into attendance_sheet
        $getData="Select * from `attendance`.`Employee_data`";
        $result=$mysqli->query($getData);

        if($result->num_rows > 0)
        {
          while($rows=$result->fetch_assoc())
          {

            $id=$rows['Employee_id'];
            $name=$rows['Emp_Name'];
            $date=$rows['Date'];
            $time=$rows['Time'];
            //echo $id.$date."<br/>";
            $attendanceData="Select `In_Time` from `attendance`.`attendaance_sheet` WHERE `Emp_ID`='$id' AND `Date`='$date'";
            $result2=$mysqli->query($attendanceData);
            if($result2->num_rows > 0)
            {
                //echo $name.$date."inside<br/>";
                $row=$result2->fetch_assoc();
                $inTime=$row['In_Time'];
                //echo $inTime;
                $dur=duration($inTime,$time);

                $updateQuery="UPDATE `attendance`.`attendaance_sheet` SET `Out_Time` = '$time',`Duration`=$dur WHERE `Emp_ID`='$id' AND `Date`='$date'";
                $mysqli->query($updateQuery);

            }
            else
            {
              //echo $name.$date."<br/>";
              $dur=duration($time,'21:00:00');
              $insertQuery="INSERT INTO `attendance`.`attendaance_sheet`(`Id`,`Emp_ID`,`Emp_Name`,`Date`,`In_Time`,`Out_Time`,`Duration`) VALUES (NULL,$id,'$name','$date','$time', '21:00:00',$dur)";
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
        }

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
        header("Content-Disposition:attachment;filename=report.csv");
        fputcsv($output, array('Id','Name','Date','In_Time','Out_Time','Duration'));
        foreach($prod as $product) {
            fputcsv($output, $product);
        }
        fclose($output) or die("Can't close php://output");
        exit();
        $mysqli->close();

      }
      else
      {
        $Err="File Uploading Failed!!";
      }
    }
  }
?>
<html>
<head>
  <title>Jeavio</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>



</body>
  <div>
      <h1 class="center">Jeavio</h1>
      <hr>
      <div class="row grey">
        <div class="col s4 push-s2">
          <h4 class="text-left">Attendance Management</h4>
        </div>
      </div>
      <div class="row">
        <div class="col s5 push-s4">
          <h4 class="text-left">This is an Attendance Management Portal.The portal helps to download In/Out report of the Employees.Upload a <span class="red lighten-2">CSV</span> file and get the report of the employees.</h4>
        </div>
      </div>

      <div class="row">
        <div class="col s6 push-s3">
          <form action="#" method="post" enctype = "multipart/form-data">
            <div class="file-field input-field">
              <div class="btn">
                <span>File</span>
                <input type="file" name="csv_file">
              </div>
              <div class="file-path-wrapper">
                <input class="file-path validate" type="text">
              </div>
              <div class="col s3 push-s5">
                <input type="submit" class="btn orange" name="submitButton"/>
              </div>
              <br />
              <br />
              <h5><div class="red lighten-2 text-center" name="error" id="error"><?php echo $Err;?></div></h5>
            </div>
          </form>
        </div>
      </div>
  </div>

  <footer class="page-footer">
          <div class="footer-copyright">
            <div class="container">
              <div class="row">
                <div class="col s5">
                  © 2015 Copyright
                </div>
                <div class="col s2">
                  <a class="grey-text text-lighten-4 center" href="http://jeavio.com/"><h6>Jeavio</h6></a>
                </div>
                <div class="col s5">
                  <a class="grey-text text-lighten-4 right" href="mailto:info@jeavio.com">info@Jeavio.com <i class="fa fa-paper-plane"></i></a>
                </div>
              </div>

            </div>
          </div>
  </footer>

</html>
