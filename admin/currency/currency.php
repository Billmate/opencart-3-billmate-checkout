<?php
$conn=mysqli_connect("localhost", "kasw_admin_opencart", "admin@123", "kasw_opencart", 3306);
if(isset($_POST['currency-status'])!='' )
{
 
 $date = date('Y/m/d H:i:s');

$cur_status=$_POST['currency-status'];
if($cur_status==1)
{
  $select = "SELECT * FROM oc_currency  WHERE `code`='SEK'";
  $check_data=mysqli_query($conn,$select);
  $rowcount=mysqli_num_rows($check_data);
  if($rowcount==1)
  {
  $update = "UPDATE `oc_currency` SET `status` = '1' WHERE `code`='SEK'";
  $update_currency=mysqli_query($conn,$update);
  
  if($update_currency)
  {
  echo 'sucessfully updated the setting';
  }else{
  echo 'error';
  }
  }elseif($rowcount==0){
  $sql = "INSERT INTO `oc_currency` (`title`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `value`, `status`, `date_modified`) VALUES ('Swedish krona', 'SEK', 'kr', '', '1', '1', '1', '$date')";
  $insert=mysqli_query($conn,$sql);
  
  if($insert)
  {
  echo 'sucessfully Added the setting';
  }else{
  echo 'error';
  }
  }
  
}
  if($cur_status==0)
{
  $update = "UPDATE `oc_currency` SET `status` = '0' WHERE `code`='SEK'";
  $update_currency=mysqli_query($conn,$update);
  
  if($update_currency)
  {
  echo 'sucessfully updated the setting';
  }else{
  echo 'error';
  }
}
}

?>