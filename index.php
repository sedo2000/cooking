<?php

// استبدل 'YOUR_BOT_TOKEN' بالـ API Token الذي حصلت عليه من BotFather
define('BOT_TOKEN', '7553069380:AAGj2GMFAR3apnDf-9J4h_bLIFXa5aRmbzo');

// مفتاح API لـ Spoonacular
define('SPOONACULAR_API_KEY', '5db1f45e6750423fa4173d84da2cff82');

// الحصول على آخر تحديثات الرسائل
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['message'])) {
    $chatId = $data['message']['chat']['id'];
    $text = $data['message']['text'];

    // الرد على الرسالة
    if ($text == '/start') {
        sendMessage($chatId, "مرحبًا! أنا بوت الطبخ. 🍳\nأرسل لي المكونات التي لديك (مفصولة بفاصلة)، وسأقترح عليك بعض الوصفات.\nمثال: طماطم، بصل، لحم");
    } else {
        // تقسيم المكونات المدخلة إلى مصفوفة
        $ingredients = array_map('trim', explode('،', $text)); // استخدام الفاصلة العربية "،" لفصل المكونات

        // البحث عن وصفات عبر Spoonacular API
        $response = "🔍 سأبحث عن وصفات تحتوي على: " . implode(", ", $ingredients) . "...\n\n";

        // البحث عن وصفات باستخدام Spoonacular API
        $recipes = searchRecipesOnline($ingredients);

        if (!empty($recipes)) {
            $response .= "🍴 إليك بعض الوصفات التي وجدتها:\n\n";
            foreach ($recipes as $recipe) {
                $response .= "**وصفة: " . $recipe['name'] . "**\n";
                $response .= "**المكونات:** " . implode(", ", $recipe['ingredients']) . "\n";
                $response .= "**طريقة التحضير:** [رابط الوصفة](" . $recipe['url'] . ")\n\n";
            }
        } else {
            $response .= "عذرًا، لم أجد وصفات تحتوي على هذه المكونات. 😢\nحاول إرسال مكونات أخرى.";
        }

        sendMessage($chatId, $response);
    }
}

/**
 * إرسال رسالة إلى المستخدم
 *
 * @param int $chatId معرف المحادثة
 * @param string $text نص الرسالة
 */
function sendMessage($chatId, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $postData = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];

    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}

/**
 * البحث عن وصفات عبر Spoonacular API
 *
 * @param array $ingredients المكونات المدخلة
 * @return array الوصفات التي تم العثور عليها
 */
function searchRecipesOnline($ingredients) {
    $apiUrl = "https://api.spoonacular.com/recipes/findByIngredients";
    $query = [
        'ingredients' => implode(",", $ingredients),
        'apiKey' => SPOONACULAR_API_KEY,
        'number' => 5 // عدد الوصفات التي تريد الحصول عليها
    ];

    $url = $apiUrl . '?' . http_build_query($query);
    $response = file_get_contents($url);

    if ($response === FALSE) {
        return [];
    }

    $data = json_decode($response, true);

    $recipes = [];
    if (!empty($data)) {
        foreach ($data as $recipe) {
            $recipes[] = [
                'name' => $recipe['title'],
                'ingredients' => array_column($recipe['missedIngredients'], 'name'),
                'url' => "https://spoonacular.com/recipes/" . $recipe['title'] . "-" . $recipe['id']
            ];
        }
    }

    return $recipes;
}
