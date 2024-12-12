SERVICE=app

DOCKER_COMPOSE=docker compose

shell:
	$(DOCKER_COMPOSE) exec -it $(SERVICE) bash

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart: down up
