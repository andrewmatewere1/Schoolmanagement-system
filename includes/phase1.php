<?php
// Phase 1: academic periods, locking and result history helpers.
function period_get(PDO $dbh, string $year, string $term, string $exam): ?array {
    $q=$dbh->prepare('SELECT * FROM academic_periods WHERE academic_year=:y AND term=:t AND exam_type=:e LIMIT 1');
    $q->execute([':y'=>$year,':t'=>$term,':e'=>$exam]);
    $r=$q->fetch(); return $r ?: null;
}
function period_locked(PDO $dbh,string $year,string $term,string $exam): bool {
    $p=period_get($dbh,$year,$term,$exam); return $p ? (bool)$p['is_locked'] : false;
}
function period_require_unlocked(PDO $dbh,string $year,string $term,string $exam): void {
    if(period_locked($dbh,$year,$term,$exam)) throw new RuntimeException('This academic period is locked. Results can no longer be edited. Unlock it from Academic Periods first.');
}
function period_ensure(PDO $dbh,string $year,string $term,string $exam): int {
    $q=$dbh->prepare('INSERT INTO academic_periods(academic_year,term,exam_type,is_active) VALUES(:y,:t,:e,0) ON DUPLICATE KEY UPDATE updated_at=NOW()');
    $q->execute([':y'=>$year,':t'=>$term,':e'=>$exam]);
    $q=$dbh->prepare('SELECT id FROM academic_periods WHERE academic_year=:y AND term=:t AND exam_type=:e');
    $q->execute([':y'=>$year,':t'=>$term,':e'=>$exam]); return (int)$q->fetchColumn();
}
function snapshot_result(PDO $dbh,int $resultId,string $actorType='system',?int $actorId=null,string $action='UPDATE',string $reason=''): void {
    $q=$dbh->prepare('SELECT id,StudentId,ClassId,SubjectId,teacher_id,submission_id,marks,AcademicYear,Term,ExamType,MaxMarks,Published,result_status,approval_state FROM tblresult WHERE id=:id');
    $q->execute([':id'=>$resultId]); $r=$q->fetch(); if(!$r) return;
    $i=$dbh->prepare('INSERT INTO result_history(result_id,student_id,class_id,subject_id,teacher_id,submission_id,marks,academic_year,term,exam_type,max_marks,published,result_status,approval_state,changed_by_type,changed_by,action,reason) VALUES(:rid,:sid,:cid,:sub,:tid,:ssid,:m,:y,:term,:exam,:max,:pub,:rs,:as,:bt,:bid,:act,:reason)');
    $i->execute([':rid'=>$r['id'],':sid'=>$r['StudentId'],':cid'=>$r['ClassId'],':sub'=>$r['SubjectId'],':tid'=>$r['teacher_id'],':ssid'=>$r['submission_id'],':m'=>$r['marks'],':y'=>$r['AcademicYear'],':term'=>$r['Term'],':exam'=>$r['ExamType'],':max'=>$r['MaxMarks'],':pub'=>$r['Published'],':rs'=>$r['result_status'],':as'=>$r['approval_state'],':bt'=>$actorType,':bid'=>$actorId,':act'=>$action,':reason'=>$reason]);
}
