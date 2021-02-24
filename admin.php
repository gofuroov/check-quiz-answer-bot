<?php

define('API', 'Your API TOKEN');
define('ACTION_NEW_ANSWER', 0);

$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$chat_id = $message->chat->id;
$text = $message->text;
$username = $message->chat->first_name;

$callback = $update->callback_query;
$clb_chat_id = $callback->from->id;
$data = $callback->data;
$callback_id = $callback->id;
$clb_message_id = $callback->message->message_id;

$con = mysqli_connect('localhost', 'colindev_admin', '13', 'basename(path)');
$sql = "select * from bot where chat_id={$chat_id}";
$res = mysqli_query($con, $sql);

$btn_main = json_encode([
        'resize_keyboard' => true,
        'keyboard' => [
            [['text' => "✏️ Yangi Test"]],
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
function statusTest($id, $chat_id)
{
	global $con;
	$sql = "select * from question where id={$id}";
	$res = mysqli_query($con, $sql);
	if($res)
	{
		$data = mysqli_fetch_array($res, MYSQLI_ASSOC);
		$d = $data;
		$id = $d['id'];	$j = $d['data']; $act = $d['active']?'✅ faol':'🚫 nofaol';

		$btn_inline = json_encode(
			['inline_keyboard' => [
			    [
			    	[
				    	'text' => '🟢 Faol',
			    		'callback_data' => "on $id",
				    ],
				    [
				    	'text' => '🔴 Nofaol',
			    		'callback_data' => "off $id",
				    ]
				],
			    [
			    	[
			    		'text' => '🧾 Natijalarni olish!',
			    		'callback_data' => "getAnswer $id",
			    	]
			    ]
		    ],
		]);

		return bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🟢 Saqlandi:\n\n🔐 Test codi:\t\t*$id*\n📑 Javoblari:\t\t*$j*\n📌 Holati:\t\t*$act*",
        'parse_mode' => 'Markdown',
        'reply_markup' => $btn_inline
        ]);
	}
}
function deleteMessage($chat_id, $message_id)
{
	return bot('deleteMessage', [
		'chat_id' => $chat_id,
		'message_id' => $message_id,
	]);
}
function getList($test_id)
{
	global $con;
	$sql = "select * from result where test_id={$test_id} ORDER BY score DESC, created_at";
	$res = mysqli_query($con, $sql);
	if (mysqli_num_rows($res)>0)
	{
		$data = mysqli_fetch_all($res, MYSQLI_ASSOC);
		$s = "🔔 *{$test_id}* - test bo'yicha hisobot:\n\n";
		$c = 0;
		foreach ($data as $user) {
			$c++;
			$name = $user['name'];
			$user_chat_id = $user['chat_id'];
			$nat = $user['score'];
			$vaqt = $user['created_at'];

			$s = $s."$c. 👨‍🎓 [{$name}](tg://user?id={$user_chat_id}) 💡 *{$nat}* ta ⏱ {$vaqt}\n";
		}
		return $s;
		// return json_encode($data[0]);
	}
	else
	{
		return "⏳ Hech kim test topshirmadi !";
	}
}
//+++++++++++++++++++++++++++++++++++
if (isset($text))
{
    typing($chat_id);
}

switch ($text)
{
	case '/start':
		bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => '💡 Salom '.$username,
            'reply_markup' => $btn_main
        ]);
        $sql = "DELETE FROM bot WHERE chat_id={$chat_id}";
        $res = mysqli_query($con, $sql);
        break;
	
	case '✏️ Yangi Test':
		bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "📝 Test javoblarini yo'llang:\n\n `⚠️ Probellarsiz va faqat harflardan iborat bo'lgan. (Max: 255)`",
            'parse_mode' => 'Markdown',
            'reply_markup' => $btn_cancel
        ]);
        $sql = "insert into bot(chat_id, action) values({$chat_id}, ".ACTION_NEW_ANSWER.")";
        $res = mysqli_query($con, $sql);
        break;
        
        case '❌ Bekor qilish':
		bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "💬 Tanlang:",
            'reply_markup' => $btn_main
        ]);
        $sql = "DELETE FROM bot WHERE chat_id={$chat_id}";
        $res = mysqli_query($con, $sql);
        break;
	
	default:
		// code...
		break;
}

