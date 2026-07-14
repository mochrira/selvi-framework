# CLI Commands

Selvi integrates Symfony Console for CLI operations. The entry point is `console.php`.

## Entry Point Setup

Create `console.php`:

```php
<?php
use Selvi\Database\Migration;
use Selvi\Database\Seeder;
use Selvi\Env;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';

define('BASEPATH', __DIR__);
Env::load(BASEPATH . '/private/.ENV');
require './app/Config/database.php';

$app = new Application('Selvi Commander', '1.0.0');
$app->addCommand(new Migration());
$app->addCommand(new Seeder());
$app->run();
```

## Built-in Commands

### `migrate`

Run database migrations:

```bash
# Run migrations up
php console.php migrate main up

# Run all pending migrations
php console.php migrate main up --all

# Run next N migrations
php console.php migrate main up --step=3

# Rollback
php console.php migrate main down
php console.php migrate main down --step=1
```

**Arguments:**
| Argument | Description |
|---|---|
| `name` | Database config name (e.g., `main`) |
| `direction` | `up` or `down` |

**Options:**
| Option | Short | Description |
|---|---|---|
| `--step` | `-s` | Number of files to process |
| `--all` | `-a` | Process all files |

### `seeder`

Run database seeders:

```bash
# Run all seeders
php console.php seeder main

# Run specific number
php console.php seeder main --step=1
```

## Creating Custom Commands

Extend `Symfony\Component\Console\Command\Command`:

```php
<?php
// app/Commands/ExportCommand.php
namespace App\Commands;

use Selvi\Database\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command {

    protected static $defaultName = 'export';

    protected function configure(): void {
        $this->setName('export')
            ->setDescription('Export data to CSV')
            ->addArgument('table', InputArgument::REQUIRED, 'Table name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $table = $input->getArgument('table');
        $db = Manager::get('main');
        $rows = $db->get($table)->result();

        $output->writeln("Exporting {$table}...");
        // Export logic here...

        return Command::SUCCESS;
    }
}
```

Register in `console.php`:

```php
$app->addCommand(new App\Commands\ExportCommand());
```

## Custom CLI with Selvi Cli Class

Selvi also has a lightweight `Selvi\Cli` class for simple commands:

```php
use Selvi\Cli;

Cli::register('hello', new class {
    function run(string $name = 'World') {
        return response("Hello, {$name}!");
    }
});
```

Run: `php console.php hello Selvi`

> Note: The Symfony Console approach (`Migration`, `Seeder`) is preferred for complex CLI applications.

## Best Practices

1. **Use Symfony Console for complex commands** — it handles input/ouput formatting, progress bars, etc.
2. **Validate input** — check required arguments before executing
3. **Return proper exit codes** — `Command::SUCCESS` (0) or `Command::FAILURE` (1)
4. **Add descriptions** — every command and argument should have a clear description
5. **Handle errors gracefully** — catch exceptions and display user-friendly messages
