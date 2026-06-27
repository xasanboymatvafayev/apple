<?php
// BOTNING DASTURCHISI: @ShamshodbekDev
define('BOT_TOKEN', '8616010952:AAFhOYNeoWQgVmKcumWnnvaJMdj1dOx28k4'); // Bot token joyi
define('ADMIN_ID', '6365371142'); // Admin id yozing

define('USERS_FILE', 'users.txt');
define('CHANNELS_FILE', 'channels.txt');
define('IMAGES_DIR', 'images/');
define('STATS_FILE', 'stats.txt');

if (!file_exists(IMAGES_DIR)) {
    mkdir(IMAGES_DIR, 0755, true);
}
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, '{}');
}
if (!file_exists(CHANNELS_FILE)) {
    file_put_contents(CHANNELS_FILE, '');
}
if (!file_exists(STATS_FILE)) {
file_put_contents(STATS_FILE, json_encode(['bugun_signallar' => 0, 'last_reset' => date('Y-m-d')]));
}

function bot_sendMessage($chat_id, $text, $reply_markup = null) {
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";

$data = [
'chat_id' => $chat_id,
'text' => $text,
'parse_mode' => 'HTML'
];

if ($reply_markup) {
$data['reply_markup'] = json_encode($reply_markup);
}

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => $url,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => $data,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
curl_close($ch);

return $response;
}

function bot_deleteMessage($chat_id, $message_id) {
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage";
    
$data = [
'chat_id' => $chat_id,
'message_id' => $message_id
];

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => $url,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => $data,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);
curl_close($ch);

return $response;
}

function bot_answerCallbackQuery($callback_query_id, $text = '', $show_alert = false) {
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";

$data = [
'callback_query_id' => $callback_query_id
];

if (!empty($text)) {
$data['text'] = $text;
}

if ($show_alert) {
$data['show_alert'] = $show_alert;
}

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => $url,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => $data,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);
curl_close($ch);

return $response;
}
// DASTURCHI: @ShamshodbekDev
function bot_sendPhoto($chat_id, $photo_path, $caption = '', $reply_markup = null) {
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";

if (!file_exists($photo_path)) {
return bot_sendMessage($chat_id, $caption, $reply_markup);
}

$data = [
'chat_id' => $chat_id,
'photo' => new CURLFile(realpath($photo_path)),
'caption' => $caption,
'parse_mode' => 'HTML'
];

if ($reply_markup) {
$data['reply_markup'] = json_encode($reply_markup);
}

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => $url,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => $data,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
curl_close($ch);

return $response;
}

function get_channels() {
$channels = [];
if (file_exists(CHANNELS_FILE) && filesize(CHANNELS_FILE) > 0) {
$lines = file(CHANNELS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
$parts = explode('|', $line, 2); // separator ni o'zgartirdim
if (count($parts) == 2) {
$channels[] = [
'username' => trim($parts[0]),
'name' => trim($parts[1])
];
}
}
}
return $channels;
}

function check_subscription($user_id) {
$channels = get_channels();
if (empty($channels)) return true;

foreach ($channels as $channel) {
$channel_id = str_replace('@', '', $channel['username']);
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getChatMember";
$data = [
'chat_id' => '@' . $channel_id,
'user_id' => $user_id
];

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => $url,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => $data,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) continue;

$result = json_decode($response, true);

if (!$result || !isset($result['ok']) || !$result['ok']) {
continue;
}

$status = $result['result']['status'];
if ($status == 'left' || $status == 'kicked') {
return false;
}
}
return true;
}

// Signal generatsiya qilish
function generate_signal() {
return rand(1, 5);
}

