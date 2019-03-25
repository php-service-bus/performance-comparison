# Details

## Goal
This test goal is to show the difference between synchronous and asynchronous interaction. For example, DB writes is slower on weaker hardware. As a result worker performs less useful work while waiting for a synchronous operation result. 

## Workflow
* Add 100.000 messages into RabbitMQ, each message is a command (represented by a peak on chart start);
* Received command handler opens a PostgreSQL 11 transaction, inserts an entry into DB, publishes a message (event) into RabbitMQ and commits the transaction;
* Received event does not induce any load - it is just ACKed.

## Conditions
* **Intel i7 8700, 16gb DDR4, SSD** 
* All the logging is disabled;
* Every application has only 1 instance (single process);
* I did not found how to change QoS settings in [symfony/messenger](https://github.com/symfony/messenger) so in both apps default ones are used;
* PostgreSQL 11 (500 connections limit);
* RabbitMQ 3.7.7;
* PHP 7.3;
* For [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus) following PHP extensions were installed: raphf, pq, sockets, event, ext-buffer.

*MpS* - messages per second

**@see**: [Cooperative multitasking](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)

# Testing

#### [symfony/messenger](https://github.com/symfony/messenger) (Time spent: ~ **1m45s**)
![symfony/messenger](https://github.com/php-service-bus/performance-comparison/blob/v3.1/results/symfony(1.45).gif)

#### [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus) (Time spent: ~ **50s**)
![symfony/messenger](https://github.com/php-service-bus/performance-comparison/blob/v3.1/results/service-bus(0.50).gif)

#### Golang (Time spent: ~ **20s**)
![symfony/messenger](https://github.com/php-service-bus/performance-comparison/blob/v3.1/results/golang(0.20).gif)

