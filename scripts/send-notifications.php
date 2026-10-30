<?php
// CLI-only notification queue worker. Run with: php scripts/send-notifications.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
require_once __DIR__.'/../includes/config.php';
$limit=max(1,min(100,(int)($argv[1]??25)));
$token=defined('WHATSAPP_ACCESS_TOKEN')?WHATSAPP_ACCESS_TOKEN:(getenv('WHATSAPP_ACCESS_TOKEN')?:'');
$phoneId=defined('WHATSAPP_PHONE_NUMBER_ID')?WHATSAPP_PHONE_NUMBER_ID:(getenv('WHATSAPP_PHONE_NUMBER_ID')?:'');
$graphVersion=defined('WHATSAPP_GRAPH_VERSION')?WHATSAPP_GRAPH_VERSION:(getenv('WHATSAPP_GRAPH_VERSION')?:'v21.0');
$q=$dbh->prepare("SELECT * FROM notification_queue WHERE status='queued' OR (status='failed' AND attempts<3) ORDER BY id ASC LIMIT ".$limit);$q->execute();$items=$q->fetchAll();$sent=0;$failed=0;
foreach($items as $n){
 try {
  if($n['channel']==='email') {
   $from=defined('SRMS_MAIL_FROM')?SRMS_MAIL_FROM:(getenv('SRMS_MAIL_FROM')?:'');
   if(!$from || !filter_var($from,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Configure SRMS_MAIL_FROM in includes/config.local.php before sending email.');
   $headers=['From: '.$from,'MIME-Version: 1.0','Content-Type: text/plain; charset=UTF-8'];
   $ok=mail($n['recipient'], '=?UTF-8?B?'.base64_encode($n['subject']).'?=', $n['message'], implode("\r\n",$headers));
   if(!$ok) throw new RuntimeException('PHP mail() did not accept the message. Configure an SMTP-capable mail transport for production.');
  } elseif($n['channel']==='whatsapp') {
   if(!$token||!$phoneId) throw new RuntimeException('Configure WHATSAPP_ACCESS_TOKEN and WHATSAPP_PHONE_NUMBER_ID.');
   if(!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required for WhatsApp Cloud API.');
   $url='https://graph.facebook.com/'.$graphVersion.'/'.$phoneId.'/messages';
   $payload=['messaging_product'=>'whatsapp','recipient_type'=>'individual','to'=>preg_replace('/\D/','',$n['recipient']),'type'=>'text','text'=>['preview_url'=>false,'body'=>$n['message']]];
   $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>25]);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$curlErr=curl_error($ch);curl_close($ch);
   if($body===false||$code<200||$code>=300) throw new RuntimeException('WhatsApp API HTTP '.$code.': '.substr((string)($body?:$curlErr),0,300));
  }
  $u=$dbh->prepare("UPDATE notification_queue SET status='sent',attempts=attempts+1,sent_at=NOW(),last_error=NULL WHERE id=:id");$u->execute([':id'=>$n['id']]);$sent++;
 } catch(Throwable $e){$u=$dbh->prepare("UPDATE notification_queue SET status='failed',attempts=attempts+1,last_error=:e WHERE id=:id");$u->execute([':e'=>substr($e->getMessage(),0,490),':id'=>$n['id']]);error_log('SRMS notification '.$n['id'].' failed: '.$e->getMessage());$failed++;}
}
echo 'Processed '.count($items).' notifications; sent='.$sent.' failed='.$failed.PHP_EOL;
