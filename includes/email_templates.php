<?php
// includes/email_templates.php (VERSÃO FINAL E COMPLETA)

/**
 * Cria o corpo HTML para um e-mail de notificação de novo comentário.
 */
function criar_corpo_email_comentario($nome_destinatario, $nome_comentarista, $texto_comentario, $id_chamado, $motivo_chamado) {
    $url_chamado = APP_URL . '/detalhes_chamado.php?id=' . $id_chamado;
    $url_logo = APP_URL . '/img/logo_contec.png';
    $nome_destinatario = htmlspecialchars($nome_destinatario);
    $nome_comentarista = htmlspecialchars($nome_comentarista);
    $motivo_chamado = htmlspecialchars($motivo_chamado);
    $texto_comentario_html = nl2br(htmlspecialchars($texto_comentario));
    $ano_atual = date('Y');
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Novo Comentário no Chamado #{$id_chamado}</title></head><body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;"><table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; margin-top: 20px; background-color: #ffffff; border: 1px solid #dddddd;"><tr><td align="center" style="padding: 20px 0; background-color: #004a91;"><img src="{$url_logo}" alt="Logo Contec" width="150" style="display: block;"></td></tr><tr><td style="padding: 40px 30px;"><h2 style="color: #333333; border-bottom: 2px solid #eeeeee; padding-bottom: 10px;">Novo Comentário no Chamado #{$id_chamado}</h2><p style="font-size: 16px; color: #555555; line-height: 1.5;">Olá, <strong>{$nome_destinatario}</strong>,</p><p style="font-size: 16px; color: #555555; line-height: 1.5;">Um novo comentário foi adicionado por <strong>{$nome_comentarista}</strong> no chamado sobre "<em>{$motivo_chamado}</em>".</p><table border="0" cellpadding="10" cellspacing="0" width="100%" style="background-color: #f9f9f9; border-left: 4px solid #004a91; margin-top: 20px; margin-bottom: 20px;"><tr><td style="font-size: 15px; color: #333333; line-height: 1.6;">{$texto_comentario_html}</td></tr></table><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" style="padding: 20px 0;"><a href="{$url_chamado}" style="background-color: #28a745; color: #ffffff; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">Ver Chamado</a></td></tr></table></td></tr><tr><td align="center" style="padding: 20px 30px; background-color: #333333; color: #ffffff; font-size: 12px;"><p>Esta é uma mensagem automática do Sistema de Chamados Contec.</p><p>&copy; {$ano_atual} Contec. Todos os direitos reservados.</p></td></tr></table></body></html>
HTML;
    return $html;
}

/**
 * Cria o corpo HTML para um e-mail de notificação de mudança de status.
 */
function criar_corpo_email_mudanca_status($nome_destinatario, $id_chamado, $motivo_chamado, $status_antigo, $status_novo) {
    $url_chamado = APP_URL . '/detalhes_chamado.php?id=' . $id_chamado;
    $url_logo = APP_URL . '/img/logo_contec.png';
    $nome_destinatario = htmlspecialchars($nome_destinatario);
    $motivo_chamado = htmlspecialchars($motivo_chamado);
    $status_antigo = htmlspecialchars($status_antigo);
    $status_novo = htmlspecialchars($status_novo);
    $ano_atual = date('Y');
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Atualização no Status do Chamado #{$id_chamado}</title></head><body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;"><table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; margin-top: 20px; background-color: #ffffff; border: 1px solid #dddddd;"><tr><td align="center" style="padding: 20px 0; background-color: #004a91;"><img src="{$url_logo}" alt="Logo Contec" width="150" style="display: block;"></td></tr><tr><td style="padding: 40px 30px;"><h2 style="color: #333333; border-bottom: 2px solid #eeeeee; padding-bottom: 10px;">Atualização no Chamado #{$id_chamado}</h2><p style="font-size: 16px; color: #555555; line-height: 1.5;">Olá, <strong>{$nome_destinatario}</strong>,</p><p style="font-size: 16px; color: #555555; line-height: 1.5;">O status do seu chamado sobre "<em>{$motivo_chamado}</em>" foi atualizado.</p><table border="0" cellpadding="10" cellspacing="0" width="100%" style="margin-top: 20px; margin-bottom: 20px; text-align: center;"><tr><td width="50%" style="font-size: 14px; color: #777;">STATUS ANTERIOR</td><td width="50%" style="font-size: 14px; color: #777;">NOVO STATUS</td></tr><tr><td style="font-size: 18px; font-weight: bold; color: #d9534f; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">{$status_antigo}</td><td style="font-size: 18px; font-weight: bold; color: #5cb85c; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">{$status_novo}</td></tr></table><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" style="padding: 20px 0;"><a href="{$url_chamado}" style="background-color: #28a745; color: #ffffff; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">Ver Detalhes do Chamado</a></td></tr></table></td></tr><tr><td align="center" style="padding: 20px 30px; background-color: #333333; color: #ffffff; font-size: 12px;"><p>Esta é uma mensagem automática do Sistema de Chamados Contec.</p><p>&copy; {$ano_atual} Contec. Todos os direitos reservados.</p></td></tr></table></body></html>
HTML;
    return $html;
}

/**
 * Cria o corpo HTML para um e-mail de notificação de novo chamado para a equipe de TI.
 */
function criar_corpo_email_novo_chamado($nome_agente_ti, $id_chamado, $motivo_chamado, $nome_solicitante) {
    $url_chamado = APP_URL . '/detalhes_chamado.php?id=' . $id_chamado;
    $url_logo = APP_URL . '/img/logo_contec.png';
    $nome_agente_ti = htmlspecialchars($nome_agente_ti);
    $motivo_chamado = htmlspecialchars($motivo_chamado);
    $nome_solicitante = htmlspecialchars($nome_solicitante);
    $ano_atual = date('Y');
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Novo Chamado Aberto: #{$id_chamado}</title></head><body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;"><table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; margin-top: 20px; background-color: #ffffff; border: 1px solid #dddddd;"><tr><td align="center" style="padding: 20px 0; background-color: #004a91;"><img src="{$url_logo}" alt="Logo Contec" width="150" style="display: block;"></td></tr><tr><td style="padding: 40px 30px;"><h2 style="color: #333333; border-bottom: 2px solid #eeeeee; padding-bottom: 10px;">Novo Chamado na Fila: #{$id_chamado}</h2><p style="font-size: 16px; color: #555555; line-height: 1.5;">Olá, <strong>{$nome_agente_ti}</strong>,</p><p style="font-size: 16px; color: #555555; line-height: 1.5;">Um novo chamado foi aberto por <strong>{$nome_solicitante}</strong> e precisa de atenção.</p><table border="0" cellpadding="10" cellspacing="0" width="100%" style="background-color: #f9f9f9; border-left: 4px solid #004a91; margin-top: 20px; margin-bottom: 20px;"><tr><td style="font-size: 15px; color: #333333; line-height: 1.6;"><strong>Motivo:</strong> {$motivo_chamado}</td></tr></table><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" style="padding: 20px 0;"><a href="{$url_chamado}" style="background-color: #007bff; color: #ffffff; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">Atender Chamado</a></td></tr></table></td></tr><tr><td align="center" style="padding: 20px 30px; background-color: #333333; color: #ffffff; font-size: 12px;"><p>Esta é uma mensagem automática do Sistema de Chamados Contec.</p><p>&copy; {$ano_atual} Contec. Todos os direitos reservados.</p></td></tr></table></body></html>
HTML;
    return $html;
}