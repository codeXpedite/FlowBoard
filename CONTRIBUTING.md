# Contributing to FlowBoard

First off, thank you for considering contributing to FlowBoard! It's people like you that make FlowBoard such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* Use a clear and descriptive title
* Describe the exact steps which reproduce the problem
* Provide specific examples to demonstrate the steps
* Describe the behavior you observed after following the steps
* Explain which behavior you expected to see instead and why
* Include screenshots if applicable

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* Use a clear and descriptive title
* Provide a step-by-step description of the suggested enhancement
* Provide specific examples to demonstrate the steps
* Describe the current behavior and explain which behavior you expected to see instead
* Explain why this enhancement would be useful

### Pull Requests

* Fill in the required template
* Do not include issue numbers in the PR title
* Include screenshots and animated GIFs in your pull request whenever possible
* Follow the PHP and JavaScript styleguides
* Include thoughtfully-worded, well-structured tests
* Document new code based on the Documentation Styleguide
* End all files with a newline

## Development Process

### Setup Development Environment

1. Fork the repo
2. Clone your fork: `git clone https://github.com/your-username/flowboard.git`
3. Install dependencies: `composer install && npm install`
4. Copy environment file: `cp .env.example .env`
5. Generate application key: `php artisan key:generate`
6. Run migrations: `php artisan migrate --seed`
7. Start development: `composer dev`

### Code Style

#### PHP
* Follow PSR-12 coding standards
* Use Laravel Pint for code formatting: `vendor/bin/pint`
* Write meaningful variable and method names
* Add type hints for all parameters and return types

#### JavaScript
* Use ES6+ features
* Follow Alpine.js conventions for Livewire components
* Use meaningful variable names
* Add JSDoc comments for complex functions

#### CSS
* Use TailwindCSS utility classes
* Follow mobile-first responsive design
* Use semantic class names for custom components
* Maintain consistent spacing and typography

### Testing

* Write tests for new features
* Ensure all tests pass: `composer test`
* Maintain test coverage above 80%
* Test both happy path and edge cases

### Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally after the first line

### Database Changes

* Create migrations for all database changes
* Use descriptive migration names
* Add appropriate indexes
* Update model relationships
* Add factory and seeder updates if needed

### Security Considerations

* Never commit secrets or API keys
* Use Laravel's built-in security features
* Validate all user inputs
* Follow OWASP security guidelines
* Report security vulnerabilities privately

## Project Structure

```
app/
├── Http/
│   ├── Controllers/     # HTTP controllers
│   └── Middleware/      # Custom middleware
├── Livewire/           # Livewire components
├── Models/             # Eloquent models
├── Services/           # Business logic services
├── Observers/          # Model observers
└── Console/Commands/   # Artisan commands

resources/
├── views/
│   ├── livewire/       # Livewire component views
│   ├── layouts/        # Application layouts
│   └── components/     # Blade components
├── css/                # Stylesheets
└── js/                 # JavaScript files

database/
├── migrations/         # Database migrations
├── seeders/           # Database seeders
└── factories/         # Model factories

tests/
├── Feature/           # Feature tests
└── Unit/              # Unit tests
```

## Styleguides

### Git Commit Messages

* feat: A new feature
* fix: A bug fix
* docs: Documentation only changes
* style: Changes that do not affect the meaning of the code
* refactor: A code change that neither fixes a bug nor adds a feature
* perf: A code change that improves performance
* test: Adding missing tests or correcting existing tests
* chore: Changes to the build process or auxiliary tools

### PHP Styleguide

```php
<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function createTask(array $data): Task
    {
        // Implementation
    }

    private function validateTaskData(array $data): void
    {
        // Implementation
    }
}
```

### Livewire Component Styleguide

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class TaskCard extends Component
{
    public Task $task;
    
    public function mount(Task $task): void
    {
        $this->task = $task;
    }

    #[On('task-updated')]
    public function refreshTask(): void
    {
        $this->task->refresh();
    }

    public function render()
    {
        return view('livewire.task-card');
    }
}
```

## Getting Help

* Check the documentation
* Search existing GitHub issues
* Ask questions in GitHub Discussions
* Join our community chat (if available)

## Recognition

Contributors will be recognized in our README.md file and release notes.

Thank you for contributing to FlowBoard! 🚀