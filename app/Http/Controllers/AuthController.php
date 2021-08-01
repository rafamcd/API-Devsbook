<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    //usando o middleware de autenticação (exceto para login, criação de login e login não autorizado, que esses métodos não precisa estar logado pra acesasar)
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except'=>[
                'login',
                'create',
                'unauthorized'
                ]
            ]);
    }

    //unauthorized (retornando mensagem de usuário não autorizado)
    public function unauthorized() {
        return response()->json(['error' =>'Não autorizado'], 401);        
    }

    //fazendo o login
    public function login(Request $request) {
        $array = ['error' => ''];

        $email = $request->input('email');
        $password = $request->input('password');

        if($email && $password) {
            //tentar fazer o login
            $token = auth()->attempt([
                'email' => $email,
                'password' => $password
            ]);

            //caso não tenha conseguido fazer o login
            if(!$token) {
                $array['error'] = 'E-mail e/ou senha errados!';
                return $array;
            }

            //caso tenha conseguido fazer o login
            $array['token'] = $token;

            return $array;
        } 
        //se não enviou o email e senha
        $array['error'] = 'Dados não enviados!';
        return $array;
    }

    public function logout() {
        auth()->logout();
        return ['error' => ''];
    }

    public function refresh() {
        $token = auth()->refresh();
        return [
            'error' => '',
            'token' => $token
        ];
    }

    //criando um usuário
    public function create(Request $request) {
        $array = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $birthdate = $request->input('birthdate');

        //todos os itens são obrigatórios
        if($name && $email && $password && $birthdate) {
            
            //validando a data de nascimento
            if(strtotime($birthdate) === false) {
                $array['error'] = 'Data de Nascimento inválida.';    
                return $array;
            }

            //verificando se o email informado já existe
            $emailExists = User::where('email', $email)->count();
            if($emailExists === 0) {

                //gerando a senha
                $hash = password_hash($password, PASSWORD_DEFAULT);

                //criando o novo usuário
                $newUser = new User();
                $newUser->name = $name;
                $newUser->email = $email;
                $newUser->password = $hash;
                $newUser->birthdate = $birthdate;
                $newUser->save();

                //logando já o usuário no sistema
                $token = auth()->attempt([
                    'email' => $email,
                    'password' => $password
                ]);

                //se não existe o token
                if(!$token) {
                    $array['error'] = 'Ocorreu um erro para logar o usuário criado.';    
                    return $array;
                } 

                //se existe o token, devolvo pro array de retorno o token
                $array['token'] = $token;

            } else {
                $array['error'] = 'E-mail já cadastrado.';
                return $array;    
            }

        } else {
            $array['error'] = 'Não enviou todos os campos de cadastro.';
            return $array;
        }

        return $array;
    }
}
