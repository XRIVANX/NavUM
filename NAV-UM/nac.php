<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nav UM</title>
    <link rel="stylesheet" href="nav.css">
</head>
<body>
    <div class="Logo-container">
        <img src="assets/img/logo.png" alt="University of Mindanao Tagum Logo" class="logo">
    </div>
    <div class="map">
    </div>
    <div class="rooms">
       <form action="umrooms.php" method="get">
        <select id="Room_id" name="room" onchange="this.form.submit()" >
                <?php include "umrooms.php"; ?>
          </select>
        </form>  
    </div>

</body>
</html>