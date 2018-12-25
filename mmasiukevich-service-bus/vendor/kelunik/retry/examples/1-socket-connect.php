<?php

use Amp\Loop;
use Kelunik\Retry\ConstantBackoff;
use function Kelunik\Retry\retry;

require __DIR__ . "/../vendor/autoload.php";

Loop::run(function () {
    /** @var Amp\Socket\ClientSocket $socket */
    $socket = yield retry(3, function () {
        return Amp\Socket\cryptoConnect("tcp://github.com:443");
    }, Amp\Socket\SocketException::class, new ConstantBackoff(1000));

    yield $socket->write("GET / HTTP/1.0\r\nhost: github.com\r\n\r\n");

    $buffer = "";

    while (null !== $chunk = yield $socket->read()) {
        $buffer .= $chunk;

        if (strpos($buffer, "\r\n\r\n") !== false) {
            print strstr($buffer, "\r\n\r\n", true);
            break;
        }
    }

    $socket->close();
});
