<?php

namespace App\Http\Controllers;

use App\Traits\ApiTransformer;
use App\Traits\AppNotifications;
use App\Traits\ActorProfile;
use App\Traits\Helpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, ApiTransformer, AppNotifications, Helpers, ActorProfile;
}
