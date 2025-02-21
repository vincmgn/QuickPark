# QuickPark API

## Initial setup

1. Install dependencies

```bash
composer install
```

2. Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

3. Create the database

```bash
php bin/console doctrine:database:create
```

4. Apply the schema

```bash
php bin/console doctrine:schema:update --force
```

5. Create DataFixtures

```bash
php bin/console doctrine:fixtures:load
```

6. Start docker

```bash
docker-compose up -d
```

## GitFlow

Find the GitFlow just [here](https://github.com/vincmgn/QuickPark/network).

## License

QuickPark is available under the MIT License.

## Credits

Fork [Symfony Docker](https://github.com/dunglas/symfony-docker)
