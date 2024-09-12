# Variables
DOCKER_COMPOSE = docker-compose
COMMON_INFRA_DIR = ../nd-common-infra
COMPOSER = $(DOCKER_COMPOSE) run --rm laravel composer
ARTISAN = $(DOCKER_COMPOSE) run --rm laravel php artisan

# Targets
.PHONY: init up down logs check_common_infra composer artisan

# Initialize project (run nd-common-infra and perform necessary setup)
init: up
	$(COMPOSER) install
	$(ARTISAN) migrate --seed
	$(ARTISAN) key:generate

# Start containers
up: check_common_infra
	$(DOCKER_COMPOSE) up -d

# Stop and remove containers
down:
	$(DOCKER_COMPOSE) down

# Show logs
logs:
	$(DOCKER_COMPOSE) logs -f

# Check and start nd-common-infra if not running
check_common_infra:
	@if ! docker-compose -f $(COMMON_INFRA_DIR)/docker-compose.yml ps -q | grep -q .; then \
		echo "Starting common-infra..."; \
		cd $(COMMON_INFRA_DIR) && $(DOCKER_COMPOSE) up -d; \
	else \
		echo "common-infra is already running"; \
	fi

# Composer command with arguments
composer:
	$(COMPOSER) $(filter-out $@,$(MAKECMDGOALS))

# Artisan command with arguments
artisan:
	$(ARTISAN) $(filter-out $@,$(MAKECMDGOALS))

# Avoids error with undefined targets
%:
	@:
