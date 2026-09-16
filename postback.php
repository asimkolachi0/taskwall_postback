<?php
// ========== SETTINGS - Yahan apni values dalo ==========
$MY_PASSWORD = "BulitApp99"; // Taskwall me bhi yahi password lagana hai
$PROJECT_ID = "offerwall99-ff197";
$FIREBASE_SECRET = "9PMjaCZVNK9JfCtIBH9u2mXPKExiufmx9OTx9vrd"; // Firebase > Project Settings > Database Secrets se lo
// ========================================================

// Taskwall se aane wala data
$userid = $_GET['userid'] ?? '';
$user_amount = intval($_GET['user_amount'] ?? 0);
$offer_id = $_GET['offer_id'] ?? '';
$offer_name = $_GET['offer_name'] ?? '';
$payout = $_GET['payout'] ?? '';
$ip_address = $_GET['ip_address'] ?? '';
$password = $_GET['password'] ?? '';

// 1. Password check
if (empty($userid) || $password !== $MY_PASSWORD) {
    http_response_code(403);
    die("Invalid password or userid");
}

// Agar amount 0 hai to galat hai
if ($user_amount <= 0) {
    die("Invalid amount");
}

$BASE_URL = "https://$PROJECT_ID-default-rtdb.firebaseio.com";

// Firebase ke liye helper functions
function fb_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function fb_put($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// 2. Duplicate check - ek hi offer dobara credit na ho
$historyUrl = "$BASE_URL/users/$userid/history/$offer_id.json?auth=$FIREBASE_SECRET";
$history = fb_get($historyUrl);

if ($history !== "null" && $history !== "" && $history !== false && $history !== null) {
    die("1 - Already credited");
}

// 3. User exist karta hai ya nahi check karo
$userCheckUrl = "$BASE_URL/users/$userid.json?auth=$FIREBASE_SECRET";
$userCheck = fb_get($userCheckUrl);
if ($userCheck === "null" || $userCheck === false) {
    http_response_code(404);
    die("User not found");
}

// 4. Current points lo
$pointsUrl = "$BASE_URL/users/$userid/points.json?auth=$FIREBASE_SECRET";
$currentPointsRaw = fb_get($pointsUrl);
$currentPoints = intval($currentPointsRaw);

// 5. Naye points calculate karo
$newPoints = $currentPoints + $user_amount;

// 6. Points update karo
fb_put($pointsUrl, $newPoints);

// 7. History save karo taake duplicate na ho
$historyData = [
    "offer_name" => $offer_name,
    "payout" => $payout,
    "user_amount" => $user_amount,
    "ip" => $ip_address,
    "time" => time()
];
fb_put($historyUrl, $historyData);

// 8. Taskwall ko 1 return karo - iska matlab success hai
echo "1";
?>
