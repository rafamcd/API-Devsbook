<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;

class PostController extends Controller
{
    private $loggedUser;
    
    //usando o middleware de autenticação
    public function __construct()
    {
        $this->middleware('auth:api');

        //já no construtor eu pego as informações do usuário logado
        $this->loggedUser = auth()->user();
    }

    //ação de dar/tirar like (toggle)
    public function like($id) {
        $array = ['error' => ''];

        $postExists = Post::find($id);
        if($postExists) {

            //vendo se eu já dei like no post (caso sim, retirar o like)
            $isLiked = PostLike::where('id_post', $id)
                            ->where('id_user', $this->loggedUser['id'])
                            ->count();
            if($isLiked > 0) {
                
                //remover
                $pl = PostLike::where('id_post', $id)
                            ->where('id_user', $this->loggedUser['id'])
                            ->first();
                $pl->delete();
            } else {

                //adicionar
                $newPostLike = new PostLike();
                $newPostLike->id_post = $id;
                $newPostLike->id_user = $this->loggedUser['id'];
                $newPostLike->created_at = date('Y-m-d H:i:s');
                $newPostLike->save();
            }

            //retornando a quantidade de likes do post
            $likeCount = PostLike::where('id_post', $id)                            
                            ->count();
            $array['likeCount'] = $likeCount;


        } else {
            $array['error'] = 'Post não existe';
            return $array;
        }

        return $array;
    }

    //método para comentar em um post
    public function comment(Request $request, $id) {
        $array = ['error' => ''];

        $txt = $request->input('txt');

        $postExists = Post::find($id);
        if($postExists) {

            if($txt) {

                //criando o post
                $newComment = new PostComment();
                $newComment->id_post = $id;
                $newComment->id_user = $this->loggedUser['id'];
                $newComment->created_at = date('Y-m-d H:i:s');
                $newComment->body = $txt;
                $newComment->save();                

            } else {
                $array['error'] = 'Não enviou mensagem';
                return $array;    
            }

        } else {
            $array['error'] = 'Post não existe';
            return $array;
        }

        return $array;
    }
}
