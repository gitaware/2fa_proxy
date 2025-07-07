<?php

if (isset($_GET['logout'])) {
  session_start();
  $_SESSION = [];                // Clear session variables
  if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
          $params["path"], $params["domain"],
          $params["secure"], $params["httponly"]
      );
  }
  session_destroy();             // Fully destroy session
  header("Location: /application");
  exit;
}
echo "<h1>Welcome to the protected application!</h1>";
echo "<a href=\"?logout\">Log out</a>"
?>
