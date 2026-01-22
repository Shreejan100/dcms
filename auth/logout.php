<?php
session_start();
session_destroy();
header("Location: /project/shreejandcms/dcms/index.php");
exit();
?>
