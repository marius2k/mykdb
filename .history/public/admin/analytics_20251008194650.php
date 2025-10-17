<?php
// Redirect to the new articles_analytics.php file
header("Location: articles_analytics.php" . (isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) ? "?" . $_SERVER["QUERY_STRING"] : ""));
exit;
?>
