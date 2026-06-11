<?php
// Utilitaire d'envoi d'email avec PHPMailer (SMTP ou mail fallback)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

function sendResetMail($to, $toName, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        // Config SMTP (adapter selon hébergeur, sinon fallback mail)
        // $mail->isSMTP();
        // $mail->Host = 'smtp.example.com';
        // $mail->SMTPAuth = true;
        // $mail->Username = 'user@example.com';
        // $mail->Password = 'password';
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // $mail->Port = 587;
        // Si pas de SMTP, commenter ci-dessus et décommenter ci-dessous :
        $mail->isMail();

        $mail->setFrom('noreply@sazulis.fr', 'Sazulis');
        $mail->addAddress($to, $toName);
        $mail->Subject = 'Réinitialisation de votre mot de passe';
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(false);
        $mail->Body = "Bonjour $toName,\n\nPour réinitialiser votre mot de passe, cliquez sur ce lien :\n$resetLink\n\nCe lien est valable 1 heure. Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
