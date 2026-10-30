<?php
// CLI-only receipt worker. Install dompdf/dompdf with Composer before scheduling.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/config.php';
$autoload=__DIR__.'/../vendor/autoload.php';
if(!is_file($autoload)){fwrite(STDERR,"Missing Composer dependencies. Run: composer require dompdf/dompdf\n");exit(2);}
require_once $autoload;
if(!class_exists('Dompdf\\Dompdf')){fwrite(STDERR,"Dompdf is not installed. Run: composer require dompdf/dompdf\n");exit(2);}
$outDir=__DIR__.'/../storage/fee-receipts';if(!is_dir($outDir)&&!mkdir($outDir,0700,true)){fwrite(STDERR,"Cannot create private receipt directory.\n");exit(2);}
$limit=max(1,min(100,(int)($argv[1]??20)));$done=0;
for($i=0;$i<$limit;$i++){
 $dbh->beginTransaction();
 $q=$dbh->query("SELECT id,transaction_id FROM fee_receipt_queue WHERE status='queued' ORDER BY id LIMIT 1 FOR UPDATE");$job=$q->fetch();
 if(!$job){$dbh->commit();break;}
 $dbh->prepare("UPDATE fee_receipt_queue SET status='processing',attempts=attempts+1 WHERE id=:id")->execute([':id'=>$job['id']]);$dbh->commit();
 try{
  $q=$dbh->prepare('SELECT a.*,p.payment_date,p.receipt_number,p.payment_method,f.academic_year,f.term,f.amount_due,s.StudentName,s.RollId,c.ClassName,c.Section,(SELECT COALESCE(SUM(pp.amount),0) FROM student_fee_payments pp WHERE pp.fee_id=f.id) paid FROM fee_transaction_audit a JOIN student_fee_payments p ON p.receipt_number=a.receipt_reference JOIN student_term_fees f ON f.id=p.fee_id JOIN tblstudents s ON s.StudentId=f.student_id LEFT JOIN tblclasses c ON c.id=f.class_id WHERE a.transaction_id=:tx LIMIT 1');$q->execute([':tx'=>$job['transaction_id']]);$r=$q->fetch();if(!$r)throw new RuntimeException('Payment transaction not found for receipt.');
  $balance=max(0,(float)$r['amount_due']-(float)$r['paid']);$html='<!doctype html><html><head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8"><style>body{font:14px DejaVu Sans,sans-serif;color:#172033}.head{text-align:center;border-bottom:2px solid #23395d;padding:12px}h1{color:#23395d;font-size:22px}.row{padding:9px;border-bottom:1px solid #ddd}.label{display:inline-block;width:42%;color:#475569}.value{font-weight:bold}</style><link rel="stylesheet" href="css/srms-responsive.css" media="screen">
</head><body><div class="head"><h1>'.htmlspecialchars(APP_NAME,ENT_QUOTES,'UTF-8').'</h1><p>Official Fee Payment Receipt</p></div>';
  $items=['Receipt Reference'=>$r['receipt_reference'],'Transaction ID'=>$r['transaction_id'],'Payment Date'=>$r['payment_date'],'Student Name'=>$r['StudentName'],'Roll ID'=>$r['RollId'],'Class/Form'=>$r['ClassName'].' '.$r['Section'],'Academic Year'=>$r['academic_year'],'Term'=>$r['term'],'Payment Method'=>$r['payment_method'],'Amount Paid'=>number_format((float)$r['amount'],2),'Remaining Balance'=>number_format($balance,2)];foreach($items as $k=>$v)$html.='<div class="row"><span class="label">'.htmlspecialchars($k,ENT_QUOTES,'UTF-8').'</span><span class="value">'.htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8').'</span></div>';$html.='<p style="margin-top:30px">Received with thanks.</p></body></html>';
  $pdf=new Dompdf\Dompdf(['isRemoteEnabled'=>false]);$pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A4','portrait');$pdf->render();$path=$outDir.'/'.$job['transaction_id'].'.pdf';if(file_put_contents($path,$pdf->output(),LOCK_EX)===false)throw new RuntimeException('Could not write PDF receipt.');chmod($path,0600);$u=$dbh->prepare("UPDATE fee_receipt_queue SET status='generated',processed_at=NOW(),last_error=NULL WHERE id=:id");$u->execute([':id'=>$job['id']]);$done++;
 }catch(Throwable $e){error_log('SRMS receipt worker: '.$e->getMessage());$u=$dbh->prepare("UPDATE fee_receipt_queue SET status=IF(attempts>=5,'failed','queued'),last_error=:e WHERE id=:id");$u->execute([':e'=>substr($e->getMessage(),0,500),':id'=>$job['id']]);}
}
fwrite(STDOUT,"Receipt PDFs generated: {$done}\n");
