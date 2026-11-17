<?php
/**
 * Local-friendly mail helper for XAMPP.
 * Tries PHP mail(), always stores a copy in mail_outbox + mailoutput folder.
 */
function sendAppEmail($to, $subject, $htmlBody) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO mail_outbox (recipient, subject, body) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $to, $subject, $htmlBody);
    $stmt->execute();
    $stmt->close();

    $mailDir = 'C:/xampp/mailoutput';
    if (!is_dir($mailDir)) {
        @mkdir($mailDir, 0777, true);
    }
    $file = $mailDir . '/otp_' . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9]/i', '_', $to) . '.eml';
    $raw = "To: {$to}\r\nSubject: {$subject}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}";
    @file_put_contents($file, $raw);

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Symbol Table MS <noreply@symboltable.local>\r\n";
    $headers .= "Reply-To: noreply@symboltable.local\r\n";

    $sent = @mail($to, $subject, $htmlBody, $headers);
    return [
        'sent' => (bool)$sent,
        'stored' => true,
        'file' => $file
    ];
}

function buildOtpEmail($name, $otp) {
    $safeName = htmlspecialchars($name ?: 'User');
    return "
    <div style='font-family:Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;background:#f4f7fb;border-radius:12px'>
      <div style='background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#fff;padding:20px;border-radius:10px;text-align:center'>
        <h2 style='margin:0'>Password Reset OTP</h2>
        <p style='margin:8px 0 0;opacity:.85'>Symbol Table Management System</p>
      </div>
      <div style='background:#fff;padding:24px;border-radius:10px;margin-top:16px'>
        <p>Hi <strong>{$safeName}</strong>,</p>
        <p>Use this One-Time Password to reset your account password. It expires in <strong>10 minutes</strong>.</p>
        <div style='font-size:32px;letter-spacing:8px;font-weight:700;text-align:center;padding:18px;background:#eef4ff;border-radius:10px;color:#0d6efd;margin:20px 0'>{$otp}</div>
        <p style='display:none'>OTP_CODE:{$otp}</p>
        <p style='color:#666;font-size:13px'>If you did not request this, you can ignore this email.</p>
      </div>
    </div>";
}
?>
