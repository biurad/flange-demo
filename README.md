# Flange Blog Demo Application

This project is a reference implementation of a [symfony's demo][1] application for [Flange][2]. It is not intended to be used in production, but as a good starting point to get started with [Flange][2].

> You can also learn extensively about the [Flange][2] framework from the [official Flange Documentation][3] page.

## 🛠️ Requirements

* PHP 8.0.5 or higher
* PDO-SQLite PHP extension enabled
* [Composer][4] installed
* and other requirements listed in composer.json file

## 📦 Installation

* Clone this repository or download the archive from [GitHub][5].
* Run `composer update` as default packages may not be compatible with your PHP version.
* Run `./flange serve` (on Windows `flange serve`). The application will be started on http://localhost:8000/.

Want to see this blog in action, check out https://flange-blog-demo.up.railway.app. Deployed on [Railway][6].

## 🧪 Testing

The project comes with ready to use [PhpUnit][7] configuration. In order to execute tests run:

```bash
composer test
```

## 🙌 Sponsors

Kindly consider supporting this project and future development by donating to  us. See <https://buirad.com/sponsor> for list of ways to contribute.

## 📄 License

The Flange Demo Project is released under the terms of the MIT License. Please see the [`LICENSE`](./LICENSE) for more information.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Contributions on this project are welcome.

[1]: https://github.com/symfony/demo
[2]: https://github.com/biurad/flange
[3]: https://biurad.com/doc/php/flange
[4]: https://getcomposer.org/
[5]: https://github.com/biurad/flange-demo/archive/refs/heads/main.zip
[6]: https://railway.app?referralCode=ZjAkYt
[7]: https://github.com/phpunit/phpunit
