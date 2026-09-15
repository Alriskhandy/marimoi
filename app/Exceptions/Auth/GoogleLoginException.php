<?php

namespace App\Exceptions\Auth;

use App\Models\User;
use Exception;

class GoogleLoginException extends Exception
{
    public function __construct(string $message, public readonly ?User $user = null)
    {
        parent::__construct($message);
    }
}