function update_stats($type = 'signal') {
$stats = [];
if (file_exists(STATS_FILE)) {
$content = file_get_contents(STATS_FILE);
$stats = json_decode($content, true) ?? [];
}

$today = date('Y-m-d');
if (!isset($stats['last_reset']) || $stats['last_reset'] != $today) {
$stats['bugun_signallar'] = 0;
$stats['last_reset'] = $today;
}

if ($type == 'signal') {
$stats['bugun_signallar'] = ($stats['bugun_signallar'] ?? 0) + 1;
}

file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
return $stats;
}

function save_user($user_id, $username, $first_name) {
$users = [];
if (file_exists(USERS_FILE) && filesize(USERS_FILE) > 0) {
$content = file_get_contents(USERS_FILE);
$users = json_decode($content, true) ?? [];
}

$users[$user_id] = [
'username' => $username,
'first_name' => $first_name,
'subscribed' => false,
'joined_at' => date('Y-m-d H:i:s'),
'last_active' => date('Y-m-d H:i:s')
];

return file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}

function update_user_subscription($user_id, $subscribed) {
$users = [];
if (file_exists(USERS_FILE) && filesize(USERS_FILE) > 0) {
$content = file_get_contents(USERS_FILE);
$users = json_decode($content, true) ?? [];
}
// DASTURCHI: @ShamshodbekDev
if (isset($users[$user_id])) {
$users[$user_id]['subscribed'] = $subscribed;
$users[$user_id]['last_active'] = date('Y-m-d H:i:s');
return file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}
return false;
}

function get_statistics() {
$users = [];
if (file_exists(USERS_FILE) && filesize(USERS_FILE) > 0) {
$content = file_get_contents(USERS_FILE);
$users = json_decode($content, true) ?? [];
}

$total_users = count($users);
$subscribed_users = 0;

foreach ($users as $user) {
if (isset($user['subscribed']) && $user['subscribed']) {
$subscribed_users++;
}
}

$stats = [];
if (file_exists(STATS_FILE)) {
$content = file_get_contents(STATS_FILE);
$stats = json_decode($content, true) ?? [];
}

return [
'total_users' => $total_users,
'subscribed_users' => $subscribed_users,
'bugun_signallar' => $stats['bugun_signallar'] ?? 0
];
}

function broadcast_message($message) {
$users = [];
if (file_exists(USERS_FILE) && filesize(USERS_FILE) > 0) {
$content = file_get_contents(USERS_FILE);
$users = json_decode($content, true) ?? [];
}
    
$sent = 0;
$failed = 0;

foreach ($users as $user_id => $user) {
if (isset($user['subscribed']) && $user['subscribed']) {
$response = bot_sendMessage($user_id, $message);
if ($response) {
$result = json_decode($response, true);
if ($result && isset($result['ok']) && $result['ok']) {
$sent++;
} else {
$failed++;
}
} else {
$failed++;
}
usleep(300000); // 0.3 soniya kutish
}
}

return ['sent' => $sent, 'failed' => $failed];
}

function add_channel($username, $name) {
$line = $username . '|' . $name . PHP_EOL; // separator ni o'zgartirdim
return file_put_contents(CHANNELS_FILE, $line, FILE_APPEND | LOCK_EX) !== false;
}

function remove_channel($channel_name) {
$channels = get_channels();
$new_channels = [];
$found = false;

foreach ($channels as $channel) {
if ($channel['name'] == $channel_name) {
$found = true;
continue;
}
$new_channels[] = $channel;
}

if (!$found) return false;

$content = '';
foreach ($new_channels as $channel) {
$content .= $channel['username'] . '|' . $channel['name'] . PHP_EOL; // separator ni o'zgartirdim
}

return file_put_contents(CHANNELS_FILE, $content, LOCK_EX) !== false;
}

function get_main_keyboard() {
return [
'keyboard' => [
[['text' => '🍎 KEYINGI SIGNAL 🍎']]
],
'resize_keyboard' => true,
'one_time_keyboard' => false
];
}

