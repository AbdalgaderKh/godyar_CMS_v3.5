<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\App;

abstract class Controller
{
    public function __construct(protected App $app) {}
}
