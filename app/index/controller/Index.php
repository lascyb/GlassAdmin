<?php

declare(strict_types=1);

namespace app\index\controller;

use phinx\InstallState;

class Index
{
    public function index()
    {
        InstallState::ensureLockIfAlreadyProvisioned();
        if (!InstallState::isInstalled() && InstallState::installAppExists()) {
            return redirect('/install');
        }

        return '';
    }
}
