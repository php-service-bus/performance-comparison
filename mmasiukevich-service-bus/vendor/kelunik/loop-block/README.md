# loop-block

Detect blocking ticks in an event loop based on the PHP [event loop standard](https://github.com/async-interop/event-loop).

## Installation

```
$ composer install kelunik/loop-block
```

## Usage

You can instantiate a new `BlockDetector`. Its constructor takes a callback to be executed on blocks, a threshold in milliseconds, and an interval to configure how often the check is executed.

Once the loop runs, you can call `BlockDetector::start` to start the detection. `BlockDetector::stop` stops the detection again.

## License

[MIT](./LICENSE).
