<?php
// includes/email_service.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once PROJECT_ROOT_PATH . '/lib/PHPMailer/Exception.php';
require_once PROJECT_ROOT_PATH . '/lib/PHPMailer/PHPMailer.php';
require_once PROJECT_ROOT_PATH . '/lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../email_config.php';

function enviar_notificacao_email($para_email, $para_nome, $assunto, $corpo_html) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($para_email, $para_nome);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo_html;
        $mail->CharSet = 'UTF-8';
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: Não foi possível enviar o e-mail. Erro: {$mail->ErrorInfo}");
        return false;
    }
}