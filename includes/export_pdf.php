<?php
// Small dependency-free PDF writer used for SRMS tabular exports.
function pdf_text($s){
    $s = iconv('UTF-8','windows-1252//TRANSLIT//IGNORE',(string)$s);
    $s = str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);
    return $s;
}
function srms_pdf_download($filename,$title,$columns,$rows){
    $pageW=842; $pageH=595; $margin=28; $lineH=15; $headerH=30;
    $widths=[]; foreach($columns as $c){$widths[]=$c['width'];}
    $pages=[]; $page=[]; $y=$pageH-$margin-$headerH;
    $addPage=function() use (&$pages,&$page,&$y,$pageH,$margin,$headerH,$title,$columns,$widths){
        if($page) $pages[]=$page; $page=[]; $y=$pageH-$margin-$headerH;
        $page[]=['type'=>'title','text'=>$title];
        $page[]=['type'=>'header','cols'=>$columns,'y'=>$y]; $y-=20;
    };
    $addPage();
    foreach($rows as $row){
        if($y < $margin+20){$addPage();}
        $page[]=['type'=>'row','row'=>$row,'y'=>$y]; $y-=$lineH;
    }
    if($page) $pages[]=$page;
    $objects=[]; $objects[]='<< /Type /Catalog /Pages 2 0 R >>'; $objects[]='<< /Type /Pages /Kids [] /Count 0 >>';
    $fontObj=3; $contentIds=[]; $pageIds=[]; $objNo=4;
    foreach($pages as $p){
        $stream="BT\n/F1 14 Tf\n{$margin} " . ($pageH-$margin-14) . " Td\n" . pdf_text($title) . " Tj\nET\n";
        foreach($p as $item){
            if($item['type']==='header'){
                $x=$margin; $stream.="BT\n/F1 8 Tf\n";
                foreach($item['cols'] as $i=>$c){$stream.=($x)." ".($item['y'])." Td\n".pdf_text($c['label'])." Tj\n"; $x+=$c['width'];}
                $stream.="ET\n";
            } elseif($item['type']==='row'){
                $x=$margin; $stream.="BT\n/F1 8 Tf\n";
                foreach($columns as $c){$v=mb_substr((string)($item['row'][$c['key']]??''),0,32);$stream.=$x." ".$item['y']." Td\n".pdf_text($v)." Tj\n";$x+=$c['width'];}
                $stream.="ET\n";
            }
        }
        $contentIds[]=$objNo; $objects[$objNo]='<< /Length '.strlen($stream).' >>\nstream\n'.$stream.'endstream'; $objNo++;
        $pageIds[]=$objNo; $objects[$objNo]=''; $objNo++;
    }
    $kids=[]; foreach($pageIds as $i=>$pid){$content=$contentIds[$i];$objects[$pid]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$pageW.' '.$pageH.'] /Resources << /Font << /F1 '.$fontObj.' 0 R >> >> /Contents '.$content.' 0 R >>';$kids[]=$pid.' 0 R';}
    $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
    $objects[$fontObj]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    ksort($objects); $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
    foreach($objects as $n=>$body){$offsets[$n]=strlen($pdf);$pdf.=$n." 0 obj\n".$body."\nendobj\n";}
    $xref=strlen($pdf);$max=max(array_keys($objects));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++){$pdf.=sprintf('%010d 00000 n \n',$offsets[$i]??0);} $pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9_.-]/','_',$filename).'"'); header('Content-Length: '.strlen($pdf)); echo $pdf; exit;
}
