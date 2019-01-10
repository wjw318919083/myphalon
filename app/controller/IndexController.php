<?php

class IndexController extends BaseController
{
    public function index()
    {
        echo 'this is myphalcon';    
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

    public function index2()
    {
        echo 3311;
        echo 2222;
        echo 1111;
    }

}
