# BileMo - Sélection de téléphones mobiles haut de gamme

## Presentation du projet

API REST BileMO réalisé avec [**Symfony 6.4 lts**](https://symfony.com/).

## Description

**BileMo** BileMo est une entreprise offrant toute une sélection de téléphones mobiles haut de gamme.

Vous êtes en charge du développement de la vitrine de téléphones mobiles de l’entreprise BileMo.
Le business modèle de BileMo n’est pas de vendre directement ses produits sur le site web, mais de fournir à toutes les plateformes qui le souhaitent l’accès au catalogue via une API (Application Programming Interface).
Il s’agit donc de vente exclusivement en B2B (business to business).

Il va falloir que vous exposiez un certain nombre d’API pour que les applications des autres plateformes web puissent effectuer des opérations.

Les clients de l’API sont authentifiés via JWT.

## Prè-requis

PHP
[**PHP 8.1**](https://www.php.net/downloads) ou supèrieur

MySQL
**MySQL 8.0** ou supèrieur.

Composer
[**Composer 2.6**](https://getcomposer.org/download/) ou supèrieur.

## Installation

Cloner le projet

`https://github.com/D-Jerome/P7-BileMo.git`

Installer les dépendances

`composer install`

_Variables d'environnements_ : Configurer un fichier _.env.local_:

```Dotenv
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
```

Pour une mise en production :

```Dotenv
APP_ENV=prod
```

Créer les clefs SSL [_aide_](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst#generate-the-ssl-keys)

`symfony console lexik:jwt:generate-keypair`

Configurer les chemins vers les clefs et la passphrase dans le _env.local_ [_aide_](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst#configuration):

```dotenv
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=yourPassphrase
```

Plus d'infos : [Documentation officielle JWT](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst#getting-started)

Créez la base de données et les tables:

`symfony console doctrine:database:create`

`symfony console doctrine:migrations:migrate`

Charger les données initiales(fixtures)

`symfony console doctrine:fixtures:load`

Lancer le serveur symfony

`symfony server:start`

## Rôles

Les endpoints suivants nécessitent un rôle administrateur :

<span style="color:#78bc61">POST</span>
`/api/customers`

<span style="color:#e6c229">PUT</span>
`/api/customers/{id}`

<span style="color:#ed6a5a">DELETE</span>
`/api/customers/{id}`

Les endpoints suivants nécessitent un rôle administrateur de la compagnie(customer) :

<span style="color:#78bc61">POST</span>
`/api/users`

<span style="color:#e6c229">PUT</span>
`/api/users/{id}`

<span style="color: red">DELETE</span>
`/api/users/{id}`

Les [données initiales](https://github.com/D-Jerome/P7-BileMo/#installation) en detail:
Le mot de passe **password** pour tous les utilisateurs.
Le Username de tous les utilisateurs est aleatoire.
1 compte administrateur
1 compte administrateur de compagnie par compagnie(customer) créée
x comptes utilisateurs aléatoirement associés au compagnie(customer)

## Documentation

La documentation est disponible au lien suivant :

`/api/doc`
