<?php

require_once __DIR__.'/../../includes/image_optimizer.php';

if(!empty($_FILES['image']['tmp_name'])){

$target = __DIR__.'/../../uploads/'.basename($_FILES['image']['name']);

move_uploaded_file($_FILES['image']['tmp_name'],$target);

gdy_convert_to_webp($target);

echo json_encode([
"success"=>true,
"path"=>"/uploads/".basename($target)
]);

}