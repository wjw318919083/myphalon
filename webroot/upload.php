<?php
$save_path = "./uploads";                               //文件保存路径
$max_size = 1000000;                                    //上传文件最大值
$allow_type = array('gif','png','jpg','jpeg');          //允许上传的类型

//判断保存的目录是否存在，如果不存在则创建保存目录
if(!is_dir($save_path))
        mkdir($save_path);

//判断文件是否上传成功
if($_FILES['myfile']['error']){
        echo "文件上传失败<br>";
        switch($_FILES['myfile']['error']){
                case 1: die('上传的文件超出系统的最大值<br>');break;
                case 2: die('上传的文件超出表单允许的最大值<br>');break;
                case 3: die('文件只有部分被上传<br>');break;
                case 4: die('没有上传任何文件<br>');break;
                default: die('未知错误<br>');break;
        }   
}

//通过文件的后缀判断是否为合法的文件名
$hz = array_pop(explode('.',$_FILES['myfile']['name']));
if(!in_array($hz,$allow_type)){
        die("该类型不允许上传<br>");
}

//判断文件是否超过允许的大小
if($max_size < $_FILES['myfile']['size']){
        die("文件超出PHP允许的最大值<br>");
}

//为了防止文件名重复，在系统中使用新名称
$save_file_name = date('YmdHis').rand(100,900).'.'.$hz;

//判断是否为HTTP POST上传的，如果是则把文件从临时目录移动到保存目录，并输出保存的信息；
if(is_uploaded_file($_FILES['myfile']['tmp_name'])){
        if(move_uploaded_file($_FILES['myfile']['tmp_name'],$save_path.'/'.$save_file_name)){
                echo "上传成功!<br>文件{$_FILES['myfile']['name']}保存在{$save_path}/{$save_file_name}!<br>";
        }
        else{
                echo "文件移动失败!<br>";
        }
}
else{
        die("文件{$_FILES['myfile']['name']}不是一个HTTP POST上传的合法文件");
}
?>
<html>
<head>
        <title>uploadfile</title>
        <meta http-equiv="content-type" content="utf-8">
</head>
<body>
        <form method="POST" action="uploadfile.php" enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
                选择文件：<input type="file" name="myfile" ><br><br>
                <input type="submit" name="sub" value="upload">
        </form>
</body>
</html>
