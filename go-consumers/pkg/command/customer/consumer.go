package customer

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"github.com/streadway/amqp"
	"log"
	"sync"
)

type Consumer struct {
	db             *sql.DB
	amqpChannel    *amqp.Channel
	amqpQueue      *amqp.Queue
}

func NewConsumer(db *sql.DB, channel *amqp.Channel) *Consumer {
	q, err := channel.QueueDeclare("test", true, false, false, false, nil)
	handleError(err)
	//err = channel.ExchangeDeclare("commands","direct", true, false, false, false, nil)
	//handleError(err)
	//err = channel.ExchangeDeclare("events","direct", true, false, false, false, nil)
	//handleError(err)
	//err = channel.QueueBind(q.Name, "command", "commands", false, nil)
	//handleError(err)
	//err = channel.QueueBind(q.Name, "event", "events", false, nil)


	return &Consumer{db: db, amqpChannel: channel, amqpQueue: &q}
}

func (c *Consumer) Consume(wg sync.WaitGroup, i int) {
	fmt.Printf("Consumer %d started...\n", i)
	defer wg.Done()
	defer fmt.Printf("Consumer %d done receiving...\n", i)

	messages, err := c.amqpChannel.Consume(c.amqpQueue.Name, "", false, false, false, false, nil)

	handleError(err)

	for d := range messages {
		var storeCmd StoreCommand
		err := json.Unmarshal(d.Body, &storeCmd)

		if err != nil {
			log.Printf("Error unmarshalling. %s", err.Error())
		}

		handleError(c.handle(storeCmd))
		handleError(d.Ack(false))
	}
}

func (c *Consumer) handle(cmd StoreCommand) error {
	tx, err := c.db.Begin()

	handleError(err)

	_, err = tx.Exec("INSERT INTO customers (id,name,email) VALUES ($1,$2,$3);", cmd.Id, cmd.Name, cmd.Email)

	if err != nil {
		handleError(tx.Rollback())
		handleError(err)
	}

	handleError(tx.Commit())

	return nil
}

func handleError(err error) {
	if err != nil {
		log.Fatalf("%s \n", err.Error())
	}
}
