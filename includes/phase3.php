<?php
// Phase 3: analytics, notifications, audit and backup helpers.
function notify_user(PDO $dbh,string $recipientType,int $recipientId,string $title,string $message,string $type='info',?string $link=null): void {
    $q=$dbh->prepare('INSERT INTO notifications(recipient_type,recipient_id,title,message,type,link) VALUES(:rt,:rid,:t,:m,:ty,:l)');
    $q->execute([':rt'=>$recipientType,':rid'=>$recipientId,':t'=>$title,':m'=>$message,':ty'=>$type,':l'=>$link]);
}
function notify_all_admins(PDO $dbh,string $title,string $message,string $type='info',?string $link=null): void {
    $ids=$dbh->query('SELECT id FROM admin')->fetchAll(PDO::FETCH_COLUMN);
    foreach($ids as $id) notify_user($dbh,'admin',(int)$id,$title,$message,$type,$link);
}
function notify_teacher(PDO $dbh,int $teacherId,string $title,string $message,string $type='info',?string $link=null): void {
    notify_user($dbh,'teacher',$teacherId,$title,$message,$type,$link);
}
function current_recipient(PDO $dbh): array {
    if(!empty($_SESSION['alogin']) && !empty($_SESSION['admin_id'])) return ['admin',(int)$_SESSION['admin_id']];
    if(!empty($_SESSION['teacher_login']) && !empty($_SESSION['teacher_id'])) return ['teacher',(int)$_SESSION['teacher_id']];
    return ['',0];
}
function notification_unread_count(PDO $dbh,string $type,int $id): int {
    $q=$dbh->prepare('SELECT COUNT(*) FROM notifications WHERE recipient_type=:t AND recipient_id=:id AND is_read=0');
    $q->execute([':t'=>$type,':id'=>$id]); return (int)$q->fetchColumn();
}
function notification_mark_all_read(PDO $dbh,string $type,int $id): void {
    $q=$dbh->prepare('UPDATE notifications SET is_read=1,read_at=NOW() WHERE recipient_type=:t AND recipient_id=:id AND is_read=0');
    $q->execute([':t'=>$type,':id'=>$id]);
}
function sql_quote(PDO $dbh,$value): string {
    if($value===null) return 'NULL';
    return $dbh->quote((string)$value);
}
function build_sql_backup(PDO $dbh): string {
    $out="-- SRMS database backup\n-- Generated: ".date('Y-m-d H:i:s')."\n-- Database: ".DB_NAME."\n\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
    $tables=$dbh->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM);
    foreach($tables as $row){
        $table=$row[0];
        $create=$dbh->query('SHOW CREATE TABLE `'.str_replace('`','``',$table).'`')->fetch(PDO::FETCH_NUM);
        $out.="DROP TABLE IF EXISTS `".str_replace('`','``',$table)."`;\n".$create[1].";\n\n";
        $columns=$dbh->query('SHOW COLUMNS FROM `'.str_replace('`','``',$table).'`')->fetchAll(PDO::FETCH_ASSOC);
        $cols=array_map(fn($c)=>'`'.str_replace('`','``',$c['Field']).'`',$columns);
        $select=$dbh->query('SELECT * FROM `'.str_replace('`','``',$table).'`');
        $batch=[];
        while($r=$select->fetch(PDO::FETCH_ASSOC)){
            $vals=[]; foreach($columns as $c){$vals[]=array_key_exists($c['Field'],$r)&&$r[$c['Field']]!==null?sql_quote($dbh,$r[$c['Field']]):'NULL';}
            $batch[]='('.implode(',',$vals).')';
            if(count($batch)>=100){$out.='INSERT INTO `'.$table.'` ('.implode(',',$cols).') VALUES\n'.implode(",\n",$batch).";\n";$batch=[];}
        }
        if($batch)$out.='INSERT INTO `'.$table.'` ('.implode(',',$cols).') VALUES\n'.implode(",\n",$batch).";\n";
        $out.="\n";
    }
    return $out."SET FOREIGN_KEY_CHECKS=1;\n";
}
function split_sql_statements(string $sql): array {
    $statements=[];$buf='';$len=strlen($sql);$quote='';$lineComment=false;$blockComment=false;
    for($i=0;$i<$len;$i++){
        $ch=$sql[$i];$next=$i+1<$len?$sql[$i+1]:'';
        if($lineComment){$buf.=$ch;if($ch==="\n")$lineComment=false;continue;}
        if($blockComment){$buf.=$ch;if($ch==='*'&&$next==='/'){$buf.='/';$i++;$blockComment=false;}continue;}
        if($quote){$buf.=$ch;if($ch==='\\'&&$i+1<$len){$buf.=$sql[++$i];continue;}if($ch===$quote){if($i+1<$len&&$sql[$i+1]===$quote){$buf.=$sql[++$i];}else{$quote='';}}continue;}
        if(($ch==='-'&&$next==='-' && ($i+2 >= $len || ctype_space($sql[$i+2]))) || ($ch==='#')){$lineComment=true;$buf.=$ch;continue;}
        if($ch==='/'&&$next==='*'){$blockComment=true;$buf.='/*';$i++;continue;}
        if($ch==="'"||$ch==='"'||$ch==='`'){$quote=$ch;$buf.=$ch;continue;}
        if($ch===';'){if(trim($buf)!=='')$statements[]=trim($buf);$buf='';continue;}
        $buf.=$ch;
    }
    if(trim($buf)!=='')$statements[]=trim($buf);
    return $statements;
}
