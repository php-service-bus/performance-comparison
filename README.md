# Details

##### Goal
This test goal is to show the difference between synchronous and asynchronous interaction. For example, DB writes is slower on weaker hardware. As a result worker performs less useful work while waiting for a synchronous operation result. The difference is best seen on a simple PC or low-level server.

##### Workflow
* Add 100.000 messages into RabbitMQ, each message is a command (represented by a peak on chart start);
* Received command handler opens a PostgreSQL 11 transaction, inserts an entry into DB, publishes a message (event) into RabbitMQ and commits the transaction;
* Received event does not induce any load - it is just ACKed.

##### Settings
* Cloud: Digital Ocean droplets with pre-installed docker
* All the logging is disabled
* Every application has only 1 instance (single process);
* I did not found how to change QoS settings in [symfony/messenger](https://github.com/symfony/messenger) so in both apps default ones are used;
* PostgreSQL 11 (100 connections limit by default)
* RabbitMQ 3.7.7
* PHP 7.3;
* For [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus) following PHP extensions were installed: raphf, pq, sockets, event.

**@see**: [Cooperative multitasking](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)

# Testing

