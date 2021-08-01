<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\PostComment;
use App\Models\PostLike;
use Image;

class FeedController extends Controller
{
    private $loggedUser;
    
    //usando o middleware de autenticação
    public function __construct()
    {
        $this->middleware('auth:api');

        //já no construtor eu pego as informações do usuário logado
        $this->loggedUser = auth()->user();
    }

    //criação de um post
    public function create(Request $request) {
        $array = ['error' => ''];

        //pegando as informações enviadas na requisição
        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        if($type) {

            switch($type) {
                case 'text':
                    if(!$body) {
                        $array['error'] = 'Texto não enviado';
                        return $array;
                    }
                    break;

                case 'photo':
                    if($photo) {

                        if(in_array($photo->getClientMimeType(), $allowedTypes)) {

                            //nome do arquivo
                            $fileName = md5(time().rand(0,9999)).'.jpg';
                
                            //pasta que sera armazenada as imagens
                            $destPath = public_path('/media/uploads');

                            //utilizando a biblioteca Image que baixamos e estamos dando Use no início do arquivo
                            //resize coloca um max-width de 800 com altura variável, mantendo a proporção do Ratio
                            $img = Image::make($photo->path())
                            ->resize(800, null, function($constraint) {
                                $constraint->aspectRatio();
                            })
                            ->save($destPath.'/'.$fileName);

                            //preenchendo o nome do arquivo no body
                            $body = $fileName;

                        } else {
                            $array['error'] = 'Arquivo não suportado.';
                            return $array;   
                        }

                    } else {
                        $array['error'] = 'Arquivo não enviado';
                        return $array;
                    }
                    break;
                default:
                        $array['error'] = 'Tipo de Postagem inexistente';
                        return $array;
                    break;
            }

            //fazendo a postagem
            if($body) {
                $newPost = new Post();
                $newPost->id_user = $this->loggedUser['id'];
                $newPost->type = $type;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->body = $body;
                $newPost->save();
            }

        } else {
            $array['error'] = 'Dados não enviados.';
            return $array;
        }

        return $array;
    }

    //leitura do feed (dos usuários que eu sigo incluindo meu usuário)
    public function read(Request $request) {
        
        $array = ['error' => ''];

        $page = intval($request->input('pagina'));
        $perPage = 2;

        //agora preciso ver as minhas postagens e também de quem eu sigo, ordenados pela data decrescente
        $users = [];
        $userList = UserRelation::where('user_from', $this->loggedUser['id']);
        foreach($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }
        //me incluindo na lista também
        $users[] = $this->loggedUser['id'];

        //pegar o post de toda lista de usuários ordenados por data desc, já com a paginação
        $postList = Post::whereIn('id_user', $users)
                        ->orderBy('created_at', 'desc')
                        ->offset($page * $perPage)
                        ->limit($perPage)
                        ->get();
        
        $total = Post::whereIn('id_user', $users)->count();
        $pageCount = ceil($total / $perPage);

        //preenchendo informações adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser['id']);
        
        //preenchendo o array de retorno
        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    //essa function passa por 2 rotas, vai ser o feed de um determinado usuário (caso eu mande o ID) ou o feed do usuário logado (caso eu não mande o ID)
    public function userFeed(Request $request, $id = false) {
        $array = ['error' => ''];

        $page = intval($request->input('pagina'));
        $perPage = 2;

        //pegando o id enviado (quando não enviado nenhum id, assume o id do usuário logado)
        if(!$id) {
            $id = $this->loggedUser['id'];
        }

        //pegar os posts do usuário ordenado pela data
        $postList = Post::where('id_user', $id)
                        ->orderBy('created_at', 'desc')
                        ->offset($page * $perPage)
                        ->limit($perPage)
                        ->get();
        
        $total = Post::where('id_user', $id)->count();
        $pageCount = ceil($total / $perPage);
        
        //preenchendo informações adicionais
        $posts = $this->_postListToObject($postList, $id);
        
        //preenchendo o array de retorno
        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    //transformando a lista de posts em objetos já  preenchidos com informações adicionais
    private function _postListToObject($postList, $loggedId) {

        foreach($postList as $postKey => $postItem) {
            
            //verificando se o post é do usuário logado
            if($postItem['id_user'] == $loggedId) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            //preenchendo informações do usuário
            $userInfo = User::find($postItem['id_user']);
            $userInfo['avatar'] = url('media/avatars/'.$userInfo['avatar']);
            $userInfo['cover'] = url('media/covers/'.$userInfo['cover']);
            $postList[$postKey]['user'] = $userInfo;

            //informações de likes do post
            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;

            //verificar se eu curti essa postagem
            $isLiked = PostLike::where('id_post', $postItem['id'])
                                ->where('id_user', $loggedId)
                                ->count();
            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;

            //preencher informações dos comentários do post
            $comments = PostComment::where('id_post', $postItem['id'])->get();
            foreach($comments as $commentKey => $comment) {
                $user = User::find($comment['id_user']);                
                $user['avatar'] = url('media/avatars/'.$user['avatar']);
                $user['cover'] = url('media/covers/'.$user['cover']);
                $comments[$commentKey]['user'] = $user;
            }
            $postList[$postKey]['comments'] = $comments;
        }

        
        return $postList;
    }

    public function userPhotos(Request $request, $id) {
        $array = ['error' => ''];

        $page = intval($request->input('pagina'));
        $perPage = 2;

        //pegar as fotos do usuário ordenado pela data
        $postList = Post::where('id_user', $id)
                        ->where('type', 'photo')
                        ->orderBy('created_at', 'desc')
                        ->offset($page * $perPage)
                        ->limit($perPage)
                        ->get();
        
        $total = Post::where('id_user', $id)->where('type', 'photo')->count();
        $pageCount = ceil($total / $perPage);
        
        //preenchendo informações adicionais
        $posts = $this->_postListToObject($postList, $id);

        //colocando a url no body para não mostrar apenas o nome do arquivo
        foreach($posts as $pkey => $post) {
            $posts[$pkey]['body'] = url('media/uploads/'.$post['body']);
        }
        
        //preenchendo o array de retorno
        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }
}
