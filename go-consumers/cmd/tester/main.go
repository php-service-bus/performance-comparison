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
	"strconv"
	"sync"
)

var amqpConnection, dbConnection, dbConnectionsCount string
var dbConnCount int

func main() {
	fmt.Println("MaxParallelism: ", MaxParallelism())
	var errors []error
	amqpConnection = os.Getenv("AMQP_CONNECTION")
	dbConnection = os.Getenv("DB_CONNECTION")
	dbConnectionsCount = os.Getenv("DB_CONNECTIONS_COUNT")

	if amqpConnection == "" {
		errors = append(errors, fmt.Errorf("AMQP_CONNECTION empty"))
	}

	if dbConnection == "" {
		errors = append(errors, fmt.Errorf("DB_CONNECTION empty"))
	}

	if dbConnectionsCount == "" {
		errors = append(errors, fmt.Errorf("DB_CONNECTIONS_COUNT empty"))
	}

	if res, err := strconv.Atoi(dbConnectionsCount); err != nil {
		errors = append(errors, fmt.Errorf("DB_CONNECTIONS_COUNT is not int"))
	} else {
		dbConnCount = res
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

	db.SetMaxOpenConns(dbConnCount)
	db.SetMaxIdleConns(dbConnCount)

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
			handleError(channel.Qos(100,0, false))

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
