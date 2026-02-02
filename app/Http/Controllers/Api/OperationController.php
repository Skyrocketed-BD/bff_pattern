<?php

namespace App\Http\Controllers;

use App\Models\main\User;
use Illuminate\Support\Facades\Auth;

class OperationController extends Controller
{
    public $user;
    public $kontraktor;
    public $id_kontraktor;
    public $initial;

    public function __construct()
    {
        $this->user          = Auth::user();
        $this->kontraktor    = User::with(['toKontraktor'])->whereIdUsers($this->user->id_users)->first();
        $this->id_kontraktor = $this->kontraktor->id_kontraktor ?? null;
        $this->initial       = $this->kontraktor->toKontraktor->initial ?? '---';
    }
}
