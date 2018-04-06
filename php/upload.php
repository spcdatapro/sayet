<?php
    set_time_limit(0);
    ini_set('upload-max-filesize', '200M');
    //ini_set('post_max_size', '20M');
    //$filename = $_FILES['file']['name'];
    $filename = preg_replace('/[^A-Za-z0-9.\-]/', '', $_FILES['file']['name']);
    $elDir = $_POST['directorio'];
    $elPref = $_POST['prefijo'];
    $destination = $elDir . $elPref . $filename;
	var_dump($_FILES);
	//var_dump($destination);
    move_uploaded_file( $_FILES['file']['tmp_name'] , $destination );