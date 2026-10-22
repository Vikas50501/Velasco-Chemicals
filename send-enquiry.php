<?php

function respond($status, $data)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function requiredEnvironmentValue($name)
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException('Missing required environment variable: ' . $name);
    }

    return $value;
}

function textLength($value)
{
    return preg_match_all('/./us', $value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($contentType, 'application/json') !== 0) {
    respond(415, ['success' => false, 'message' => 'The request must use JSON.']);
}

$rawInput = file_get_contents('php://input');
if ($rawInput === false || strlen($rawInput) > 22000) {
    respond(413, ['success' => false, 'message' => 'The submitted enquiry is too large.']);
}

$input = json_decode($rawInput, true);
if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'The submitted enquiry is invalid.']);
}

$name = isset($input['name']) && is_string($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) && is_string($input['email']) ? trim($input['email']) : '';
$requirement = isset($input['requirement']) && is_string($input['requirement'])
    ? trim($input['requirement'])
    : '';
$company = isset($input['company']) && is_string($input['company']) ? trim($input['company']) : '';

if ($company !== '') {
    respond(400, ['success' => false, 'message' => 'The submitted enquiry is invalid.']);
}

if (
    $name === '' ||
    textLength($name) > 120 ||
    preg_match('/[\r\n]/', $name) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 254 ||
    textLength($requirement) < 10 ||
    textLength($requirement) > 5000
) {
    respond(422, ['success' => false, 'message' => 'Please check your name, email address, and requirement.']);
}

try {
    require __DIR__ . '/vendor/autoload.php';

    $host = getenv('MAIL_SMTP_HOST');
    if ($host === false || trim($host) === '') {
        $host = 'smtp.gmail.com';
    }
    $username = getenv('MAIL_SMTP_USERNAME');
    if ($username === false || trim($username) === '') {
        $username = 'yavish50501@gmail.com';
    }
    $username = trim($username);
    $password = requiredEnvironmentValue('MAIL_SMTP_PASSWORD');
    $fromAddress = getenv('MAIL_FROM_ADDRESS');
    if ($fromAddress === false || trim($fromAddress) === '') {
        $fromAddress = 'yavish50501@gmail.com';
    }
    $fromAddress = trim($fromAddress);
    $fromName = getenv('MAIL_FROM_NAME');
    $toAddress = getenv('MAIL_TO_ADDRESS');
    $port = getenv('MAIL_SMTP_PORT');
    $encryption = strtolower((string) getenv('MAIL_SMTP_ENCRYPTION'));

    if ($fromName === false || trim($fromName) === '') {
        $fromName = 'Velasco Chemicals';
    }
    if ($toAddress === false || trim($toAddress) === '') {
        $toAddress = 'velascochemicals@gmail.com';
    }
    if ($port === false || trim($port) === '') {
        $port = '587';
    }
    if ($encryption === '') {
        $encryption = 'tls';
    }
    if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || !filter_var($toAddress, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('MAIL_FROM_ADDRESS and MAIL_TO_ADDRESS must be valid email addresses.');
    }
    if (!ctype_digit((string) $port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('MAIL_SMTP_PORT must be a valid TCP port.');
    }
    if ($encryption !== 'tls' && $encryption !== 'ssl') {
        throw new RuntimeException('MAIL_SMTP_ENCRYPTION must be either tls or ssl.');
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = $encryption === 'ssl'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $port;
    $mail->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mail->setFrom($fromAddress, $fromName);
    $mail->addAddress(trim($toAddress));
    $mail->addReplyTo($email, $name);
    $mail->Subject = 'Website enquiry - Velasco Chemicals';
    $mail->isHTML(false);
    $mail->Body = "Name: {$name}\nEmail: {$email}\n\nRequirement:\n{$requirement}";
    $mail->send();

    respond(200, ['success' => true]);
} catch (Throwable $error) {
    error_log('Website enquiry email failed: ' . $error->getMessage());
    respond(500, ['success' => false, 'message' => 'We could not send your enquiry right now. Please try again later.']);
}