function get_admin_keyboard() {
return [
'keyboard' => [
[['text' => '📊 Statistika'], ['text' => '📨 Xabar yuborish']],
[['text' => '➕ Kanal qo\'shish'], ['text' => '➖ Kanal o\'chirish']],
[['text' => '📋 Kanallar ro\'yxati'], ['text' => '🏠 Asosiy menyu']]
],
'resize_keyboard' => true,
'one_time_keyboard' => false
];
}

function get_back_keyboard() {
return [
'keyboard' => [
[['text' => '🔙 Orqaga']]
],
'resize_keyboard' => true,
'one_time_keyboard' => true
];
}

function send_signal($chat_id, $user_id) {
$is_subscribed = check_subscription($user_id);

if (!$is_subscribed) {
bot_sendMessage($chat_id, 
"❌ Signal olish uchun avval barcha kanallarga obuna bo'ling!",
get_main_keyboard()
);
return;
}
// DASTURCHI: @ShamshodbekDev
update_user_subscription($user_id, true);
update_stats('signal');

$signal_number = generate_signal();
$photo_path = IMAGES_DIR . "signal" . $signal_number . ".jpg";

$caption = "🎯 <b>APPLE FORTUNA SIGNAL</b> 🎯\n\n" .
"📡 <b>Signal:</b> " . $signal_number . "-qatorni tanlang!\n" .
"➖➖➖➖➖➖➖➖➖➖➖\n" .
"⚠️ <b>Diqqat:</b> Bizning silkamizdan foydalanmasangiz, signal ishlamasligi mumkin!\n\n" .
"📱 <b>1XBET: https://reffpa.com/L?tag=d_5653256m_1599c_&site=5653256&ad=1599\n\n" .
"🔰 <b>Faqat shu silka orqali royxatdan otib kamida 50.000 som depozit qiling";

if (file_exists($photo_path)) {
bot_sendPhoto($chat_id, $photo_path, $caption, get_main_keyboard());
} else {
bot_sendMessage($chat_id, $caption, get_main_keyboard());
}
}

function set_admin_mode($user_id, $mode) {
$mode_file = "admin_mode_{$user_id}.txt";
return file_put_contents($mode_file, $mode) !== false;
}

function get_admin_mode($user_id) {
$mode_file = "admin_mode_{$user_id}.txt";
if (file_exists($mode_file)) {
return file_get_contents($mode_file);
}
return '';
}

function clear_admin_mode($user_id) {
$mode_file = "admin_mode_{$user_id}.txt";
if (file_exists($mode_file)) {
return unlink($mode_file);
}
return true;
}

$input = file_get_contents('php://input');
if (empty($input)) {
echo "Bot ishga tushdi!";
exit;
}

$update = json_decode($input, true);
if (!$update) {
exit;
}

