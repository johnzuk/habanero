## Habanero
clone repository
cp app/config.yaml.dist app/config.yaml
set valid parameters in to config.yaml
composer install
php vendor/bin/doctrine orm:schema-tool:create

cd web/
npm install
bower install
gulp

done