##### Сценарий
* В RabbitMQ добавляется 100.000 команд (пик в начале)
* При получении команды приложение начинает транзакцию (PostgreSQL 9.6), добавляет запись, отправляет событие в RabbitMQ, коммитит транзакцию
* При получении события оно никак не обрабатывается (просто принимается; видно на пике в конце)

##### Настройки
* Логирование у приложений выключено
* Каждое приложение запускается только в 1 экземляре (1 процесс)
* Я не нашёл как у **symfony/messenger** корректно изменить QoS параметры, поэтому у обоих приложений они оставлены "как есть" (как из коробки настроено, так и работает)
* В **mmasiukevich-service-bus** Изменено количество обрабатываемых одновременно сообщений до 90
* Версия PHP - 7.2
* Для **mmasiukevich-service-bus** дополнительно установлены расширения: raphf, pq, sockets, event

##### Цель
Цель тестирование - показать разницу между синхронным и асинхронным взаимодействием. Чем слабее железо, тем больше времени занимает, например, запись в базу данных. Как следствие - воркер совершает меньше полезной работы за еденицу времени (ведь пока он ожидает запись, можно было бы сделать что-то ещё). Лучше всего разница видна на примере обычного компьютера.

## Тестирование

#### PC на базе i5/16GB, SSD

##### **symfony/messenger**
* заполнение очереди: ~13.200 сообщений в секунду
* Обработка сообщений: ~250 сообщений в секунду
* Принятие событий: ~4350 сообщений в секунду

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/messenger-pc.png)

##### **mmasiukevich-service-bus**
* заполнение очереди: ~10.200 сообщений в секунду
* Обработка сообщений: ~2.000 сообщений в секунду
* Принятие событий: ~6.700 сообщений в секунду

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/service-bus-pc.png)

#### [DigitalOcean](https://www.digitalocean.com/) CPU Optimized Droplet (2 CPUs/4GB, SSD)

##### **symfony/messenger**
* заполнение очереди: ~5.900 сообщений в секунду
* Обработка сообщений: ~670 сообщений в секунду
* Принятие событий: ~2.450 сообщений в секунду

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/messenger-1.png)

##### **mmasiukevich-service-bus**
* заполнение очереди: ~6.000 сообщений в секунду
* Обработка сообщений: ~1050 сообщений в секунду
* Принятие событий: ~4.100 (~4.600) сообщений в секунду

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/service-bus-1.png)

#### [DigitalOcean](https://www.digitalocean.com/) CPU Optimized Droplet (8 CPUs/16GB, SSD)

##### **symfony/messenger**
* заполнение очереди: ~17.000 сообщений в секунду
* Обработка сообщений: ~1.600 сообщений в секунду
* Принятие событий: ~5.600 сообщений в секунду

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/messenger-2.png)

##### **mmasiukevich-service-bus**
* заполнение очереди: ~10.900 сообщений в секунду
* Обработка сообщений: ~2.200 сообщений в секунду
* Принятие событий: ~8.000 сообщений в секунду

![](https://github.com/mmasiukevich/performance-comparison/blob/master/results/service-bus-2.png)
