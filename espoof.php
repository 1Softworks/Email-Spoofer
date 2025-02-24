<?php
function spoofEmail($to, $from_email, $from_name, $subject, $message, $additional_headers = array(), $attachments = array()) {
    $boundary = md5(uniqid(time()));
    $boundary_alt = md5(uniqid(time()) . 'alt');
    
    $headers = array(
        'MIME-Version: 1.0',
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'X-Mailer: PHP/' . phpversion(),
        'Return-Path: ' . $from_email,
        'Message-ID: <' . time() . '-' . md5($from_email . $to . uniqid()) . '@' . $_SERVER['SERVER_NAME'] . '>',
        'Date: ' . date('r', time()),
        'X-Priority: 1',
        'X-MSMail-Priority: High',
        'Importance: High',
        'X-Sender: ' . $from_email,
        'X-Originating-IP: ' . $_SERVER['SERVER_ADDR'],
        'DKIM-Signature: v=1; a=rsa-sha256; d=' . $_SERVER['SERVER_NAME'] . '; s=selector' . time() . '; h=from:to:subject:date; bh=' . base64_encode(hash('sha256', $message, true)),
        'Authentication-Results: ' . $_SERVER['SERVER_NAME'] . '; dkim=pass; spf=pass',
        'Received: from ' . $_SERVER['SERVER_NAME'] . ' (localhost [' . $_SERVER['SERVER_ADDR'] . ']) by ' . $_SERVER['SERVER_NAME'] . ' with SMTP id ' . uniqid(),
        'SPF: pass (' . $_SERVER['SERVER_NAME'] . ': domain of ' . $from_email . ' designates ' . $_SERVER['SERVER_ADDR'] . ' as permitted sender)'
    );
    
    $headers = array_merge($headers, $additional_headers);
    
    $message_body = "--" . $boundary . "\r\n";
    $message_body .= "Content-Type: multipart/alternative; boundary=\"" . $boundary_alt . "\"\r\n\r\n";
    
    $message_body .= "--" . $boundary_alt . "\r\n";
    $message_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message_body .= chunk_split(base64_encode(strip_tags($message))) . "\r\n";
    
    $message_body .= "--" . $boundary_alt . "\r\n";
    $message_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message_body .= chunk_split(base64_encode($message)) . "\r\n";
    
    $message_body .= "--" . $boundary_alt . "--\r\n";
    
    foreach($attachments as $attachment) {
        if(file_exists($attachment['path'])) {
            $content = file_get_contents($attachment['path']);
            $message_body .= "--" . $boundary . "\r\n";
            $message_body .= "Content-Type: " . $attachment['type'] . "; name=\"" . $attachment['name'] . "\"\r\n";
            $message_body .= "Content-Disposition: attachment; filename=\"" . $attachment['name'] . "\"\r\n";
            $message_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message_body .= chunk_split(base64_encode($content)) . "\r\n";
        }
    }
    
    $message_body .= "--" . $boundary . "--";
    
    $headers_str = implode("\r\n", $headers);
    $parameters = '-f ' . $from_email;
    
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid recipient email address');
    }
    
    if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid sender email address');
    }
    
    try {
        if(mail($to, $subject, $message_body, $headers_str, $parameters)) {
            return array(
                'success' => true,
                'message' => 'Email sent successfully',
                'details' => array(
                    'to' => $to,
                    'from' => $from_email,
                    'subject' => $subject,
                    'timestamp' => time(),
                    'message_id' => $headers['Message-ID'],
                    'attachments' => count($attachments)
                )
            );
        }
        throw new Exception('Failed to send email');
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        );
    }
}

$to = "recipient@example.com";
$from_email = "spoof@example.com";
$from_name = "Spoofed Sender";
$subject = "Important Security Update " . date('Y-m-d H:i:s');
$message = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Security Update</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f9f9f9;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
        <header style='background-color: #2c3e50; padding: 20px; border-radius: 6px 6px 0 0;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>Security Alert</h1>
        </header>
        <div style='padding: 20px;'>
            <p style='margin-bottom: 20px; font-size: 16px;'>Critical security update required for your account.</p>
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #2c3e50;'>
                <p style='margin: 0; font-weight: bold;'>Action Required:</p>
                <p style='margin: 10px 0 0 0;'>Please verify your account security settings immediately.</p>
            </div>
            <div style='text-align: center; margin-top: 30px;'>
                <a href='#' style='background-color: #2c3e50; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Verify Account</a>
            </div>
        </div>
        <footer style='text-align: center; padding: 20px; color: #666; font-size: 12px; border-top: 1px solid #eee;'>
            <p>This is an automated security notification. Please do not reply to this email.</p>
        </footer>
    </div>
</body>
</html>";

$additional_headers = array(
    'List-Unsubscribe: <mailto:unsubscribe@example.com>, <https://example.com/unsubscribe>',
    'Precedence: bulk',
    'X-Auto-Response-Suppress: All',
    'X-Report-Abuse: Please report abuse here: abuse@example.com',
    'X-Spam-Status: No',
    'X-Spam-Score: 0.0',
    'X-Spam-Level: ',
    'X-Spam-Flag: NO'
);

$attachments = array(
    array(
        'path' => 'security_report.pdf',
        'name' => 'Security_Report_' . date('Y-m-d') . '.pdf',
        'type' => 'application/pdf'
    )
);

$result = spoofEmail($to, $from_email, $from_name, $subject, $message, $additional_headers, $attachments);
echo json_encode($result, JSON_PRETTY_PRINT);
?>
