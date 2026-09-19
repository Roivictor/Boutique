<?php
session_start();
require_once 'config/database.php';

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit();
?>