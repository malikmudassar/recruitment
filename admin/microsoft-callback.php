<?php
session_start();
include('db.php');
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client_secret = $_ENV['AZURE_CLIENT_SECRET'];
$client_id = $_ENV['AZURE_CLIENT_ID'];
$tenant = $_ENV['AZURE_TENANT_ID'];

 
$redirect_uri = 'https://api.cinergiedigital.com/recruitment/admin/microsoft-callback.php';
 
if (!isset($_GET['code'])) {
    die('Authorization code missing');
}
 
$token_url = "https://login.microsoftonline.com/$tenant/oauth2/v2.0/token";
 
$data = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    // 'scope' => 'openid profile email'
];
 
$options = [
    'http' => [
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];
 
$response = file_get_contents($token_url, false, stream_context_create($options));
$result = json_decode($response, true);
 
if (!isset($result['id_token'])) {
    die('Token exchange failed');
}
 
/* Decode JWT */
$parts = explode('.', $result['id_token']);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
 
$email = $payload['email'] ?? $payload['upn'] ?? null;
$name  = $payload['name'] ?? 'Admin';
 
// echo '<pre>'; print_r($payload); exit;

if (!$email) {
    die('Email not found in token');
}
 
/* OPTIONAL: restrict domain */
// if (!str_ends_with($email, '@yourcompany.com')) {
//     die('Access denied');
// }

$allowed_domains = ['@cinergiedigital.com'];
$allowed = false;
foreach ($allowed_domains as $domain) {
    if (str_ends_with($email, $domain)) {
        $allowed = true;
        break;
    }
}

$admins_list = [
    'umer@cinergiedigital.com',
    'mudassar.khani@cinergiedigital.com',
    'Mudassar.Khani@cinergiedigital.com',
    "luqman@cinergiedigital.com",
    "fayyaz@cinergiedigital.com"
];

if (!$allowed) {
    // die('Access denied: Unauthorized domain');
    header('Location: access-denied.php');
    exit;
}
 
// Determine role
$role = in_array(strtolower($email), $admins_list) ? 'admin' : 'hr';

$stmt = $conn->prepare("
    INSERT INTO admins (name, email, role, password, created_at)
    VALUES (:name, :email, :role, '', NOW())
    ON DUPLICATE KEY UPDATE
        name = :name,
        role = :role
");
$stmt->execute([
    'name'  => $name,
    'email' => $email,
    'role'  => $role
]);

/* Check DB */
$stmt = $conn->prepare("SELECT admin_id, name, role FROM admins WHERE email = :email");
$stmt->execute(['email' => $email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
 
if (!$admin) {
    // Auto signup
    die('Error fetching user from database.');
    // $stmt = $conn->prepare(
    //     "INSERT INTO admins (name, email, role, password)
    //      VALUES (:name, :email, 'admin', '')"
    // );
    // $stmt->execute(['name' => $name, 'email' => $email]);
 
    // $admin = [
    //     'admin_id' => $conn->lastInsertId(),
    //     'name' => $name,
    //     'role' => 'admin'
    // ];
}
 
/* Login */
session_regenerate_id(true);
$_SESSION['admin_id'] = $admin['admin_id'];
$_SESSION['admin_name'] = $admin['name'];
$_SESSION['admin_role'] = $admin['role'];
 
header('Location: index.php');
exit;
?>