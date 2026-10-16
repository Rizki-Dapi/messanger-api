.PHONY: tinker run-s run-c fresh-m run-m migrate controller model model-m middleware \
	route-l route-c config-c resource trait-r file-m repo interface-r interface-s service \
	req seeder test seed-r run-seeder test-r factory setup-packages install key-g jwt-secret \
	run-q run-d pint cache-c optimize psql-sh mongo-sh config cors api \
	exception dump

# Run tinker (interactive REPL)
tinker:
	php artisan tinker

# Run development server
run-s:
	php artisan serve

# Run custom composer script (usually "dev" in composer.json)
run-c:
	composer run

# Drop all tables and re-migrate. Example: make fresh-m options=--seed
fresh-m:
	php artisan migrate:fresh $(options)

# Run pending migrations
run-m:
	php artisan migrate

# Create a new migration file. Example: make migrate name=test
migrate:
	php artisan make:migration create_$(name)_table

# Create a controller in App/Http/Controllers/Api. Example: make controller path=test
controller:
	php artisan make:controller Api/$(shell echo $(path) | sed 's/Controller$$//')Controller $(options)

# Create a new model. Example: make model name=test options=-m (with migration)
model:
	php artisan make:model $(name) $(options)

# Create model with migration
model-m:
	php artisan make:model $(name) -m

# Create a new middleware. Example: make middleware name=test
middleware:
	php artisan make:middleware $(name)Middleware

# List all registered routes
route-l:
	php artisan route:list

# Clear route cache
route-c:
	php artisan route:clear

# Clear config cache
config-c:
	php artisan config:clear

# Create an API Resource. Example: make resource name=test
resource:
	php artisan make:resource $(name)Resource

# Create a relationship trait. Example: make trait-r path=test/test
trait-r:
	php artisan make:trait Trait/$(shell echo $(path) | sed 's|\(.*\)/\(.*\)|\1/\2Relationship|')

# Generic wrapper for any artisan make: command. Example: make file-m make=event name=test
file-m:
	php artisan make:$(make) $(name)

# Create a Repository class - uses native Laravel make:class (generic class
# generator), NOT a third-party package. Laravel has no dedicated
# make:repository command, so this is the closest built-in equivalent.
# Example: make repo name=test
repo:
	php artisan make:class Repositories/$(name)Repository
	php artisan make:interface Repositories/Interfaces/$(name)RepositoryInterface

# Create a repository interface - make:interface IS a native Laravel
# command (confirmed), unrelated to any package.
interface-r:
	php artisan make:interface Repositories/Interfaces/$(name)RepositoryInterface

# Create a service interface - same native command as above.
interface-s:
	php artisan make:interface Services/Interfaces/$(name)ServiceInterface

# Create a Service class - same reasoning as `repo` above (no native
# make:service command in Laravel, make:class is the closest equivalent).
# Example: make service name=test
service:
	php artisan make:class Services/$(name)Service

# Create a FormRequest for input validation. Example: make req name=test
req:
	php artisan make:request $(name)Request

# Create a new seeder. Example: make seeder name=Room
seeder:
	php artisan make:seeder $(name)Seeder

# Create a new test. Example: make test name=test options=--unit
test:
	php artisan make:test $(name)Test $(options)

# Run one specific seeder. Example: make seed-r name=Room
seed-r:
	php artisan db:seed --class=$(name)Seeder

seeder-r:
	php artisan db:seed --class=RoleSeeder
	php artisan db:seed --class=SuperAdminSeeder

# Run all seeders
run-seeder:
	php artisan db:seed

# Run the full test suite
test-r:
	php artisan test

test-r-f:
	php artisan test --filter $(name)Test

# Create a new factory. Example: make factory name=test
factory:
	php artisan make:factory $(name)Factory

# Initial setup after cloning / composer install: app key, JWT config + secret.
install:
	composer install
	composer require tymon/jwt-auth
	composer require mongodb/laravel-mongodb
	cp -n .env.example .env || true
	$(MAKE) key-g
	php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
	php artisan jwt:secret

# (Re)generate APP_KEY
key-g:
	php artisan key:generate

# Publish JWT config only (if you need to re-publish without a full install)
jwt-secret:
	php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
	php artisan jwt:secret

# Run the queue worker - MUST be kept running in a separate terminal during
# (email verification, password reset) are processed through the queue.
run-q:
	php artisan queue:work --tries=3 --backoff=5

# Run server + queue worker + log viewer together in one terminal
run-d:
	npx concurrently -c "#93c5fd,#c4b5fd,#fb7185" \
		"php artisan serve" \
		"php artisan queue:listen --tries=1" \
		"php artisan pail --timeout=0" \
		--names=server,queue,logs

# Format code to Laravel's style standard (Pint)
pint:
	./vendor/bin/pint

# Clear ALL caches at once (config, route, view, application)
cache-c:
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan cache:clear

# Cache config & routes for performance - use before deploying, NEVER
# during development (config changes won't be picked up once cached)
optimize:
	php artisan config:cache
	php artisan route:cache
	php artisan event:cache

# Create a service provider. Example: make provider name=Repository
provider:
	php artisan make:provider $(name)Provider

# Create a config file. NOTE: unconfirmed whether `make:config` exists as
# a native Artisan command in this Laravel version - verify before relying
# on it; config files can always be created manually under config/ instead.
config:
	php artisan make:config $(name)

# Publish the default cors.php config (not included by default since
# Laravel 11) - only needed if this API will be called from a browser on
# a different origin (not needed for pure mobile-app consumption).
cors:
	php artisan config:publish cors

# !! DO NOT RUN on this project !! `install:api` bundles Laravel Sanctum
# and regenerates routes/api.php from a template - both of which conflict
# with decisions already made here (Sanctum was deliberately removed,
# routes/api.php is hand-written for JWT auth). Kept only as a reference
# for what a stock Laravel API setup would look like on a DIFFERENT project.
api:
	php artisan install:api

# Create a custom exception. Example: make exception name=RoomNotAvailable
exception:
	php artisan make:exception $(name)Exception

# Regenerate the Composer autoloader
dump:
	composer dump-autoload

policy:
	php artisan make:policy $(name)Policy --model=$(model)

enum:
	php artisan make:enum Enums/$(name)Enum

event:
	php artisan make:event $(name)Event

notif:
	php artisan make:notification $(name)Notification

listener:
	php artisan make:listener $(name)Listener --event=$(event)Event

rule:
	php artisan make:rule $(name)Rule

reverb-s:
	php artisan reverb:start

prune:
	php artisan model:prune --pretend

work-r:
	php artisan schedule:work