# QuickPark API

Welcome to the **QuickPark** API! QuickPark aims to be an application where parking space owners can rent out their spaces to individuals who are often looking for a spot in a world where they are becoming increasingly scarce.

## 🎯 How to Test It?

To test our project, we have set up a procedure so that you can access our **API** directly on your local machine.

### Prerequisites

-   **Docker**
-   **Windows WSL** (recommended) or **MacOS Terminal** or **Linux**

### Setup

First, you need to open a terminal that works with the **BASH** command interpreter.

1. Ensure that **Docker** is running on your machine. If you have the desktop application, make sure it is running, otherwise, you can check by running:

```bash
sudo systemctl status docker
```

2. Start the creation of our **Docker** container:

```bash
docker-compose up -d
```

_💡 The installation may take some time the first time as your machine needs to download the official **Symfony**, **PostgreSQL**, and other dependencies we use in our project._

3. Once the container is 'Started', we will enter it to execute future commands:

```bash
docker exec -it quickpark-php-1 bash
```

4. We need to install the dependencies using **Composer**:

```bash
composer install
```

_💡 Make sure you are inside your container by typing this command. You should see something like `root@xxxxxxxxxxxx:/app#` at the beginning of your terminal. If not, go back to step 3._

5. Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

6. We need to ensure that our database uses the correct structure, also known as the **schema**:

```bash
php bin/console doctrine:schema:update --force
```

7. Next, to test our **API** with a complete dataset, we need to populate our database:

```bash
php bin/console doctrine:fixtures:load
```

8. And that's it! Our **API** is ready to be tested. To do this, simply go to [https://localhost/api/doc](https://localhost/api/doc) in your favorite browser. <br><br>
   _💡 If your browser informs you that the connection is not secure, click "Continue anyway". You are safe because you are on **localhost**, which means the service is running on your own machine. We just haven't set up a self-signed certificate system, which is why the error appears._ <br><br>
   You can now enjoy the documentation and integrated examples directly in **Swagger** to test the features of our **API**.

9. Before using our routes, you need to start with the **login** route where you will log in with the following credentials:

-   Admin:

    -   **username**: 'adminDemo'
    -   **password**: 'password'

-   User:
    -   **username**: 'userDemo'
    -   **password**: 'password'

Then you need to copy the text from the `token` field:

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NDI5Mzk5MzYsImV4cCI6MTc0Mjk0MzUzNiwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6InZpbnZpbiJ9.CKb3UbcRBJUE_KKGpNEC7x8GBTyq7xncYZCMbcwWsC3Ipt2bWNX8pPROlXosE5axVwoP-F5-6xo86BzZdGCBJ_p9B6udnDXVSYgZzWPZoJKmR5o708ZseeNwHQBUSvtNPX4GIHGGHSaJ4cxQUeBr66u3RFbZBUSsb-TGunMtCOTbHlibrrMt3xhjH2a9-c2gYq6R-3jnie2eTi8Q-43iWcOhqDZ-52f7JibFN7HzmygzTVKEzuWALh-IhvZoHMm6Qx85blz8piF3ROT3vx_R3b1tOdDSkx1dpWLRgyXCkT_zrq1_gkMaBoju_ct8m2TN2QCLMxZd1oGg2Dg1BiXzCQ",
    "refresh_token": "0f413b5750f690f5f6c66d3f2096cb41716f0c6330bb9a48c7b019ca30fb2df984f805b1d9edd06155bc412f28ea5cb4d3ef5891de023e8c1944846411709602"
}
```

In this example, it is the text `ey...zCQ` that you need to copy and paste into the **Authorize** field at the top of the **Swagger** web page. Once this token is entered, you can start using the other routes 🥳

### Medias

To test routes that require sending files, you can use the **Postman** tool. Simply create a new **POST** request, add an **Authorization** of type **Bearer Token**, and paste the token. Then, in the **Body** tab, you can select the **form-data** request type and add a field named `media` with the type `File`. You can then choose a file from your machine to send.

When performing a "GET" request on the route `/api/media/{id}`, you have the option to access the previously uploaded image. Simply copy the URL into your browser to view the image.

### For Advanced Users (Experienced Developers 😉)

#### 🧪 Tests

You can also verify the integrity and proper functioning of our routes by running the unit test procedure we have designed for our **API**. To do this, simply go to the container:

```bash
docker exec -it quickpark-php-1 bash
```

Then run the following command:

```bash
php vendor/bin/phpunit tests
```

#### 💾 Database

To access the database, you must first obtain the port the container is running on:

```bash
docker port quickpark-database-1
```

You should see output similar to this:

```bash
5432/tcp -> 0.0.0.0:65193
```

In this example, your database container is running on port **65193**. To view the database, you will need database management software. We recommend **Beekeeper Studio** because it is intuitive and open source. You can download it [here](https://www.beekeeperstudio.io).

Once installed, open the software and use the following configuration:

-   **Host**: localhost
-   **Port**: 65193
-   **User**: app
-   **Password**: !ChangeMe!

That's it! You now have access to the API database. Enjoy! 🥳

## License

**QuickPark** is available under the **MIT License**.

## Credits

Fork [**Symfony Docker**](https://github.com/dunglas/symfony-docker)
