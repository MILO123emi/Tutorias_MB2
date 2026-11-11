<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PerfilTutor;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $tutores = PerfilTutor::with('user')
            ->where('estado', 'APROBADO')
            ->paginate(15);

        return response()->json($tutores);
    }
}
