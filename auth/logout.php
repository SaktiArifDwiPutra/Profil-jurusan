<?php
session_start();
session_unset();
session_destroy();
header('Location: /ProfilJurusan/auth/login.php');
exit;
