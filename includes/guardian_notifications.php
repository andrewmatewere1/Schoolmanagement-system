<?php
// Queue-only notification creation. Delivery is handled by scripts/send-notifications.php.
function srms_queue_published_result_notifications(PDO $dbh, int $studentId, string $year, string $term, string $exam): void {
    $q=$dbh->prepare('SELECT StudentName,NextOfKinName,NextOfKinPhone,GuardianEmail,RollId FROM tblstudents WHERE StudentId=:id LIMIT 1');
    $q->execute([':id'=>$studentId]); $s=$q->fetch(); if(!$s) return;
    $student=trim((string)$s['StudentName']); $guardian=trim((string)($s['NextOfKinName']??''));
    $subject='Student results published - '.$student;
    $message='Dear '.($guardian ?: 'Parent/Guardian').",\n\nResults for ".$student.' (Roll ID: '.($s['RollId']??'').') have been published. Academic year: '.$year.'; Term: '.$term.'; Examination: '.$exam.". Please visit the school result portal to view the report.\n\nThis is an automated notification. Please contact the school if you need assistance.";
    $email=trim((string)($s['GuardianEmail']??''));
    if($email!=='' && filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $i=$dbh->prepare("INSERT INTO notification_queue(student_id,channel,recipient,subject,message,academic_year,term,exam_type,status) VALUES(:s,'email',:r,:sub,:m,:y,:t,:e,'queued')");
        $i->execute([':s'=>$studentId,':r'=>$email,':sub'=>$subject,':m'=>$message,':y'=>$year,':t'=>$term,':e'=>$exam]);
    }
    $phone=preg_replace('/[^0-9+]/','',(string)($s['NextOfKinPhone']??''));
    if($phone!=='' && preg_match('/^\+?[1-9][0-9]{7,14}$/',$phone)) {
        $i=$dbh->prepare("INSERT INTO notification_queue(student_id,channel,recipient,subject,message,academic_year,term,exam_type,status) VALUES(:s,'whatsapp',:r,:sub,:m,:y,:t,:e,'queued')");
        $i->execute([':s'=>$studentId,':r'=>$phone,':sub'=>$subject,':m'=>$message,':y'=>$year,':t'=>$term,':e'=>$exam]);
    }
}
