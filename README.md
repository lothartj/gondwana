# Gondwana Collection Booking System

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=gondwana-booking&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=gondwana-booking)

A modern booking system for Gondwana Collection Namibia's luxury lodges and activities.

## Features

- Real-time availability checking
- Dynamic rate calculation
- User-friendly booking interface
- Responsive design
- Integration with Gondwana's API

## Technology Stack

- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP 8.1
- Quality Assurance: SonarCloud
- Development Environment: GitHub Codespaces

## Development Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd gondwana
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Start the development environment:
```bash
# Using PHP's built-in server
php -S localhost:8000

# Or using Docker
docker-compose up -d
```

4. Open http://localhost:8000 in your browser

## Quality Standards

This project uses SonarCloud for continuous code quality inspection. Every pull request is automatically analyzed for:
- Code quality
- Security vulnerabilities
- Code duplication
- Test coverage
- Coding standards compliance

## Contributing

1. Create a feature branch:
```bash
git checkout -b feature/your-feature-name
```

2. Make your changes and commit:
```bash
git commit -m "Add your feature description"
```

3. Push to your branch:
```bash
git push origin feature/your-feature-name
```

4. Create a Pull Request

Your PR will automatically trigger a SonarCloud analysis. The PR can only be merged if it passes all quality gates.

## License

MIT License - see LICENSE file for details 