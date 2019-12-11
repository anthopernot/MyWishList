<?php


namespace mywishlist\controllers;


use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use mywishlist\models\Liste;
use mywishlist\models\Message;

class MessageController extends Controller {

    /**
     * @todo: Fonction ajouter message a partir de données POST
     */
    public function addMessage($request, $response, $args) {
        try {
            $name = $request->getParsedBody()['name'];
            $message = $request->getParsedBody()['message'];
            $token = $request->getParsedBody()['token'];
            $liste = Liste::where('token', '=', $token)->first();
            if(is_null($liste)) {
                throw new Exception();
            }

            $m = new Message();
            $m->idListe = $liste->no;
            $m->message = $message;
            $m->messager = $name;
            $m->save();
            $response = FigResponseCookies::set($response, SetCookie::create("nom")->withValue($name)->rememberForever());
            $this->flash->addMessage('success', "$name, Votre message a été envoyé");
            $response = $response->withRedirect($this->router->pathFor('home'));
        } catch(Exception $e) {
            $this->flash->addMessage('error', 'Nous n\'avons pas pu envoyer votre message.');
            $response = $response->withRedirect($this->router->pathFor('home'));
        }
        return $response;
    }
}