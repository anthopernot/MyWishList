<?php
namespace mywishlist\views;

class TemplateView {

    public static function render($content) {
        $rootUri = \Slim\Slim::getInstance()->request->getRootUri();
        print <<<END
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <link rel="stylesheet" href="{$rootUri}/public/css/bootstrap.css">
        <title>MyWishlist</title>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark static-top">
            <div class="container">
                <a class="navbar-brand" href="{$rootUri}">MyWishList</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{$rootUri}">Accueil
                                <span class="sr-only">(current)</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">A propos</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Espace membres</a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#">Connexion</a>
                                <a class="dropdown-item" href="#">Inscription</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container">
            {$content}
        </div>

        <script src="{$rootUri}/public/js/jquery.slim.min.js"></script>
        <script src="{$rootUri}/public/js/bootstrap.min.js"></script>
    </body>
</html>
END;
    }
}