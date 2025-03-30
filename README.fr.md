# QuickPark API

Bienvenue sur l'API de **Quickpark** ! Quickpark se veut être une application où les propriétaires de places de parking peuvent louer leur place à des particuliers qui bien souvent sont à la recherche d'une place dans un monde où celles-ci se font de plus en rares.

## 🎯 Comment la tester ?

Pour tester notre projet, nous avons mis en place une procédure pour que vous puissiez avoir accès à notre **API**, directement en local sur votre machine.

### Prérequis

-   **Docker**
-   **Windows WSL** (recommandé) ou **MacOs Terminal** ou **Linux**

### Mise en place

Tout d'abord, vous devez ouvrir un terminal fonctionnant avec l'interpréteur de commande **BASH**.

1. Vérifier que **Docker** est bien lancé sur votre machine. Si vous avez l'application desktop, vérifiez qu'elle tourne sinon vous pouvez tester en faisant:

```bash
sudo systemctl status docker
```

2. Lancer la création de notre container **Docker**

```bash
docker-compose up -d
```

_💡 L'installation peut prendre un peu de temps la première fois car il faut que votre machine télécharge l'image officielle de **Symfony**, **PostgreSQL**, et d'autres dépendances que nous utilisons dans notre projet._

3. Une fois le container 'Started', nous allons nous rendre à l'intérieur de celui-ci pour effectuer les commandes futures :

```bash
docker exec -it quickpark-php-1 bash
```

4. Il nous faut donc installer les dépendances via l'outil **Composer**.

```bash
composer install
```

_💡 Pensez à vérifier que vous êtes bien à l'intérieur de votre container en tapant cette commande. Vous devriez voir apparaître au début de votre terminal quelque chose comme `root@xxxxxxxxxxxx:/app#`. Si ce n'est pas le cas, retour à l'étape 3._

5. Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

6. Il nous faut vérifier que notre base de données va utiliser la bonne structure, aussi appelée **schéma**.

```bash
php bin/console doctrine:schema:update --force
```

7. Ensuite, pour tester notre **API** avec un jeu de données complet, il nous faut peupler notre base de données.

```bash
php bin/console doctrine:fixtures:load
```

8. Et voilà ! Notre **API** est prête à être testée. Pour cela, rien de plus simple, rendez-vous à l'adresse [https://localhost/api/doc](https://localhost/api/doc) sur votre navigateur préféré. <br><br>
   _💡Si votre navigateur vous informe que la connexion n'est pas sécurisée, faites "Continuer quand même". Vous ne craignez rien car vous êtes en **localhost** ce qui signifie que le service tourne sur votre propre machine. Nous n'avons juste pas mis en place de système de certificats auto-signés, c'est pour ça que l'erreur apparaît._ <br><br>
   Vous pouvez désormais profiter des documentations et des exemples intégrés directement au **Swagger** pour tester les fonctionnalités de notre **API**.

9. Avant d'utiliser nos routes, il faut commencer par la route **login** où vous devrez vous connecter avec les identifiants suivants :

-   Admin:

    -   **username**: 'adminDemo'
    -   **password**: 'password'

-   User:
    -   **username**: 'userDemo'
    -   **password**: 'password'

Il faut ensuite copier le texte du champ `token` :

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NDI5Mzk5MzYsImV4cCI6MTc0Mjk0MzUzNiwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6InZpbnZpbiJ9.CKb3UbcRBJUE_KKGpNEC7x8GBTyq7xncYZCMbcwWsC3Ipt2bWNX8pPROlXosE5axVwoP-F5-6xo86BzZdGCBJ_p9B6udnDXVSYgZzWPZoJKmR5o708ZseeNwHQBUSvtNPX4GIHGGHSaJ4cxQUeBr66u3RFbZBUSsb-TGunMtCOTbHlibrrMt3xhjH2a9-c2gYq6R-3jnie2eTi8Q-43iWcOhqDZ-52f7JibFN7HzmygzTVKEzuWALh-IhvZoHMm6Qx85blz8piF3ROT3vx_R3b1tOdDSkx1dpWLRgyXCkT_zrq1_gkMaBoju_ct8m2TN2QCLMxZd1oGg2Dg1BiXzCQ",
    "refresh_token": "0f413b5750f690f5f6c66d3f2096cb41716f0c6330bb9a48c7b019ca30fb2df984f805b1d9edd06155bc412f28ea5cb4d3ef5891de023e8c1944846411709602"
}
```

Dans cet exemple c'est le texte `ey...zCQ` qu'il faut copier puis aller coller dans le champ **Authorize** tout en haut de la page web du **Swagger**. Une fois ce token entré, vous pouvez commencer à utiliser les autres routes 🥳

### Medias

Pour tester les routes qui nécessitent d'envoyer des fichiers, vous pouvez utiliser l'outil **Postman**. Il vous suffit de créer une nouvelle requête de type **POST**, ajouter une **Authorization** de type **Bearer Token** et coller le token. Ensuite, dans l'onglet **Body**, vous pouvez choisir le type de requête **form-data** et ajouter un champ avec le nom `media` et le type `File`. Vous pouvez ensuite choisir un fichier sur votre machine pour l'envoyer.

Lors d'un "GET" sur la route `/api/media/{id}`, vous avez la possibilité d'accéder à l'image envoyée précédemment. Il vous suffit de copier l'URL dans votre navigateur pour voir l'image.

### Pour aller plus loin (côté des développeurs expérimentés 😉)

#### 🧪 Tests

Vous pouvez également vérifier l'intégrité et le bon fonctionnement de nos routes en exécutant la procédure de tests unitaires que nous avons conçue pour notre **API**. Pour ce faire, rendez-vous dans le container :

```bash
docker exec -it quickpark-php-1 bash
```

Puis exécutez la commande suivante :

```bash
php vendor/bin/phpunit tests
```

#### 💾 Base de données

Pour accéder à la base de données, vous devez d'abord obtenir le port sur lequel le container fonctionne :

```bash
docker port quickpark-database-1
```

Vous devriez voir une sortie similaire à celle-ci :

```bash
5432/tcp -> 0.0.0.0:65193
```

Dans cet exemple, votre container de base de données fonctionne sur le port **65193**. Pour visualiser la base de données, vous aurez besoin d'un logiciel de gestion de base de données. Nous recommandons **Beekeeper Studio** car il est intuitif et open source. Vous pouvez le télécharger [ici](https://www.beekeeperstudio.io).

Une fois installé, ouvrez le logiciel et utilisez la configuration suivante :

-   **Hôte** : localhost
-   **Port** : 65193
-   **Utilisateur** : app
-   **Mot de passe** : !ChangeMe!

Et voilà ! Vous avez maintenant accès à la base de données de l'API. Profitez-en ! 🥳

## License

**QuickPark** is available under the **MIT License**.

## Credits

Fork [**Symfony Docker**](https://github.com/dunglas/symfony-docker)
