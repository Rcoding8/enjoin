<?php

namespace Enjoin1;

use Illuminate\Support\Facades\Facade;

class EnjoinFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'enjoin';
    }

} // end of class
