<?php
session_start();
session_destroy();
header("Location: /smartqueue/index.php");
exit();