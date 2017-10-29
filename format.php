<?php
  function dtformat($date)
  {
    $date_pieces=explode("/",$date);
    $day=(string)$date_pieces[0];
    $month=(string)$date_pieces[1];
    $year=(string)$date_pieces[2];

    $formatted=$year."-".$month."-".$day;
    return $formatted;
  }

  function timeformat($time)
  {
    $time_pieces=explode(".",$time);
  //  $fo=$time_pieces[0].":".$time_pieces[1];
    $formatted=date("H:i",strtotime($time));
    return $formatted;
  }

  function duration($inTime,$outTime)
  {
      //print_r($inTime);
      $in_seconds=hmsToSeconds($inTime);
      //print_r($outTime);
      $out_seconds=hmsToSeconds($outTime);
      $dur=$out_seconds-$in_seconds;
      return $dur;
  }

  function hmsToSeconds ($hms) {
    $total_seconds = 0;
    list($hours,$minutes) = explode(":", $hms);
    $total_seconds += $hours * 60 * 60;
    $total_seconds += $minutes * 60;

    return $total_seconds;
  }

  function secondsToHms($seconds){
    $hours=floor($seconds / 3600);
    $remaining=$seconds-$hours*3600;
    $minutes=floor($remaining/60);
    $remaining=$remaining-$minutes*60;
    $value=$hours."Hrs ".$minutes."Min ";
    return $value;
  }

 ?>
