<?php
session_start();
require_once _DIR_ . '/../db/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: livres.php");
    exit();
}

$livre_id = $_GET['id'];
$stmt = $conn->prepare("SELECT fichier_pdf, titre FROM livres WHERE id = ?");
$stmt->execute([$livre_id]);
$livre = $stmt->fetch();

if(!$livre || empty($livre['fichier_pdf'])) {
    $_SESSION['error'] = "Fichier PDF non disponible";
    header("Location: livres.php");
    exit();
}

$filepath = 'pdfs/' . $livre['fichier_pdf'];

if(file_exists($filepath)) {
    // Vérifier que le fichier est bien dans le dossier pdfs (sécurité)
    if(strpos(realpath($filepath), realpath('pdfs/')) === 0) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($livre['titre']).'.pdf"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $_SESSION['error'] = "Accès non autorisé";
        header("Location: livres.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Fichier introuvable";
    header("Location: livres.php");
    exit;
}