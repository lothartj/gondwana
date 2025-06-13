# Gondwana Collection Booking System
[![Live Demo on Render](https://img.shields.io/badge/Live%20Demo-Render-blue?logo=render&style=for-the-badge)](https://gondwana.onrender.com/frontend/index.html)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=gondwana-booking&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=gondwana-booking)
[![SonarCloud Analysis](https://github.com/lothartj/gondwana/actions/workflows/sonarqube.yml/badge.svg)](https://github.com/lothartj/gondwana/actions/workflows/sonarqube.yml)

A modern booking system for Gondwana Collection Namibia's luxury lodges and activities. This system provides a seamless booking experience for guests while ensuring proper integration with Gondwana's existing infrastructure.

## Features

- Real-time availability checking for all properties
- Dynamic rate calculation based on:
  - Property selection
  - Date range
  - Number of guests
  - Guest ages
- Modern, responsive user interface with:
  - Intuitive search form
  - Property cards with detailed information
  - Seamless booking process
- Secure API integration with Gondwana's backend systems
- Cross-browser compatibility
- Mobile-first design approach

## Technology Stack

### Frontend
- HTML5 & CSS3 with modern features
- Vanilla JavaScript for lightweight performance
- Responsive design using CSS Grid and Flexbox
- Custom styling with CSS variables for theming

### Backend
- PHP 8.1
- RESTful API architecture
- Secure CORS implementation
- Robust error handling and validation

### Quality Assurance
- SonarCloud integration for:
  - Code quality analysis
  - Security vulnerability scanning
  - Code coverage tracking
  - Coding standards enforcement
- Automated GitHub Actions workflows
- Continuous Integration/Deployment pipeline

### Development Environment
- GitHub Codespaces ready
- Docker support for consistent development
- Environment-based configuration

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

3. Install dependencies:
```bash
# If using Composer
composer install

# If using npm
npm install
```

4. Start the development environment:
```bash
# Using PHP's built-in server
php -S localhost:8000

# Or using Docker
docker-compose up -d
```

5. Open http://localhost:8000 in your browser

## API Integration

The system integrates with Gondwana's API using the following payload transformation:

### Request Format
```json
{
  "Unit Name": "String",
  "Arrival": "dd/mm/yyyy",
  "Departure": "dd/mm/yyyy",
  "Occupants": <int>,
  "Ages": [<int array>]
}
```

### Transformed Format
```json
{
  "Unit Type ID": <int>,
  "Arrival": "yyyy-mm-dd",
  "Departure": "yyyy-mm-dd",
  "Guests": [{"Age Group": "Adult"}, {"Age Group": "Child"}]
}
```

## Quality Standards

This project maintains high quality standards through automated tools and processes:

### SonarCloud Integration
- Automated code analysis on every push and PR
- Quality gates must pass before merge
- Coverage tracking for both PHP and JavaScript
- Security vulnerability scanning

### Coding Standards
- PSR-12 for PHP code
- ESLint for JavaScript
- EditorConfig for consistent styling
- Prettier for code formatting

## Contributing

1. Create a feature branch:
```bash
git checkout -b feature/your-feature-name
```

2. Make your changes and commit using conventional commits:
```bash
git commit -m "feat: add your feature description"
```

3. Push to your branch:
```bash
git push origin feature/your-feature-name
```

4. Create a Pull Request

Your PR will automatically trigger:
- SonarCloud analysis
- GitHub Actions workflows
- Code quality checks

The PR can only be merged if it passes all quality gates and receives approval.

## Environment Variables

Required environment variables:
- `API_ENDPOINT`: Gondwana API endpoint URL
- `API_KEY`: Authentication key for API access
- `CORS_ALLOWED_ORIGINS`: Allowed origins for CORS
- `DEBUG_MODE`: Enable/disable debug mode

## License

MIT License - see LICENSE file for details

## Support

For support or questions, please open an issue in the GitHub repository. 