if (isset($update['message'])) {
$message = $update['message'];
$chat_id = $message['chat']['id'];
$user_id = $message['from']['id'];
$username = $message['from']['username'] ?? '';
$first_name = $message['from']['first_name'] ?? '';
$text = $message['text'] ?? '';
$message_id = $message['message_id'] ?? '';

save_user($user_id, $username, $first_name);

if ($text == '/start') {
$channels = get_channels();

if (empty($channels)) {
update_user_subscription($user_id, true);
bot_sendMessage($chat_id, 
"🤖 <b>Apple Fortuna Signal Botiga xush kelibsiz!</b>\n\n" .
"📡 Signal olish uchun pastdagi tugmani bosing!",
get_main_keyboard()
);

} else {
$inline_keyboard = [];
foreach ($channels as $channel) {
$inline_keyboard[] = [
[
'text' => '📢 ' . $channel['name'], 
'url' => 'https://t.me/' . str_replace('@', '', $channel['username'])
]
];
}
$inline_keyboard[] = [
[
'text' => '✅ Obuna bo\'ldim', 
'callback_data' => 'check_subscription'
]
];

$channels_text = "UZ🇺🇿: 🔔 Iltimos, kanalga obuna bo‘ling!

👇 Kanalga kirish uchun pastdagi tugmani bosing:.
➖➖➖➖➖➖➖➖➖➖➖\n\n";

foreach ($channels as $index => $channel) {
$channels_text .= ($index + 1) . ". " . $channel['name'] . " - " . $channel['username'] . "\n";
}


bot_sendMessage($chat_id, $channels_text, [
'inline_keyboard' => $inline_keyboard
]);
}
}

elseif ($text == '🍎 KEYINGI SIGNAL 🍎' || $text == '🍎KEYINGI SIGNAL🍎') {
send_signal($chat_id, $user_id);
}

elseif ($text == '/panel' && $user_id == ADMIN_ID) {
bot_sendMessage($chat_id, 
"👨‍💻 <b>Admin paneliga xush kelibsiz!</b>\n\n" .
"Quyidagi tugmalardan birini tanlang:",
get_admin_keyboard()
);
}

elseif ($user_id == ADMIN_ID) {
$admin_mode = get_admin_mode($user_id);

if ($admin_mode) {
switch ($admin_mode) {
case 'broadcast':
$result = broadcast_message($text);
bot_sendMessage($chat_id, 
"✅ <b>Xabar yuborildi!</b>\n\n" .
"Yuborilgan: " . $result['sent'] . "\n" .
"Xatolik: " . $result['failed'],
get_admin_keyboard()
);
clear_admin_mode($user_id);
break;

case 'add_channel':
$parts = explode(' ', $text, 2);
if (count($parts) == 2 && strpos($parts[0], '@') === 0) {
$username = trim($parts[0]);
$name = trim($parts[1]);

if (add_channel($username, $name)) {
bot_sendMessage($chat_id, 
"✅ <b>Kanal muvaffaqiyatli qo'shildi!</b>\n\n" .
"Nomi: " . $name . "\n" .
"Username: " . $username,
get_admin_keyboard()
);
} else {
bot_sendMessage($chat_id, 
"❌ Kanal qo'shishda xatolik!", 
get_admin_keyboard()
);
}
} else {
bot_sendMessage($chat_id, 
"❌ <b>Noto'g'ri format!</b>\n\n" .
"Iltimos, quyidagi formatda yuboring:\n" .
"<code>@shamshodbekh Kanal Nomi</code>",
get_back_keyboard()
                        );
}
clear_admin_mode($user_id);
break;
}
} else {
switch ($text) {
case '📊 Statistika':
$stats = get_statistics();
$message = "📊 <b>Bot Statistikalari</b>\n\n" .
"👥 Umumiy foydalanuvchilar: " . $stats['total_users'] . "\n" .
"✅ Obuna bo'lganlar: " . $stats['subscribed_users'] . "\n" .
"📡 Bugungi signal so'rovlari: " . $stats['bugun_signallar'];

bot_sendMessage($chat_id, $message, get_back_keyboard());
break;

case '📨 Xabar yuborish':
set_admin_mode($user_id, 'broadcast');
bot_sendMessage($chat_id, 
"📢 <b>Xabar yuborish</b>\n\n" .
"Yubormoqchi bo'lgan xabaringizni kiriting:",
get_back_keyboard()
);
break;

case '➕ Kanal qo\'shish':
set_admin_mode($user_id, 'add_channel');
bot_sendMessage($chat_id, 
"➕ <b>Kanal qo'shish</b>\n\n" .
"Kanal ma'lumotlarini quyidagi formatda yuboring:\n\n" .
"<code>@shamshodbekh Kanal Nomi</code>",
get_back_keyboard()
);
break;

case '➖ Kanal o\'chirish':
$channels = get_channels();
if (empty($channels)) {
bot_sendMessage($chat_id, 
"❌ O'chirish uchun kanal mavjud emas!",
get_back_keyboard()
);
} else {
$keyboard = [];
foreach ($channels as $channel) {
$keyboard[] = [['text' => '❌ ' . $channel['name']]];
}
$keyboard[] = [['text' => '🔙 Orqaga']];

bot_sendMessage($chat_id, 
"➖ <b>Kanal o'chirish</b>\n\n" .
"O'chirmoqchi bo'lgan kanalni tanlang:",
[
'keyboard' => $keyboard,
'resize_keyboard' => true,
'one_time_keyboard' => true
]
);
}
break;
// DASTURCHI: @ShamshodbekDev
case '📋 Kanallar ro\'yxati':
$channels = get_channels();
if (empty($channels)) {
$channels_text = "📋 <b>Kanallar ro'yxati</b>\n\nHozircha kanal qo'shilmagan.";
} else {
$channels_text = "📋 <b>Kanallar ro'yxati</b>\n\n";
foreach ($channels as $index => $channel) {
$channels_text .= ($index + 1) . ". " . $channel['name'] . "\n";
$channels_text .= "   " . $channel['username'] . "\n\n";
}
$channels_text .= "Jami: " . count($channels) . " ta kanal";
}

bot_sendMessage($chat_id, $channels_text, get_back_keyboard());
break;

case '🏠 Asosiy menyu':
bot_sendMessage($chat_id, 
"💎 <b>Asosiy menuga qaytdingiz:</b>",
get_main_keyboard()
);
break;

case '🔙 Orqaga':
bot_sendMessage($chat_id, 
"👨‍💻 <b>Admin paneliga xush kelibsiz!</b>\n\n" .
"Quyidagi tugmalardan birini tanlang:",
get_admin_keyboard()
);
break;

default:
if (strpos($text, '❌ ') === 0) {
$channel_name = substr($text, 3);
if (remove_channel($channel_name)) {
bot_sendMessage($chat_id, 
"✅ <b>Kanal muvaffaqiyatli o'chirildi!</b>\n\n" .
"Nomi: " . $channel_name,
get_admin_keyboard()
);
} else {
bot_sendMessage($chat_id, 
"❌ Kanal o'chirishda xatolik!", 
get_admin_keyboard()
);
}
}
break;
}
}
}
}

