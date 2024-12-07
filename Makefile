SERVICE=app

DOCKER_COMPOSE=docker compose

shell:
	$(DOCKER_COMPOSE) exec -it $(SERVICE) bash
