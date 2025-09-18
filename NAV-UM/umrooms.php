<?php
$conn=mysqli_connect('localhost','root','','umroom');
if(!$conn){
    die("Connection failed:" .mysqli_connect_error());
}
else{
    echo"Success Connection";
}

$sql="SELECT ROOM_No FROM rooms";
$result=$conn->query($sql);

if($result->num_rows>0){
    while($row=$result->fetch_assoc())
    {echo "<option value='" . $row['ROOM_No'] . "'>" . $row['ROOM_No'] . "</option>";
    }}
?>