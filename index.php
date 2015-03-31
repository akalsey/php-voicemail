<?php
require 'vendor/autoload.php';
$app = new \Slim\Slim(array(
	'debug' => true,
	'log.level' => \Slim\Log::DEBUG,
	'log.enabled' => true,
	'log.writer' => new Slim\Extras\Log\DateTimeFileWriter(
	 	array(
	 		'path' => __DIR__ . '/logs',
	 		'name_format' => 'y-m-d'
	 		)
	 	)
));

$app->get('/test', function () {
	$baseurl = myurl($_SERVER) . '/' . basename(dirname($_SERVER['PHP_SELF']));
	$dir = getcwd();
	print '<p>Your Tropo Script URL is <code>' . $baseurl . '/tropo.php</code></p>';
	print '<p>Your files will be stored in <code>' . $dir . '/audio</code></p>';
});

$app->post('/message/:name', function ($name) {
	$dir = getcwd();
	//$log = $this->app->log->debug(print_r($_FILES, true));
	move_uploaded_file($_FILES['filename']['tmp_name'], "$dir/audio/$name.wav");
});

$app->post('/transcription/:id', function ($id) {
	$dir = getcwd();
	$json = file_get_contents("php://input");
	$transcript = json_decode($json);

	$file = fopen("$dir/audio/$id.txt","w");
	fwrite($file,print_r($json, true));
	fclose($file);
	$caller = explode('-', $id);

	$conf = Config::load('config.json');
	$mail = new PHPMailer();
	$mail->isSMTP();
	$mail->SMTPDebug = 0;
	$mail->Host = $conf->get('mailserver');
	if (!empty($conf->get('mailport'))) { $mail->Port = 587; }
	if ($conf->get('mailtls')) { $mail->SMTPSecure = 'tls'; }
	if (!empty($conf->get('mailuser')) && !empty($conf->get('mailpassword'))) {
		$mail->SMTPAuth = true;
		$mail->Username = $conf->get('mailuser');
		$mail->Password = $conf->get('mailpassword');
	}
	$mail->setFrom($conf->get('mailfrom'));
	$mail->Subject   = 'Voicemail from ' . $caller[0];
	$mail->Body      = "From: {$caller[0]}\n\n{$transcript->result->transcription}";
	$mail->AddAddress($conf->get('mailto'));

	$mail->AddAttachment("$dir/audio/$id.wav", $id . '.wav' );

	if(!$mail->send()) {
	    echo 'Mailer Error: ' . $mail->ErrorInfo;
	}
});

$app->get('/tropo.php', function () {
	$conf = Config::load('config.json');

	$baseurl = myurl($_SERVER) . '/' . basename(dirname($_SERVER['PHP_SELF']));
	$ring = "$baseurl/ring.wav"
	$greeting = $conf->get('greeting');
	$transfer = $conf->get('transfer');

	if (empty($greeting)) {
		$greeting = 'The person at $mynum is not available. Leave a message after the beep';
  }

	print <<< EOF
<?php
if (\$currentCall->channel == 'TEXT') {
  say('This number cannot receive text messages');
} else {
  \$callerID = \$currentCall->callerID;
  \$time = date('Y-m-d-His');
  \$id = \$callerID . '-' . \$time;

  \$mynum = implode(' ',str_split(\$currentCall->calledID));
  \$greeting = "$greeting";

  \$transfer = '$transfer';
  if (empty(\$transfer)) {
    voicemail();
  } else {
    \$result = transfer(\$transfer, array(
      'playvalue' => "$ring",
      'playrepeat' => 2000,
      'timeout' => 20,
      'onTimeout' => "voicemail",
      'onBusy' => "voicemail",
      'onError' => "voicemail",
      'onCallFailure' => "voicemail",
      ));
  }
}

function voicemail() {
  record(\$GLOBALS['greeting'], array (
    "beep" => true,
    "maxTime" => 900,
    "bargein" => false,
    "recordURI"=>"$baseurl/message/{\$GLOBALS['id']}",
    "transcriptionOutURI" => "$baseurl/transcription/{\$GLOBALS['id']}",
    "transcriptionID" => \$GLOBALS['id'],
    "voice" => "Veronica"
    )
  );
}
?>
EOF;
});

$app->run();

function myurl($s)
{
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host = isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null;
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}
