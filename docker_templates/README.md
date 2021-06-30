# Using the BMAC local docker env

You can use these files to run a local development environment with docker-compose (version 1.28 or newer).

To get started:


1)  Create a directory to hold your mysql database files.  This will allow your database configuration to persist between builds.
2)  Copy docker_templates/env_example to .env so that docker-compose can find it.  If you are at the root of the repository on *nix like machines this would be `cp docker_templates/env_example .env`
3)  Edit .env and set the MYSQL_DATA parameter to the absolute path to the directory you created in step 1.  
4)  From the root of the repository, run `docker compose up` or `docker compose up -d` if you want to leave the environment running in the background.  After this you'll be able to see the environment in the Docker Desktop application.
5)  The first time build will take some time, and your computer will be very busy while the container builds.  Once it is ready you can visit http://localhost:8080/install to set up Collective Access.
