<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\Post;
use Image;

class UserController extends Controller
{
    private $loggedUser;
    
    //usando o middleware de autenticação
    public function __construct()
    {
        $this->middleware('auth:api');

        //já no construtor eu pego as informações do usuário logado
        $this->loggedUser = auth()->user();
    }

    //atualizar dados do usuário
    public function update(Request $request) {
        $array = ['error' => ''];

        //pegando os campos
        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user = User::find($this->loggedUser['id']);

        //se enviou o nome para ser alterado, eu altero
        if($name) {
            $user->name = $name;
        }

        //se enviou algum email para ser alterado, preciso ver se existe esse email já utilizado em outro registro, caso não exista, eu altero
        if($email) {
            
            //se o usuário enviou algum email para alterar que seja diferente do que ele tem hoje no cadastro
            if($email != $user->email) {
                
                $emailExists = User::where('email', $email)->count();
                
                //se não existir outro cadastro utilizando o email informado, eu altero
                if($emailExists === 0) {
                    $user->email = $email;
                } else {
                    $array['error'] = 'Email já existe!';
                    return $array;
                }
            }
        }

        //se o usuário mandou alguma data de nascimento para ser alterada, verificar se ela é válida
        if($birthdate) {

            if(strtotime($birthdate) === false) {
                $array['error'] = 'Data de nascimento inválida.';
                return $array;
            }

            $user->birthdate = $birthdate;
        }

        //alterando city
        if($city) {
            $user->city = $city;
        }

        //alterando work
        if($work) {
            $user->work = $work; 
        }

        //alterando password
        if($password && $password_confirm) {

            if($password === $password_confirm) {

                //alterando a senha
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;

            } else {
                $array['error'] = 'As senhas não batem.';
                return $array;
            }
        }

        $user->save();

        return $array;
    }

    //atualização da imagem do avatar
    public function updateAvatar(Request $request) {
        $array = ['error' => ''];

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if($image) {

            if(in_array($image->getClientMimeType(), $allowedTypes)) {

                //alterando o avatar
                
                //nome do arquivo
                $fileName = md5(time().rand(0,9999)).'.jpg';
                
                //pasta que sera armazenada as imagens
                $destPath = public_path('/media/avatars');

                //utilizando a biblioteca Image que baixamos (já cortando a imagem 200x200)
                $img = Image::make($image->path())
                            ->fit(200,200)
                            ->save($destPath.'/'.$fileName);

                //salvando a imagem no banco de dados (nome do arquivo)
                $user = User::find($this->loggedUser['id']);
                $user->avatar = $fileName;
                $user->save();

                //retornando a url da imagem inserida
                $array['url'] = url('/media/avatars/'.$fileName);

            } else {
                $array['error'] = 'Arquivo não suportado.';
                return $array;    
            }

        } else {
            $array['error'] = 'Arquivo não enviado.';
            return $array;
        }

        return $array;
    }

//atualização da imagem da capa (cover)
public function updateCover(Request $request) {
    $array = ['error' => ''];

    $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

    $image = $request->file('cover');

    if($image) {

        if(in_array($image->getClientMimeType(), $allowedTypes)) {

            //alterando o avatar
            
            //nome do arquivo
            $fileName = md5(time().rand(0,9999)).'.jpg';
            
            //pasta que sera armazenada as imagens
            $destPath = public_path('/media/covers');

            //utilizando a biblioteca Image que baixamos (já cortando a imagem 200x200)
            $img = Image::make($image->path())
                        ->fit(850,310)
                        ->save($destPath.'/'.$fileName);

            //salvando a imagem no banco de dados (nome do arquivo)
            $user = User::find($this->loggedUser['id']);
            $user->cover = $fileName;
            $user->save();

            //retornando a url da imagem inserida
            $array['url'] = url('/media/covers/'.$fileName);

        } else {
            $array['error'] = 'Arquivo não suportado.';
            return $array;    
        }

    } else {
        $array['error'] = 'Arquivo não enviado.';
        return $array;
    }

    return $array;
}

    //metodo para pegar as informações de um usuário (quer seja logado ou não, essa função é acessada por 2 rotas)
    public function read($id = false) {
        $array = ['error' => ''];

        //se enviou algum id
        if($id) {
            $info = User::find($id);
            if(!$info) {
                $array['error'] = 'Usuário inexistente.';
                return $array; 
            }
        } else {
            //se não enviou nenhum id significa que é para pegar as informações do usuário logado
            $info = $this->loggedUser;
        }

        //como eu preciso passar a URL do avatar, eu troco o info['avatar] para o endereço completo
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $info['cover'] = url('media/covers/'.$info['cover']);

        //vendo se o usuário logado é igual ao usuário que foi enviado na requisição
        $info['me'] = ($info['id'] == $this->loggedUser['id']) ? true : false;

        //calculando idade do usuário
        $dateFrom = new \DateTime($info['birthdate']);
        $dateTo = new \DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y;

        //quantidade de seguidores, seguindo e fotos
        $info['followers'] = UserRelation::where('user_to', $info['id'])->count();
        $info['following'] = UserRelation::where('user_from', $info['id'])->count();
        $info['photoCount'] = Post::where('id_user', $info['id'])
                                    ->where('type', 'photo')
                                    ->count();

        //vendo se o usuário logado segue ou não esse perfil que ele ta acessando
        $hasRelation = UserRelation::where('user_from', $this->loggedUser['id'])
                                    ->where('user_to', $info['id'])
                                    ->count();
        $info['isFollowing'] = ($hasRelation > 0) ? true : false;

        $array['data'] = $info;

        return $array;
    }

    //seguir um usuário
    public function follow($id) {
        $array = ['error' => ''];

        if($id == $this->loggedUser['id']) {
            $array['error'] = 'Você não pode seguir a si mesmo.';
            return $array; 
        }

        $userExists = User::find($id);
        if($userExists) {

            //se o usuário logado já segue essa pessoa, vai deixar de seguir, caso contrário, vai seguir
            $relation = UserRelation::where('user_from', $this->loggedUser['id'])
                                    ->where('user_to', $id)
                                    ->first();
            if($relation) {
                //parar de seguir
                $relation->delete();
            } else {
                //seguir
                $newRelation = new UserRelation();
                $newRelation->user_from = $this->loggedUser['id'];
                $newRelation->user_to = $id;
                $newRelation->save();
            }
                                

        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array; 
        }

        return $array;
    }

    //pegar a lista de seguidores de um usuário
    public function followers($id) {
        $array = ['error' => ''];

        $userExists = User::find($id);
        if($userExists) {

            $followers = UserRelation::where('user_to', $id)->get();
            $following = UserRelation::where('user_from', $id)->get();

            $array['followers'] = [];
            $array['following'] = [];

            foreach($followers as $item) {
                $user = User::find($item['user_from']);
                $array['followers'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => url('media/avatars/'.$user['avatar'])
                ];
            }

            foreach($following as $item) {
                $user = User::find($item['user_to']);
                $array['following'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => url('media/avatars/'.$user['avatar'])
                ];
            }

        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array; 
        }

        return $array;
    }

}


