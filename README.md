##### Workflow
* Add 100.000 messages into RabbitMQ, each message is a command (represented by a peak on chart start);
* Received command handler opens a PostgreSQL 11 transaction, inserts an entry into DB, publishes a message (event) into RabbitMQ and commits the transaction;
* Received event does not induce any load - it is just ACKed.

##### Settings
* All the logging is disabled
* Every application has only 1 instance (single process);
* I did not found how to change QoS settings in [symfony/messenger](https://github.com/symfony/messenger) so in both apps default ones are used;
* Number of simultaneously processed messages in [mmasiukevich/service-bus](https://github.com/mmasiukevich/service-bus) was changed to 80;
* PHP 7.3;
* For [mmasiukevich/service-bus](https://github.com/mmasiukevich/service-bus) following PHP extensions were installed: raphf, pq, sockets, event.

##### Goal
This test goal is to show the difference between synchronous and asynchronous interaction. For example, DB writes is slower on weaker hardware. As a result worker performs less useful work while waiting for a synchronous operation result. The difference is best seen on a simple PC or low-level server.

**@see**: [Cooperative multitasking](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)

## Testing

#### PC with i5 CPU, 16GB RAM and SSD

##### [symfony/messenger](https://github.com/symfony/messenger)
* Queue filling: ~13.200 messages per second (MpS);
* Command (message) processing: ~250 MpS;
* Event (message) ACKing: ~4.350 MpS.

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/messenger-pc.png)

##### [mmasiukevich/service-bus](https://github.com/mmasiukevich/service-bus)
* Queue filling: ~10.200 MpS
* Command (message) processing: ~2.000 MpS
* Event (message) ACKing: ~6.700 MpS

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/service-bus-pc.png)

#### [DigitalOcean](https://www.digitalocean.com/) CPU Optimized Droplet (2 CPUs/4GB, SSD)

##### [symfony/messenger](https://github.com/symfony/messenger)
* Queue filling: ~5.900 MpS
* Command (message) processing: ~670 MpS
* Event (message) ACKing: ~2.450 MpS

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/messenger-1.png)

##### [mmasiukevich/service-bus](https://github.com/mmasiukevich/service-bus)
* Queue filling: ~5.900 MpS
* Command (message) processing: ~1050 MpS
* Event (message) ACKing: ~4.100 (~4.600) MpS

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/service-bus-1.png)

#### [DigitalOcean](https://www.digitalocean.com/) CPU Optimized Droplet (8 CPUs/16GB, SSD)

##### [symfony/messenger](https://github.com/symfony/messenger)
* Queue filling: ~17.000 MpS
* Command (message) processing: ~1.600 MpS
* Event (message) ACKing: ~5.600 MpS

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/messenger-2.png)

##### [mmasiukevich/service-bus](https://github.com/mmasiukevich/service-bus)
* Queue filling: ~10.900 MpS
* Command (message) processing: ~2.200 MpS
* Event (message) ACKing: ~8.000 MpS

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/service-bus-2.png)
