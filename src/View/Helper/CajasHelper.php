<?php

namespace Cajas\View\Helper;

use Cake\View\Helper;

/**
 * Helper class for Cajas plugin
 * @link https://github.com/xv1t/cakephp-cajas Official web page
 */
class CajasHelper extends Helper
{
    var $ids = [];
    
    /**
     * Generate id for mark
     * 
     * @return string
     */
    public function id($ctp_file)
    {
        if (empty($this->ids[$ctp_file])) {
            $this->ids[$ctp_file]= 'cajas-' . uniqid() . '-id';
        }
        $link = $this->ids[$ctp_file];
        //debug($_View->_current);
        return $link;
    }
    
    /**
     * Generate func name from .ctp file path
     * 
     * @param type $viewFile
     * @return string
     */
    public function funcName($viewFile)
    {
        return 'cajas' . str_replace([APP, '/Element/..', 'Template', '/', '.ctp'],
                    ['', '','', '_', ''],
                $viewFile
                );
    }
    
    /**
     * Override Helper Event
     * 
     * @param type $event
     * @param type $viewFile
     * @param type $content
     */
    public function afterRenderFile($event, $viewFile, $content)
    {
        $js_file = $viewFile . '.js';
        if (file_exists($js_file)) {
            
            $funcName = $this->funcName($viewFile);

            $short_ctp_name = str_replace(
                [APP, '/Element/..', '/', '.ctp'], 
                ['', '', '_', ''], 
                $viewFile);
         
            $this->_View->append('cajas_after_script');
            //echo "// $short_ctp_name \n";
            //echo file_get_contents($js_file) . "\n\n";
            echo "$funcName('" . $this->id($viewFile) . "');\n";
            $this->_View->end();
        }
    }
}