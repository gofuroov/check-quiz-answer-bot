<?php

define('API', 'TOKEN');
define('API_ADMIN', 'ADMIN_BOT_TOKEN');
define('ACTION_NEW_ANSWER', 0);

$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$chat_id = $message->chat->id;
$text = $message->text;
$username = $message->chat->first_name;

$con = mysqli_connect('localhost', 'colindev_admin', '8A76VCULBDeaaiE', 'colindev_bot');
$sql = "select * from botcheck where chat_id={$chat_id}";
$res = mysqli_query($con, $sql);

$btn_main = json_encode([
        'resize_keyboard' => true,
        'keyboard' => [
            [['text' => "👉 Testni tekshirish"]],
        ]
    ]);
$btn_cancel = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "❌ Bekor qilish"]],
    ],
]);

function bot($method, $data = [])
{
    $url = 'https://api.telegram.org/bot' . API . '/' . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}
function botAdmin($method, $data = [])
{
    $url = 'https://api.telegram.org/bot' . API_ADMIN . '/' . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}
function typing($id)
{
    return bot('sendChatAction', [
        'chat_id' => $id,
        'action' => 'typing'
    ]);
}
function sendMessage($message)
{
	global $chat_id;
	return bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]);
}
function sendMessage2Admin($chat_id, $message)
{
	return botAdmin('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]);
}
function getCountRightAnswer($test_code, $answers)
{
	global $con;
	$sql = "select * from question where id={$test_code}";
	$res = mysqli_query($con, $sql);
	$data = mysqli_fetch_array($res, MYSQLI_ASSOC);
	$answerOrginal = $data['data'];

	$answerOrginal = str_split(strtolower($answerOrginal));
    $answerAsked = str_split(strtolower($answers));
    $c=0;
    for($i=0; $i<min(count($answerOrginal), count($answerAsked)); $i++)
    {
        $ok = (strcmp($answerOrginal[$i], $answerAsked[$i]) === 0);
        if ($ok){ $c++; }
    }
    return $c;
}
function getFullCmp($test_code, $answers)
{
	global $con;
	$sql = "select * from question where id={$test_code}";
	$res = mysqli_query($con, $sql);
	$data = mysqli_fetch_array($res, MYSQLI_ASSOC);
	$answerOrginal = $data['data'];

	$answerOrginal = str_split(strtolower($answerOrginal));
    $answerAsked = str_split(strtolower($answers));
    $c=0;
    $s="";
    for($i=0; $i<min(count($answerOrginal), count($answerAsked)); $i++)
    {
    	if ($i<9){ $c = $i+1; $c ="0{$c}"; } else { $c = $i+1; }
    	if (($i+1) % 3 == 0) { $add = "\n"; } else { $add = ""; } 
        $ok = (strcmp($answerOrginal[$i], $answerAsked[$i]) === 0);
        if ($ok){
        	$s = $s."{$c}) {$answerAsked[$i]} ✅      {$add}";
        } else {
        	$s = $s."{$c}) {$answerAsked[$i]} ❌      {$add}";
        }
    }
    return $s;
}
function isExsist($test_id)
{
	global $con;
	$sql = "select * from question where id={$test_id}";
	$res = mysqli_query($con, $sql);
	if (mysqli_num_rows($res)==1) {
		return true;
	}else{
		return false;
	}
}
function isActive($test_id)
{
	global $con;
	$sql = "select * from question where id={$test_id}";
	$res = mysqli_query($con, $sql);
	$data = mysqli_fetch_array($res, MYSQLI_ASSOC);
	if ($data['active']) {
		return true;
	}else{
		return false;
	}
}
function isFirstTime($test_id)
{
	global $con;
	global $chat_id;
	$sql = "select * from result where test_id={$test_id} and chat_id={$chat_id}";
	$res = mysqli_query($con, $sql);
	if (mysqli_num_rows($res)==0) {
		return true;
	}else{
		return false;
	}
}
function alert2Admin($test_id)
{
	global $con;
	global $chat_id;
	global $username;

	$sql = "select * from question where id={$test_id}";
	$res = mysqli_query($con, $sql);
	$data = mysqli_fetch_array($res, MYSQLI_ASSOC);
	$admin_chat_id = $data['chat_id'];

	sendMessage2Admin($admin_chat_id, "👨‍🎓 [{$username}](tg://user?id={$chat_id}) 📄 _{$test_id}_ - raqamli testni topshirdi!");
}
//usually typing
if (isset($text))
{
    typing($chat_id);
}
//Working with texts
switch ($text)
{
	case '/start':
		bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => '💡 Salom '.$username,
            'reply_markup' => $btn_main
        ]);
        $sql = "DELETE FROM botcheck WHERE chat_id={$chat_id}";
        $res = mysqli_query($con, $sql);
        break;
	
	case '👉 Testni tekshirish':
		bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "📝 Test kodi va javoblaringizni yo'llang:\n\n `⚠️ Test kodidan so'ng probel va test javoblari raqamlarsiz.`\n\n Misol uchun: _123 abcdacdb_",
            'parse_mode' => 'Markdown',
            'reply_markup' => $btn_cancel
        ]);
        $sql = "insert into botcheck(chat_id, action) values({$chat_id}, ".ACTION_NEW_ANSWER.")";
        $res = mysqli_query($con, $sql);
        break;
        
        case '❌ Bekor qilish':
		bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "💬 Tanlang:",
            'reply_markup' => $btn_main
        ]);
        $sql = "DELETE FROM botcheck WHERE chat_id={$chat_id}";
        $res = mysqli_query($con, $sql);
        break;
	
	default:
		// code...
		break;
}

