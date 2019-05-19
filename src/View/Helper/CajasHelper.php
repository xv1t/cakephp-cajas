<?php

namespace Cajas\View\Helper;

use Cake\View\Helper;

class CajasHelper extends Helper
{
    public function afterRenderFile($event, $viewFile, $content)
    {
        $js_file = $viewFile . '.js';

        if (file_exists($js_file)) {

            $short_ctp_name = str_replace(
                [APP, '/Element/..', '/', '.ctp'], 
                ['', '', '_', ''], 
                $viewFile);
         
            $this->_View->append('cajas_after_script');
            echo "// $short_ctp_name \n";
            echo file_get_contents($js_file) . "\n\n";
            $this->_View->end();
        }
    }
}