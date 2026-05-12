<?php



if (isset($_POST['testLocation'])) {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        echo $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IPs passed from proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Extract the first IP if multiple exist
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        echo trim($ip_list[0])."l";
    }
    echo $_SERVER['REMOTE_ADDR']."x";
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="" method="post">
        <button type="submit" name="testLocation">Test Location</button>
    </form>
</body>

</html>