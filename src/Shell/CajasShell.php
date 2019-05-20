<?php

namespace Cajas\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;

class CajasShell extends Shell {
    
    var $buildJsFilename     = WWW_ROOT . 'js' . DIRECTORY_SEPARATOR . 'cajas.js';
    var $buildJsFilenameMin  = WWW_ROOT . 'js' . DIRECTORY_SEPARATOR . 'cajas.min.js';
    var $buildJsFilenameTest = WWW_ROOT . 'js' . DIRECTORY_SEPARATOR . 'cajas.test.js';
    
    var $cacheDir = CACHE . 'cajas';
    
    var $cacheDirFiles = CACHE . 'cajas' . DIRECTORY_SEPARATOR . 'files';
    var $cacheDirBuild = CACHE . 'cajas' . DIRECTORY_SEPARATOR . 'build';
    var $cacheDirSyntax= CACHE . 'cajas' . DIRECTORY_SEPARATOR . 'syntax';
    var $cacheDirMinify= CACHE . 'cajas' . DIRECTORY_SEPARATOR . 'minify';
     
    public function initialize()
    {
        parent::initialize();

        if (Configure::read('Cajas.BuildJsFilename') ) {
            $this->buildJsFilename = Configure::read('Cajas.BuildJsFilename');
        }
        
        //Create cache dirs
        foreach ([
            $this->cacheDirFiles,
            $this->cacheDirBuild,
            $this->cacheDirSyntax,
            $this->cacheDirMinify,
        ] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
        
        //touch cajas production js files
        foreach ([
            $this->buildJsFilename,
            $this->buildJsFilenameMin,
            $this->buildJsFilenameTest,
        ] as $file) {
            if (!file_exists($file)) {
                file_put_contents($file, '');
            }
        }
    }    
    
    public function main()
    {
        $this->help();
    }
    
    public function check($file = null){
        $this->out("<info>Check file [$file]</info>");
        $this->hr();
        $this->checkJsFile($file);
    }
    
    /**
     * Show help commands
     */
    public function help()
    {
        $this->out('<info>Cajas CLI tool</info>');
        $this->out('');
        $this->out(' - cajas build');
        $this->out('     Build all *.js files in the project under src/Template dir');
        $this->out('');
        $this->out(' - cajas check /path/to/file.ctp.js');
        $this->out('     Check one js file');
    }
    
    /**
     * Check JS file, check syntax and paths
     * 
     * @param string $file
     * @return Array
     */
    public function checkJsFile($file = null)
    {
        if (!$file) {
            $this->out("<error>Filename is empty</error>");
            
            return [
                'message' => 'File not exists',
                'error_code' => 255
            ];
        }
        
        if (!file_exists($file)) {
            
            $this->out("<error>ERROR:</error> File [$file] not found");
            
            return [
                'message' => 'File not exists',
                'error_code' => 255
            ];
        }
        
        $short_name = str_replace(APP, '', $file);
        $short_name2 = './src/' . $short_name;
           
        list($ctp_file, $ext) = explode('.', $short_name, 2);

        $ext_explode = explode('.', $ext);

        $js_func_name = 'cajas' . str_replace(['Template' , '/'], ['', '_'], $ctp_file);
        
        $js_func_crypt_name = md5($js_func_name);

        $md5 = md5_file($file);
        
        if ( file_exists($this->cacheDirSyntax . DIRECTORY_SEPARATOR . $md5 ) ) {
            //syntax ok
            $error_code = 0;
        } else {
            exec("node -c $file", $out, $error_code);
            
        }

        $content = '';
        $size = 0;
        if (!$error_code) {
            file_put_contents($this->cacheDirSyntax . DIRECTORY_SEPARATOR . $md5 , '');
            
            $content = file_get_contents($file);
            $size = strlen($content);
            $this->out("<success>Syntax OK</success>: [$short_name]");
        } else {
            $this->out( "<error>Syntax ERROR [$short_name]</error>" );
        }
        
        $js_file = compact([
            'js_func_name',
            'js_func_crypt_name',
            'md5',
            'ctp_file',
            'ext',
            'ext_explode',
            'file',
            'error_code',
            'content',
            'size'
        ]);
        
        return $js_file;
    }
    
    /**
     * Search all *.js files in the project
     * and compile them to one JS file
     */
    public function build()
    {
        $this->out('<info>Check files</info>');
        $this->hr();
        $dir = new Folder( APP . 'Template' );
        $files = $dir->findRecursive('.*\.js?');
        
        $js_files = [];
        
        foreach ($files as $file) {
            $js_file = $this->checkJsFile($file);
            
            if ( $js_file['error_code'] ) {
                $this->out('<error>Aborted</error>');
                exit($js_file['error_code']);        
            }
            
            $js_files[] = $js_file;
        }
                
        $cacheBuildFilePath  = $this->cacheDirBuild  . DIRECTORY_SEPARATOR . 'cajas.js';
        $cacheBuildFilePathTest  = $this->cacheDirBuild  . DIRECTORY_SEPARATOR . 'cajas.test.js';
        
        $this->out('');
        $this->info('Build');
        $this->hr();
        
        $resultJsContent = $resultJsContentTest = "/** Cajax build file. Could not edit. File generated automatically. <https://github.com/xv1t/cakephp-cajas> **/\n";
        
        foreach ($js_files as $js_file) {
            $test = in_array('test', $js_file['ext_explode']);
                        
            $functionContent = "function " . $js_file['js_func_name'] . "(id) {\n" .
                            $js_file['content'] . "\n}\n\n";
            
            if ($test) {
                $resultJsContentTest .= $functionContent;
                        
            } else {
                $resultJsContent .= $functionContent;
            }
        }
        
        file_put_contents($cacheBuildFilePath, $resultJsContent);
        file_put_contents($cacheBuildFilePathTest, $resultJsContentTest);
        
        foreach ([
            $this->checkJsFile($cacheBuildFilePath),
            $this->checkJsFile($cacheBuildFilePathTest),
        ] as $file) {
            if ($file['error_code']){                
                exit;
            }
        }
        
        $this->info('Write prod files');
        copy($cacheBuildFilePath    , $this->buildJsFilename);
        copy($cacheBuildFilePathTest, $this->buildJsFilenameTest);
        $this->success( date('c') . ': Build OK');
    }
}
