To start consuming messages, run:

    php bin/console messenger:setup-transports
    php bin/console messenger:consume async --daemon



regular db setup:
    
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate

test db setup:
    
    php bin/console doctrine:database:create --env=test
    php bin/console doctrine:migrations:migrate --env=test


running tests:

    ./vendor/bin/phpunit --testdox tests

