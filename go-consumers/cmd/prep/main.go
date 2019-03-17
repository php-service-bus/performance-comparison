package main

import (
	"encoding/json"
	"fmt"
	"github.com/google/uuid"
	"github.com/streadway/amqp"
	"log"
	"os"
	"service-bus-benchmark/pkg/command/customer"
)

func main() {
	amqpConnection := os.Getenv("AMQP_CONNECTION")

	if amqpConnection == "" {
		panic("AMQP_CONNECTION is empty")
	}

	connection, err := amqp.Dial(amqpConnection)
	if err != nil {
		log.Fatal(err)
	}

	defer connection.Close()
	channel, err := connection.Channel()

	if err != nil {
		log.Fatal(err)
	}

	defer channel.Close()


	q, err := channel.QueueDeclare("test", true, false, false, false, nil)

	for i := 0; i < 100000; i++ {
		storeCmd := customer.NewStoreCustomerCommand(uuid.New().String(), fmt.Sprintf("name_%d", i), "name@qwerty.root")
		body, err := json.Marshal(storeCmd)

		if err != nil {
			log.Fatal(err)
		}

		err = channel.Publish(
			"",     // exchange
			q.Name, // routing key
			false,  // mandatory
			false,  // immediate
			amqp.Publishing{
				ContentType: "text/plain",
				Body:        body,
			})

		if err != nil {
			log.Fatal(err)
		}
	}

	fmt.Println("Filling in the message queue is completed")
}
