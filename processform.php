<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('include_path', '......./PHPMailer');
require_once('PHPMailerAutoload.php');

$fromEmail = 'from@email333.com';
$toEmail = 'to@email333.com';
$fromName = 'SiteName';
$subject = 'Site notification';
$message = '<h3>Заявка с сайта</h3>';
$CSS = '<style>table tr td:first-child{font-weight:bold}</style>';
// $adminEmail = 'info@example.com';
$redirect_url = 'http://path-to-thank-you-page';
$antispam_fields = array(
	'field1' => '', 
	'field2' => '',
	'field3' => '',
	'field4' => '',
	'field5' => '',
	);

function get_ip_info($ip){
	global $ipinfo;
    $curl = curl_init(); 
    curl_setopt($curl, CURLOPT_URL, 'http://ipgeobase.ru:7020/geo?ip='.$ip); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    $responseXml = curl_exec($curl);
    curl_close($curl);
    if (substr($responseXml, 0, 5) == '<?xml'){
        $ipinfo = new SimpleXMLElement($responseXml);
        return $ipinfo->ip;
    }
    return false;
}

function prepareFields(){
	global $form_message, $data;
	$ipinfo = get_ip_info($_SERVER['REMOTE_ADDR']);
  	$form_message = isset($_POST['message'])  ? trim( htmlspecialchars($_POST['message']), 1000)  : '';
	$data = array(
	'Имя' =>      isset($_POST['name']) ? trim( htmlspecialchars($_POST['name']), 100) : '',
	'Телефон' =>   isset($_POST['phone']) ? trim( htmlspecialchars($_POST['phone']), 50) : '',
	'Email' =>     isset($_POST['email']) ? trim( htmlspecialchars($_POST['email']), 50) : '',
	// 'Форма на сайте' => isset($_POST['form_name'])  ? trim( htmlspecialchars($_POST['form_name']), 100)  : '',
	'IP' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
	'Город' => !empty($ipinfo->city->__toString()) ? $ipinfo->city->__toString() : '',
  	'Источник перехода' => isset($_COOKIE['_referrer']) ? $_COOKIE['_referrer'] : $_SERVER['HTTP_REFERER'],
	// 'utm_source' => isset($_COOKIE['utm_source'])  ? trim( htmlspecialchars($_COOKIE['utm_source']), 50)  : '',
	// 'utm_keyword' =>isset($_COOKIE['utm_keyword'])  ? trim( htmlspecialchars( rawurldecode($_COOKIE['utm_keyword']) ) , 200)  : '',
	);
	
}

function validateForm(){
	global $antispam_fields;
	$have_errors = true;
	if ($_POST['email']) {
		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $have_errors = false;
	}
	foreach( $antispam_fields as $key => $value ){
    	if ($_POST[$key] != $value) $have_errors = false;
	}
	return $have_errors;
}

function sendEmail($to, $subject, $message, $attachment = false, $attachmentName = false){
	global $fromEmail, $fromName;
	$mail = new PHPMailer;
	$mail->CharSet = "UTF-8";
	$mail->setFrom($fromEmail, $fromName);
	$mail->addAddress($to);
	//$mail->addAddress($adminEmail);
	$mail->Subject = $subject;
	$mail->msgHTML($message);
	if ($attachment) $mail->addAttachment($attachment, $attachmentName);
	if(!$mail->send()) {
	    echo 'Message could not be sent.';
	    // echo 'Mailer Error: ' . $mail->ErrorInfo;
	    return false;
	} else {
	    echo 'Message has been sent';
	    return true;
	}
}

if (validateForm()){
	prepareFields();
	$message .= '<p>Данные контакта:</p>';
	$message .= '<table>';
	foreach ($data as $key => $value) {
		$message .= '<tr><td>'.$key.': </td><td>'.$value.'</td></tr>';
	}
	$message .= '</table>';
	$message .= '<p><strong>Сообщение: </strong><br>'.$form_message.'</p>';
	$message .= $CSS;

	if (sendEmail($toEmail, $subject, $message)){
		// print "Успешно отправлено <script>window.location = '".$redirect_url."'</script>";
		header( 'Location: '.$redirect_url, true, 301 );
	} else echo 'не работает';
	
} else die('ERROR!');
?>
