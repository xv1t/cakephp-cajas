<?php

namespace Cajas\Shell;

use Cake\Console\Shell;

class CajasShell extends Shell {
    public function main()
    {
        $this->out('<warning>Fire!!!</warning>');
    }
}
