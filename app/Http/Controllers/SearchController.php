<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use app\Models\User;

class SearchController extends Controller
{
    private $loggedUser;
    
    //usando o middleware de autenticação
    public function __construct()
    {
        $this->middleware('auth:api');

        //já no construtor eu pego as informações do usuário logado
        $this->loggedUser = auth()->user();
    }

    public function search(Request $request) {
        $array = ['error' => '', 'users' => []];

        $txt = $request->input('txt');

        if($txt) {

            //Busca de Usuários
            $userList = User::where('name', 'like', '%'.$txt.'%')->get();
            foreach($userList as $userItem) {
                $array['users'][] = [
                    'id' => $userItem['id'],
                    'name' => $userItem['name'],
                    'avatar' => url('media/avatars/'.$userItem['avatar'])
                ];
            }

        } else {
            $array['error'] = 'Digite algo para buscar.';
            return $array;
        }

        return $array;
    }
}
