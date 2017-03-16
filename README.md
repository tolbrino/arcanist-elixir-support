# Arcanist Elixir Support

This is an extension for [Arcanist](https://phacility.com/phabricator/arcanist)
to provide support for linting and unit testing [Elixir](http://elixir-lang.org) code.

## Installation

**These installation instruction were borrowed and adapted from
[clang-format-linter](https://github.com/vhbit/clang-format-linter).**

`arcanist` can load modules from an absolute path. But there is one
more trick - it also searches for modules in a directory up one level
from itself.

It means that you can clone this repository to the same directory
where `arcanist` and `libphutil` are located. In the end it should
look like this:

```sh
> ls
arcanist
arcanist-elixir-support
libphutil
```

## General Requirements

- [Erlang/OTP 18.1](http://www.erlang.org/download.html) - an older version might work as well, but hasn't been tested
- [Elixir 1.1.1](https://github.com/elixir-lang/elixir/releases) - an older version might work as well, but hasn't been tested
- [Mix](http://elixir-lang.org/docs/stable/mix/Mix.html) - used for running linting and unit testing

## Linting Setup

In order to be able to run `arc lint` you need to use the Elixir linting
library [dogma](https://github.com/lpil/dogma). It needs to be installed as
part of your dependencies of your Mix project in `mix.exs`:

```
{:dogma, github: "lpil/dogma", ref: "586ee29", only: [:dev, :test]}
```

Then you need to sync your dependencies by executing: `mix do deps.get, deps.update, compile`

Finally you need to tell Arcanist which files it should lint by adding a snippet like the following to your `.arclint`:

```
{
  "linters": {
    "elixir": {
      "type": "elixirdogma",
      "include": [
        "(\\.ex[s]?$)"
      ],
      "exclude": [
        "(^deps/)"
      ]
    }
  }
}
```

Now you can run `arc lint`.

## Unit Testing Setup

The unit testing supports uses the
[ExUnit](http://elixir-lang.org/docs/stable/ex_unit/ExUnit.html) library which
comes with Elixir out-of-the-box. However, an additional library is needed to
generate proper XML report files which can be consumed by Arcanist. Therefore,
you need to add
[junit_formatter](https://github.com/victorolinasc/junit-formatter) to your
project dependencies in `mix.exs`:

```
{:junit_formatter, "~> 1.3.0", only: :test}
```

To enable the XML report generation you need to configure ExUnit just before you actually start it within your test suite:

```
ExUnit.configure formatters: [JUnitFormatter, ExUnit.CLIFormatter]
ExUnit.start
```

Then you need to sync your dependencies by executing: `mix do deps.get, deps.update, compile`

Finally you need to tell Arcanist which files it should test by adding a snippet like the following to your `.arcunit`:

```
{
  "engines": {
    "exunit": {
      "type": "exunit",
      "include": [
        "(^test/.+_test\\.ex[s]?$)"
      ],
      "exclude": [
        "(^deps/)"
      ]
    }
  }
}
```

Now you can run `arc unit`.
