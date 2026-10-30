<?php
function srms_store_profile_photo(array $file, string $personType, int $personId, PDO $dbh): void {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return;
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size'] > 3*1024*1024) throw new RuntimeException('Photo upload failed or exceeds 3 MB.');
    $info = @getimagesize($file['tmp_name']);
    if (!$info) throw new RuntimeException('Upload must be a valid image.');
    if (($info[0] ?? 0) > 5000 || ($info[1] ?? 0) > 5000 || (($info[0] ?? 0) * ($info[1] ?? 0)) > 20000000) throw new RuntimeException('Image dimensions are too large. Use a photo under 20 megapixels.');
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, or WebP photos are allowed.');
    $dir = dirname(__DIR__).'/uploads/profiles';
    if (!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('Photo storage is unavailable.');
    $name = bin2hex(random_bytes(20)).'.'.$allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('Could not store photo.');
    $q=$dbh->prepare('INSERT INTO srms_profile_photos(person_type,person_id,stored_filename,mime_type) VALUES(:t,:i,:f,:m) ON DUPLICATE KEY UPDATE stored_filename=VALUES(stored_filename),mime_type=VALUES(mime_type),uploaded_at=CURRENT_TIMESTAMP');
    $q->execute([':t'=>$personType,':i'=>$personId,':f'=>$name,':m'=>$mime]);
}
