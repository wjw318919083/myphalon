<?php

use App\Common\Single;
class IndexController extends BaseController
{
    public function index()
    {
        // 生成Uuid
        // test
        // test1
        echo getUuid();die;
        $instance = Single::getInstance();
        var_dump($instance);die;

        $flag = new Ftp();
        $host='106.14.191.14';
        $user = 'root';
        $password = '12345678a!';
        $flag->connect($host,$user,$password);
        $re = $flag->upload('2.txt','/home/mcadmin/futurelinkhttp/1.txt');
        var_dump($re);die;
        
        $m = new HistoryHearModel;
        // $o = $m->findFirst(['id' => '1a74471e0e1e4159bdbd9fd9ae21d276']);
        $o = $m->findFirstById('1a74471e0e1e4159bdbd9fd9ae21d276');
        $data = [
            'id' => '1a74471e0e1e4159bdbd9fd9ae21d276',
            'is_usable' => 2,
            'modified' => '2019-01-18 08:00:00'
        ];
        $o->update($data);
        var_dump($m->getWriteConnection()->affectedRows());
    }

    public function test()
    {

        $dbmap = $this->di->getShared('dbmap')->toArray();
        foreach ($dbmap as &$value) {
            $value['object'] = humpWord($value['string']) . "Model";
        }
        $file = "./array.php";
        cache_write($file, $dbmap, 'return');
    }

    public function testdbmap()
    {
        $dbmap = new Phalcon\Config\Adapter\Php(ROOT . "conf/dbmap_demo.php");
        $dbmap = ($dbmap->toArray());
        $dbmap = array_merge($dbmap['edubase'], $dbmap['eduwork'], $dbmap['edulogs']);
        foreach ($dbmap as $key => $value) {
            $result[$value['object']] = $key;
        }
        $path = ROOT . "app/Model/";
        $ds = opendir($path);
        while ($fs = readdir($ds)) {
            if (!in_array($fs, ['.', '..', 'AppModel.php'])) {
                $class_name = rtrim($fs, '.php');
                $map = getArrVal($result, $class_name);
                if (empty($map)) {
                    var_dump($class_name . " not exist");
                    continue;
                }
                try {
                    $m = getModel(getArrVal($result, $class_name));
                } catch (\Exception $e) {
                    var_export($class_name . "  error");
                }
            }

        }
    }
}
