# Details

## Goal
This test goal is to show the difference between synchronous and asynchronous interaction. For example, DB writes is slower on weaker hardware. As a result worker performs less useful work while waiting for a synchronous operation result. 

*As a bonus, an example of the implementation of the task on golang is given*

## Workflow
* Add 100.000 messages into RabbitMQ, each message is a command (represented by a peak on chart start);
* Received command handler opens a PostgreSQL 11 transaction, inserts an entry into DB, publishes a message (event) into RabbitMQ and commits the transaction;
* Received event does not induce any load - it is just ACKed and log message.

## Conditions
* **Intel i7 8700, 16gb DDR4, SSD** 
* All the logging is disabled;
* Every application has only 1 instance (single process);
* I did not found how to change QoS settings in [symfony/messenger](https://github.com/symfony/messenger) so in both apps default ones are used;
* PostgreSQL 11 (500 connections limit);
* RabbitMQ 3.7.7;
* PHP 7.4;
* For [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus) following PHP extensions were installed: raphf, pq, sockets, event, ext-buffer.

**@see**: [Cooperative multitasking](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)

# Testing

*The estimated execution time is indicated (considering the delay in updating the schedule).*

#### Pure php (Time spent: ~ **4m20s**)
* Without any dependencies!
![pure](https://github.com/php-service-bus/performance-comparison/blob/v4.0/results/pure4.20.gif)

#### [symfony/messenger](https://github.com/symfony/messenger) (Time spent: ~ **7m25s**)
![symfony/messenger](https://github.com/php-service-bus/performance-comparison/blob/v4.0/results/messenger7.25.gif)

#### [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus) (Time spent: ~ **1m**)
![symfony/messenger](https://github.com/php-service-bus/performance-comparison/blob/v4.0/results/service-bus1m.gif)

#### Golang (Time spent: ~ **0m 22s**)
![symfony/messenger](https://github.com/php-service-bus/performance-comparison/blob/v4.0/results/golang22.gif)