<?php

function send_booking_notification_email(array $booking, $recipient)
{
    $subject = 'Novo pedido de horário - PowerLift Gym';
    $body = implode("\r\n", [
        'Foi recebido um novo pedido de horário:',
        '',
        'Nome: ' . $booking['name'],
        'Email: ' . $booking['email'],
        'Telefone: ' . $booking['phone'],
        'Plano: ' . $booking['plan'],
        'Data: ' . $booking['date'],
        'Hora: ' . $booking['time'],
        'Recebido em: ' . $booking['created_at'],
    ]);
    $headers = [
        'From' => 'PowerLift Gym <PowerLiftGym0@gmail.com>',
        'Reply-To' => $booking['email'],
        'Content-Type' => 'text/plain; charset=UTF-8',
    ];
    $headerLines = [];
    foreach ($headers as $name => $value) {
        $headerLines[] = $name . ': ' . $value;
    }

    return mail(
        $recipient,
        $subject,
        $body,
        implode("\r\n", $headerLines)
    );
}
