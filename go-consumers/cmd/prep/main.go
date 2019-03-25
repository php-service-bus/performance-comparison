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
	handleError(err)

	defer connection.Close()

	channel, err := connection.Channel()
	handleError(err)

	defer channel.Close()

	q, err := channel.QueueDeclare("test", true, false, false, false, nil)
	handleError(err)
	err = channel.ExchangeDeclare("commands","direct", true, false, false, false, nil)
	handleError(err)
	err = channel.QueueBind(q.Name, "command", "commands", false, nil)

	for i := 0; i < 100000; i++ {
		storeCmd := customer.NewStoreCustomerCommand(uuid.New().String(), fmt.Sprintf("name_%d", i), "name@qwerty.root")
		body, err := json.Marshal(storeCmd)
		handleError(err)

		err = channel.Publish(
			"commands",     // exchange
			"command", // routing key
			false,  // mandatory
			false,  // immediate
			amqp.Publishing{
				ContentType: "text/plain",
				Headers: amqp.Table{
					"type": "command",
				},
				Body:        body,
			})

		handleError(err)
	}

	fmt.Println("Filling in the message queue is completed")
}

func handleError(err error) {
	if err != nil {
		log.Fatalf("%s \n", err.Error())
	}
}