if (mysqli_num_rows($res)==1)
{
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🟣 Kiritildi:\n\n `".$text."`",
        'parse_mode' => 'Markdown'
    ]);
	
    if (count(explode(" ", $text))==1 && strlen(preg_replace("/[^a-zA-Z]+/", "", $text))>1)
    {
    	$sql = "DELETE FROM bot WHERE chat_id={$chat_id}";
		$res = mysqli_query($con, $sql);

    	$result = preg_replace("/[^a-zA-Z]+/", "", $text);
        bot('sendMessage', [
	        'chat_id' => $chat_id,
	        'text' => "🔵 Qabul qilindi:\n\n `$result`",
	        'parse_mode' => 'Markdown',
	     	'reply_markup' => $btn_main
	        ]);
    	$sql = "insert into question(data, chat_id) values('{$result}', {$chat_id})";
    	$res = mysqli_query($con, $sql);
    	if ($res)
    	{
    		$sql = "select * from question where data='{$result}'";
    		$res = mysqli_query($con, $sql);
    		if($res)
    		{
    			$index = mysqli_num_rows($res);
	    		$data = mysqli_fetch_all($res, MYSQLI_ASSOC);
	    		$d = $data[$index-1];
	    		$id = $d['id'];	$j = $d['data']; $act = $d['active']?'✅ faol':'🚫 nofaol';

	    		$btn_inline = json_encode(
					['inline_keyboard' => [
					    [
					    	[
						    	'text' => '🟢 Faol',
					    		'callback_data' => "on $id",
						    ],
						    [
						    	'text' => '🔴 Nofaol',
					    		'callback_data' => "off $id",
						    ]
						],
					    [
					    	[
					    		'text' => '🧾 Natijalarni olish!',
					    		'callback_data' => "getAnswer $id",
					    	]
					    ]
				    ],
				]);

	    		bot('sendMessage', [
		        'chat_id' => $chat_id,
		        'text' => "🟢 Saqlandi:\n\n🔐 Test codi:\t\t*$id*\n📑 Javoblari:\t\t*$j*\n📌 Holati:\t\t*$act*",
		        'parse_mode' => 'Markdown',
		        'reply_markup' => $btn_inline
		        ]);
	    	}
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

//CallBackQuery
if (isset($data))
{
	$clbExp = explode(" ", $data);
	switch ($clbExp[0])
	{
		case 'on':
			$sql = "update question set active='1' where id=$clbExp[1]";
	    	$res = mysqli_query($con, $sql);
	    	if ($res)
	    	{
		    	bot('answerCallbackQuery', [
			        'callback_query_id' => $callback_id,
			        'text' => "🟢 Faol holatga o'tkazildi !",
			        'show_alert' => false,
			    ]);
		    }
		    statusTest($clbExp[1], $clb_chat_id);
		    deleteMessage($clb_chat_id, $clb_message_id);
		    
		break;
		
		case 'off':
			$sql = "update question set active='0' where id=$clbExp[1]";
			$res = mysqli_query($con, $sql);
			if ($res)
			{
		    	bot('answerCallbackQuery', [
			        'callback_query_id' => $callback_id,
			        'text' => "🔴 Nofaol holatga o'tkazildi !",
			        'show_alert' => false,
			    ]);
		    }
		    statusTest($clbExp[1], $clb_chat_id);
		    deleteMessage($clb_chat_id, $clb_message_id);
		    
    	break;

    	case 'getAnswer':
    		$test_id = $clbExp[1];
    		$q = getList($test_id);
			bot('answerCallbackQuery', [
			        'callback_query_id' => $callback_id,
			        'text' => "💬 Natija jo'natildi!",
			        'show_alert' => false,
			    ]);
			bot('sendMessage', [
		        'chat_id' => $clb_chat_id,
		        'text' => $q,
		        'parse_mode' => 'Markdown'
		        ]);
    	break;
		
		default:
			# code...
			break;
	}
}