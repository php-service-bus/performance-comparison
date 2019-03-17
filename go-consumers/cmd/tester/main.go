package main

import (
	"database/sql"
	"fmt"
	_ "github.com/lib/pq"
	"github.com/streadway/amqp"
	"log"
	"os"
	"runtime"
	"service-bus-benchmark/pkg/command/customer"
	"sync"
)

var amqpConnection, dbConnection string

func main() {
	fmt.Println("MaxParallelism: ", MaxParallelism())
	var errors []error
	amqpConnection = os.Getenv("AMQP_CONNECTION")
	dbConnection = os.Getenv("DB_CONNECTION")

	if amqpConnection == "" {
		errors = append(errors, fmt.Errorf("AMQP_CONNECTION empty"))
	}

	if dbConnection == "" {
		errors = append(errors, fmt.Errorf("DB_CONNECTION empty"))
	}

	if len(errors) > 0 {
		var errStr string
		for _, err := range errors {
			errStr += fmt.Sprintf("%s \n", err.Error())
		}
		panic(errStr)
	}

	run()
}

func run() {
	db, err := sql.Open("postgres", dbConnection)

	handleError(err)

	db.SetMaxOpenConns(99)
	db.SetMaxIdleConns(99)

	err = db.Ping()

	handleError(err)

	connection, err := amqp.Dial(amqpConnection)
	handleError(err)

	defer connection.Close()

	var wg sync.WaitGroup

	for i := 0; i < 100; i++ {
		wg.Add(1)

		go func(index int) {
			channel, err := connection.Channel()
			handleError(err)
			handleError(channel.Qos(10,0, false))

			defer channel.Close()

			consumer := customer.NewConsumer(db, channel)
			consumer.Consume(wg, index)
		}(i)
	}

	wg.Wait()
}

func MaxParallelism() int {
	maxProcs := runtime.GOMAXPROCS(0)
	numCPU := runtime.NumCPU()
	if maxProcs < numCPU {
		return maxProcs
	}
	return numCPU
}

func handleError(err error) {
	if err != nil {
		log.Fatalf("%s \n", err.Error())
	}
}
