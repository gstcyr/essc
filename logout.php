<?
session_start();
session_unset();
session_destroy();
session_write_close();

header('location: login.php');
?><script>location.href='login.php'</script>