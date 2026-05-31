<?php

class App
{

    protected $module = "dashboard";
    protected $action = "index";
    protected $params = [];

    public function __construct()
    {

        $url = $this->parseUrl();

        // MODULE
        if(isset($url[0]))
        {
            $this->module = $url[0];
            unset($url[0]);
        }

        // ACTION
        if(isset($url[1]))
        {
            $this->action = $url[1];
            unset($url[1]);
        }

        // PARAMETER
        $this->params = $url ? array_values($url) : [];

        $this->run();
    }


    private function parseUrl()
    {

        if(isset($_GET['url']))
        {
            return explode("/", filter_var(rtrim($_GET['url'],"/"), FILTER_SANITIZE_URL));
        }

        return [];
    }


    private function run()
    {

        $module_file = "modules/".$this->module."/controller.php";

        if(file_exists($module_file))
        {
            require $module_file;

            $controller = ucfirst($this->module)."Controller";

            if(class_exists($controller))
            {
                $object = new $controller;

                if(method_exists($object,$this->action))
                {
                    call_user_func_array([$object,$this->action],$this->params);
                }
                else
                {
                    echo "Action tidak ditemukan";
                }
            }
        }
        else
        {
            echo "Module tidak ditemukan";
        }

    }

}