if (isset($update['callback_query'])) {
$callback = $update['callback_query'];
$chat_id = $callback['message']['chat']['id'];
$user_id = $callback['from']['id'];
$callback_id = $callback['id'];
$message_id = $callback['message']['message_id'];
$data = $callback['data'];

if ($data == 'check_subscription') {
$is_subscribed = check_subscription($user_id);

if ($is_subscribed) {
update_user_subscription($user_id, true);

bot_deleteMessage($chat_id, $message_id);

$success_message = "✅ <b>Tabriklaymiz! Siz kanalga obuna bo'ldingiz!</b>\n\n" .
"🛡 <b>ESLATMA:</b> Sizda Signallar ishlamayapti demak siz biz tomonizdan VZLOM qilingan dasturlarni yuklamagansiz❗️\n\n" .
"📌 <b>Bizni VZLOM Dasturlarimiz:</b>\n" .
"🔱 @MossbetAPK\n\n" .
"📹 <b>Botdan Foydalanish videosi:</b>\n" .
"🔱 @MossbetAPK\n\n" .
"🚨 <b>ESLATMA: PROMOKOD YOZMANG❗️</b>\n\n" .
"📡 Signal olish uchun tugmani bosing!";

bot_sendMessage($chat_id, $success_message, get_main_keyboard());

bot_answerCallbackQuery($callback_id, "✅ Obuna tekshirildi! Tabriklaymiz!", false);

} else {
bot_sendMessage($chat_id, 
"❌ <b>Obuna tekshirilmadi!</b>\n\n" .
"Iltimos, barcha kanallarga obuna bo'ling va qaytadan '✅ Obuna bo\'ldim' tugmasini bosing!"
);

bot_answerCallbackQuery($callback_id, "❌ Iltimos, barcha kanallarga obuna bo'ling!", false);
}
}
}

// DASTURCHI: @ShamshodbekDev

http_response_code(200);
echo "OK";
?>
