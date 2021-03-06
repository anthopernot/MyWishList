<?php

namespace mywishlist\controllers;

use DateTime;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use mywishlist\models\Liste;
use mywishlist\models\Message;
use Slim\Http\Request;
use Slim\Http\Response;
use function mywishlist\models\Liste;

/**
 * Class ListeController
 * @author Jules Sayer <jules.sayer@protonmail.com>
 * @author Anthony Pernot <anthony.pernot9@etu.univ-lorraine.fr>
 * @author Nathan Chevalier <nathan.chevalier2@etu.univ-lorraine.fr>
 * @package mywishlist\controllers
 */
class ListeController extends CookiesController {

    /**
     * Appel liste.phtml, permet d'afficher les informations
     * d'une liste, ses items, ses messages et l'état des réservations
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getListe(Request $request, Response $response, array $args): Response {
        try {
            $liste = Liste::where('token', '=', $args['token'])->firstOrFail();
            $this->loadCookiesFromRequest($request);

            $can = [
                "canSee" => $liste->haveExpired() || !in_array($liste->creationToken, $this->getCreationTokens()),
                "haveExpired" => $liste->haveExpired(),
                "haveCreated" => in_array($liste->creationToken, $this->getCreationTokens())
            ];

            $this->view->render($response, 'liste.phtml', [
                "liste" => $liste,
                "items" => $liste->items()->get(),
                "messages" => $liste->messages()->get(),
                "nom" => $this->getName(),
                "infos" => $can
            ]);
        } catch (ModelNotFoundException $e) {
            $this->flash->addMessage('error', "Cette liste n'existe pas...");
            $response = $response->withRedirect($this->router->pathFor('home'));
        }
        return $response;
    }

    /**
     * Appel adminliste.phtml, permet d'afficher le
     * formulaire de modification d'une liste
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getAdminListe(Request $request, Response $response, array $args): Response {
        try {
            $liste = Liste::where(['token' => $args['token'], 'creationToken' => $args['creationToken']])->firstOrFail();
            $this->loadCookiesFromRequest($request);

            $this->view->render($response, 'adminliste.phtml', [
                "liste" => $liste,
                "items" => $liste->items()->get(),
                "uri" => $request->getUri(),
                "flash" => $this->flash->getMessages(),
                "showRes" => $this->getShowRes()
            ]);
        } catch (ModelNotFoundException $e) {
            $this->flash->addMessage('error', "Token invalide.");
            $response = $response->withRedirect($this->router->pathFor('home'));
        }
        return $response;
    }

    /**
     * Modifie si l'utilisateur qui a créé
     * une liste peut voir les reservation
     * avant échéance
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function showRes(Request $request, Response $response, array $args): Response {
        $this->loadCookiesFromRequest($request);
        $bool = filter_var($args['bool'], FILTER_VALIDATE_BOOLEAN);
        $this->changeShowRes($bool);

        $response = $this->createResponseCookie($response);
        $this->flash->addMessage('success', "Le mode d'affichage a été changé. Vous pouvez désormais voir si un objet a été réservé sur les listes que vous avez créé à partir de votre administration.");
        $response = $response->withRedirect($this->router->pathFor('home'));
        return $response;
    }

    /**
     * Permet d'ajouter un message publique à une liste
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function addMessage(Request $request, Response $response, array $args): Response {
        try {
            $name = filter_var($request->getParsedBodyParam('name'), FILTER_SANITIZE_STRING);
            $message = filter_var($request->getParsedBodyParam('message'), FILTER_SANITIZE_STRING);
            $token = filter_var($args['token'], FILTER_SANITIZE_STRING);

            if (mb_strlen($message, 'utf8') < 4) throw new Exception("Votre message doit comporter au minimum 4 caractères.");
            if (mb_strlen($name, 'utf8') < 2) throw new Exception("Votre nom doit comporter au minimum 2 caractères.");

            $liste = Liste::where('token', '=', $token)->firstOrFail();
            $this->loadCookiesFromRequest($request);

            $m = new Message();
            $m->idListe = $liste->no;
            $m->message = $message;
            $m->messager = $name;
            $m->save();

            $this->changeName($name);
            $response = $this->createResponseCookie($response);
            $this->flash->addMessage('success', "$name, Votre message a été envoyé");
            $response = $response->withRedirect($this->router->pathFor('home'));
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            $response = $response->withRedirect($this->router->pathFor('home'));
        }
        return $response;
    }

    /**
     * Créer une liste a partir d'une requête POST, retourne sur
     * l'accueil avec un flahs message, et ajoute le cookie de création
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function createListe(Request $request, Response $response, array $args): Response {
        try {
            $titre = filter_var($request->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING);
            $description = filter_var($request->getParsedBodyParam('descr'), FILTER_SANITIZE_STRING);
            $dateExp = $request->getParsedBodyParam('dateExpi');

            if (mb_strlen($titre, 'utf8') < 4) throw new Exception("Le titre de la liste doit comporter au minimum 4 caractères.");
            if (new DateTime() > new DateTime($dateExp)) throw new Exception("La date d'expiration ne peut être déjà passée..");

            $this->loadCookiesFromRequest($request);

            $liste = new Liste();
            $liste->user_id = 0;
            $liste->titre = $titre;
            $liste->description = $description;
            $liste->expiration = $dateExp;
            $liste->token = bin2hex(openssl_random_pseudo_bytes(32));
            $liste->creationToken = bin2hex(openssl_random_pseudo_bytes(12));
            $liste->public = 0;
            $liste->save();

            $this->addCreationToken($liste->creationToken);
            $response = $this->createResponseCookie($response);
            $link = $this->router->pathFor('showListe', ['token' => $liste->token]);
            $this->flash->addMessage('success', "Votre liste a été créée! Cliquez <a href='$link'>ici</a> pour y accéder.");
            $response = $response->withRedirect($this->router->pathFor('home'));
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            $response = $response->withRedirect($this->router->pathFor('home'));
        }
        return $response;
    }

    /**
     * Modifie la liste, vérifie que l'utilisateur a créé la liste
     * et qu'elle existe bel et bien.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function updateListe(Request $request, Response $response, array $args): Response {
        try {
            $titre = filter_var($request->getParsedBodyParam('newTitle'), FILTER_SANITIZE_STRING);
            $description = filter_var($request->getParsedBodyParam('newDescription'), FILTER_SANITIZE_STRING);
            $date = $request->getParsedBodyParam('newDate');
            $token = filter_var($args['token'], FILTER_SANITIZE_STRING);
            $creationToken = filter_var($args['creationToken'], FILTER_SANITIZE_STRING);
            $public = !is_null($request->getParsedBodyParam('public')) ? 1 : 0;

            if (!isset($titre, $description, $date, $token, $creationToken)) throw new Exception("Un des paramètres est manquant.");
            if (mb_strlen($titre, 'utf8') < 4) throw new Exception("Le titre de la liste doit comporter au minimum 4 caractères.");
            if (new DateTime() > new DateTime($date)) throw new Exception("La date d'expiration ne peut être déjà passée..");

            $liste = Liste::where(['token' => $token, 'creationToken' => $creationToken])->firstOrFail();

            $liste->titre = $titre;
            $liste->description = $description;
            $liste->expiration = $date;
            $liste->public = $public;
            $liste->save();

            $this->flash->addMessage('success', "Votre modification a été enregistrée!");
            $response = $response->withRedirect($this->router->pathFor('showAdminListe', ['token' => $token, 'creationToken' => $creationToken]));
        } catch (ModelNotFoundException $e) {
            $this->flash->addMessage('error', "Impossible de modifier la liste.");
            $response = $response->withRedirect($this->router->pathFor('showAdminListe', ['token' => $token, 'creationToken' => $creationToken]));
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            $response = $response->withRedirect($this->router->pathFor('showAdminListe', ['token' => $token, 'creationToken' => $creationToken]));
        }
        return $response;
    }
}