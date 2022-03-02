# Using the BMAC local docker env

You can use these files to run a local development environment with docker-compose (version 1.28 or newer).

To get started:


1)  Copy the setup.php.docker file from docker_templates into the application root.  
2)  From the root of the repository, run `docker compose up` or `docker compose up -d` if you want to leave the environment running in the background.  After this you'll be able to see the environment in the Docker Desktop application.
3)  The first time build will take some time, and your computer will be very busy while the container builds.  Once it is ready you can visit http://localhost:8080/install to set up Collective Access.
