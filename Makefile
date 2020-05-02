APP_NAME=sberprime
DOCKER_REPO=gotzmann

all:

# Up and run SberPrime API with local .env settings
up:

	#docker pull ${DOCKER_REPO}/${APP_NAME}
	docker run -d -p 80:80 --name=${APP_NAME} --net=host --env-file=.env --volumes=./log:/var/www/log ${DOCKER_REPO}/${APP_NAME}

# TODO Graceful shutdown of service
down:


# TODO Replace Docker Hub with internal registry
build:
push:

	docker build -f Dockerfile -t ${DOCKER_REPO}/${APP_NAME} --no-cache .
	#docker build -f Dockerfile -t ${DOCKER_REPO}/${APP_NAME} .
	docker push ${DOCKER_REPO}/${APP_NAME}

# TODO Migrate DB through Phinx files
migrate:

	php vendor/robmorgan/phinx/bin/phinx migrate
