# Details

##### Goal
This test goal is to show the difference between synchronous and asynchronous interaction. For example, DB writes is slower on weaker hardware. As a result worker performs less useful work while waiting for a synchronous operation result. The difference is best seen on a simple PC or low-level server.

##### Workflow
* Add 100.000 messages into RabbitMQ, each message is a command (represented by a peak on chart start);
* Received command handler opens a PostgreSQL 11 transaction, inserts an entry into DB, publishes a message (event) into RabbitMQ and commits the transaction;
* Received event does not induce any load - it is just ACKed.

##### Conditions
* Cloud: Digital Ocean droplets with pre-installed docker;
* All the logging is disabled;
* Every application has only 1 instance (single process);
* The description shows **the peak values** visible on the graph. Real values below, **I recommend to watch videos** of a specific test
* I did not found how to change QoS settings in [symfony/messenger](https://github.com/symfony/messenger) so in both apps default ones are used;
* PostgreSQL 11 (100 connections limit by default);
* RabbitMQ 3.7.7;
* PHP 7.3;
* For [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus) following PHP extensions were installed: raphf, pq, sockets, event.

*MpS* - messages per second

**@see**: [Cooperative multitasking](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)

# Testing

## CPU Optimized Droplet ($160, 16GB/8CPUs)

#### [symfony/messenger](https://github.com/symfony/messenger)
* Time spent: ~ 3m30s
* Queue filling: ~ 7.922 (MpS)
* Command (message) processing: ~ 702 (MpS)
* Event (message) ACKing: ~ 2802 (MpS)
[**Video**](https://youtu.be/7TQOwBnj30A)

##### [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus)
* Time spent: ~ 1m30s
* Queue filling: ~ 9.433 (MpS)
* Command (message) processing: ~ 2.088 (MpS)
* Event (message) ACKing: ~ 6.436 (MpS)
[**Video**](https://youtu.be/SpkVH3u0Pp4)

## CPU Optimized Droplet ($40, 4GB/2CPUs)

##### [symfony/messenger](https://github.com/symfony/messenger)
* Time spent: ~ 3m15s
* Queue filling: ~ 11.268 (MpS)
* Command (message) processing: ~ 721 (MpS) 
* Event (message) ACKing: ~ 2.882 (MpS)
[**Video**](https://youtu.be/5KtXdAuiCuU)

##### [php-service-bus/service-bus](https://github.com/php-service-bus/service-bus)
* Time spent: ~ 1m55s
* Queue filling: ~ 13.004 (MpS)
* Command (message) processing: ~ 1.574 (MpS)
* Event (message) ACKing: ~ 5.776 (MpS)
[**Video**](https://youtu.be/5AxT8LIb5Rg)
