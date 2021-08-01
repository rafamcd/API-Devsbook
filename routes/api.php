<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ping', function(Request $request) {
    return ['pong' => true];
});



Route::get('/401','AuthController@unauthorized')->name('login'); //Rota que irá cair quando um usuário não logado tentar acessar um controller que necessita de autenticação

Route::post('/auth/login', 'AuthController@login'); //realiza o login e retorna como resultado o token
Route::post('/auth/logout', 'AuthController@logout'); //logout
Route::post('/auth/refresh', 'AuthController@refresh'); //refresh (serve para dar um refresh no token do usuário)

Route::post('/user', 'AuthController@create'); //criação de usuários - recebe os 4 parâmetros necessários (nome, email, senha e data de nascimento)

Route::put('/user', 'UserController@update'); //atualizar dados do usuário - recebe todos os parâmetros possíveis (exceto avatar e cover) para alterar os dados do usuário

Route::post('/user/avatar', 'UserController@updateAvatar'); //atualizar o avatar
Route::post('/user/cover', 'UserController@updateCover'); //atualizar a capa (cover)


Route::get('/feed', 'FeedController@read'); //leitura do feed (do meu usuário e dos usuários que eu sigo)
Route::get('/user/feed', 'FeedController@userFeed'); //pegar o feed do usuário logado
Route::get('/user/{id}/feed', 'FeedController@userFeed'); //pegar o feed de qualquer usuário
Route::post('/user/{id}/follow', 'UserController@follow'); //dar follow em um usuário
Route::get('/user/{id}/followers', 'UserController@followers'); //pegando a lista de seguidores de um usuário
Route::get('/user/{id}/photos', 'FeedController@userPhotos'); //pegando a lista de fotos de um usuário


Route::get('/user', 'UserController@read'); //pegar dados do usuário logado
Route::get('/user/{id}', 'UserController@read'); //pegar dados de qualquer usuário

Route::post('/feed', 'FeedController@create'); //criar uma postagem no feed

Route::post('/post/{id}/like', 'PostController@like'); //dar ou tirar like de um post

Route::post('/post/{id}/comment', 'PostController@comment'); //realizar um comentário em algum post

Route::get('/search', 'SearchController@search'); //realizar a pesquisa