//Check databe
if (mysqli_num_rows($res)==1)
{	
	$parse = explode(" ", $text);
	$parse[0] = preg_replace("/[^0-9]+/", "", $parse[0]);
	$parse[1] = preg_replace("/[^a-zA-Z]+/", "", $parse[1]);

    if (count($parse)==2 && strlen($parse[0])>0 && strlen($parse[1])>1)
    {
    	$sql = "DELETE FROM botcheck WHERE chat_id={$chat_id}";
		$res = mysqli_query($con, $sql);

        bot('sendMessage', [
	        'chat_id' => $chat_id,
	        'text' => "🔵 Qabul qilindi:\n\n🔐 Test kodi: *$parse[0]*\n📄 Javoblaringiz: *$parse[1]*",
	        'parse_mode' => 'Markdown',
	     	'reply_markup' => $btn_main
	        ]);

        if (isExsist($parse[0]))
        {	
        	if (isActive($parse[0]))
        	{
        		$score = getCountRightAnswer($parse[0], $parse[1]);
        		if (isFirstTime($parse[0]))
        		{
		        	$sql = "insert into result(chat_id, name, test_id, answer, score) values({$chat_id}, '{$username}', {$parse[0]}, '{$parse[1]}', {$score})";
		        	$res = mysqli_query($con, $sql);
		        	if ($res)
		        	{
		        		sendMessage("Ismi: *$username*\nTog'ri javoblar soni: *$score ta*\nVaqti: 123456\n\n".getFullCmp($parse[0], $parse[1]));
		        		alert2Admin($parse[0]);
		        	}
        		}
        		else 
        		{
        			sendMessage("⚠️ Siz * $parse[0] * - raqamli testni avval ishlagansiz!\n\n Sizning natijangiz: *{$score}* ta");
        		}
        	} else {
        		sendMessage("⛔️ * $parse[0] * - raqamli test vaqti yakunlangan!");
        	}
        }
        else
    	{
	    	sendMessage("🚫 * $parse[0] * - raqamli test mavjud emas!");
	    }
    }
    else
    {
        bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "❌ Siz noto'g'ri kiritdingiz!",
        'parse_mode' => 'Markdown'
        ]);
    }
}