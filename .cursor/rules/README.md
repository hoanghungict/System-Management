---
trigger: manual
---

# 🚀 Laravel System Management Backend - Complete Rules

## 📌 Quick Access
- **Main Entry**: `.cursorrules` (project root) - Quick reference
- **This File**: Complete detailed rules and examples
- **Specialized Rules**: See other files in this directory

## Project Overview
This is a **Laravel 12 modular system** with Clean Architecture, featuring:
- **PHP 8.3+** with strict typing
- **Modules**: Auth, Task, Notifications, RollCall, Handle
- **JWT Authentication** with Firebase JWT 6.11
- **Redis Caching** with Predis 3.2
- **Kafka Messaging** for notifications
- **Docker** containerization
- **Swagger API** documentation
- **SQLite** database for development

## 🏗️ Architecture Rules

### Clean Architecture Implementation
```
Presentation Layer (Controllers, Requests, Middleware)
    ↓
Application Layer (Use Cases, DTOs, Services)
    ↓
Domain Layer (Models, Entities, Value Objects)
    ↓
Infrastructure Layer (Repositories, External Services)
```

### Module Structure (nwidart/laravel-modules 12.0)
```
Modules/{ModuleName}/
├── app/
│   ├── Http/Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── UseCases/
│   ├── DTOs/
│   ├── Exceptions/
│   └── Models/
├── config/
├── routes/
└── README.md
```

## 💻 Coding Standards

### PHP 8.3+ Features (Always Use)
- **Strict Types**: `declare(strict_types=1);` in ALL files
- **Typed Properties**: Use property types in all classes
- **Named Arguments**: Use named arguments for clarity
- **Match Expressions**: Use `match()` instead of `switch()`
- **Constructor Property Promotion**: Use for simple DTOs
- **Readonly Properties**: Use `readonly` for immutable data
- **Enums**: Use backed enums for constants
- **Union Types**: Use union types where appropriate
- **Nullsafe Operator**: Use `?->` for safe property access

### Laravel 12+ Patterns
- **Eloquent ORM**: Use Eloquent instead of raw SQL
- **Form Requests**: Use FormRequest for validation
- **Dependency Injection**: Use constructor injection
- **Service Layer**: Use services for business logic
- **Repository Pattern**: Use repositories for data access
- **DTO Pattern**: Use DTOs for data transfer
- **Exception Classes**: Use custom exception classes

## 🔐 Security Rules

### Authentication & Authorization
- **JWT Tokens**: Use Firebase JWT 6.11 for authentication
- **Middleware**: Use middleware for route protection
- **Permission Service**: Use centralized permission checking
- **User Types**: lecturer, student, admin with proper role checking
- **Password Hashing**: Use Laravel's Hash facade

### Input Validation
- **Form Requests**: Use FormRequest for all input validation
- **SQL Injection**: Use Eloquent ORM and parameterized queries
- **XSS Protection**: Use proper escaping and sanitization
- **CSRF Protection**: Use CSRF middleware for forms
- **Rate Limiting**: Implement rate limiting for API endpoints

## 🗄️ Database Rules

### Database Operations
- **Transactions**: Use `DB::transaction()` for operations that can fail
- **Eager Loading**: Use `with()` and `load()` to prevent N+1 queries
- **Soft Deletes**: Use soft deletes when data might be needed later
- **Indexes**: Add proper indexes for performance
- **Migrations**: Always create migrations for schema changes

### Query Optimization
- **Select Specific Columns**: Use `select()` to limit columns
- **Pagination**: Use pagination for large datasets
- **Query Scopes**: Use query scopes for reusable query logic
- **Database Seeding**: Use seeders for test data

## 🚀 Performance Rules

### Caching Strategy
- **Redis**: Use Redis for all caching operations
- **Cache Keys**: Use consistent naming: `{module}:{type}:{id}`
- **TTL**: Set appropriate cache expiration times
- **Invalidation**: Implement cache invalidation strategies
- **Fallback**: Handle cache failures gracefully

### Background Processing
- **Queues**: Use Laravel queues for heavy operations
- **Jobs**: Implement proper job classes with error handling
- **Retry Logic**: Implement retry mechanisms for failed jobs
- **Kafka**: Use Kafka for message queuing

## 🧪 Testing Rules

### Test Structure
- **Unit Tests**: Test business logic in isolation
- **Integration Tests**: Test API endpoints and database operations
- **Feature Tests**: Test complete user workflows
- **Mocking**: Mock external dependencies and services

### Test Quality
- **Coverage**: Maintain high test coverage
- **Edge Cases**: Test error scenarios and edge cases
- **Data Factories**: Use factories for test data generation
- **Database**: Use database transactions for test isolation

## 📝 Documentation Rules

### Code Documentation
- **PHPDoc**: Document all public methods and classes
- **README Files**: Create comprehensive README for each module
- **API Documentation**: Use Swagger/OpenAPI for API docs
- **Architecture Diagrams**: Create diagrams for complex flows

### Comments
- **Why, not What**: Explain why code exists, not what it does
- **Complex Logic**: Comment complex business logic
- **TODOs**: Use TODO comments for future improvements
- **Deprecated**: Mark deprecated code appropriately

## 🔧 Module-Specific Rules

### Auth Module
- **JWT Authentication**: Use Firebase JWT for token generation/validation
- **User Management**: Separate accounts for lecturers and students
- **Role-Based Access**: Implement proper role checking
- **Password Security**: Use secure password hashing
- **Session Management**: Implement proper session handling

### Task Module
- **Clean Architecture**: Follow established patterns in existing code
- **Permission Service**: Use centralized permission checking
- **Caching Strategy**: Implement Redis caching with proper invalidation
- **Business Rules**: Validate deadlines, receivers, creator types
- **Kafka Integration**: Use Kafka for task notifications
- **File Uploads**: Implement secure file handling for task attachments
- **Role-Based Access**: Separate controllers and permissions for Admin, Lecturer, Student
- **Reminder System**: Automatic and manual reminders with multiple channels

### Notifications Module
- **Kafka Producer/Consumer**: Use Kafka for message queuing
- **Email Templates**: Create reusable email templates
- **Background Jobs**: Use queues for email sending
- **Error Handling**: Graceful handling of notification failures
- **Monitoring**: Log notification delivery status

### RollCall Module
- **Attendance Management**: Implement proper attendance tracking
- **Data Validation**: Validate attendance data
- **Reporting**: Generate attendance reports
- **Integration**: Integrate with other modules

### Handle Module
- **File Processing**: Implement secure file handling
- **Data Processing**: Process data efficiently
- **Error Handling**: Handle processing errors gracefully
- **Logging**: Log processing activities

## 🐳 Docker Rules

### Container Configuration
- **Multi-stage Builds**: Use multi-stage Docker builds
- **Environment Variables**: Use environment variables for configuration
- **Health Checks**: Implement health checks for services
- **Resource Limits**: Set appropriate resource limits

### Development Environment
- **Docker Compose**: Use docker-compose for local development
- **Volume Mounts**: Mount source code for development
- **Hot Reload**: Enable hot reload for development
- **Database**: Use containerized database for development

## 📊 Monitoring Rules

### Application Monitoring
- **Health Checks**: Implement health check endpoints
- **Metrics**: Collect performance metrics
- **Alerts**: Set up alerts for critical issues
- **Logging**: Centralized logging for all services

### Performance Monitoring
- **Response Times**: Monitor API response times
- **Database Queries**: Monitor slow database queries
- **Memory Usage**: Monitor memory usage and leaks
- **Cache Hit Rates**: Monitor cache performance

## 🎯 Code Generation Rules

### When Creating Controllers
- Extend base controller if available
- Use dependency injection for services
- Implement proper error handling
- Add request validation
- Use service layer for business logic
- Add comprehensive logging

### When Creating Services
- Implement interfaces for contracts
- Use dependency injection
- Add proper error handling
- Implement caching where appropriate
- Add comprehensive logging
- Use transactions for data operations

### When Creating Models
- Use proper relationships
- Implement accessors/mutators
- Add fillable/guarded properties
- Use soft deletes when appropriate
- Add validation rules
- Implement proper scopes

### When Creating Repositories
- Implement repository interfaces
- Use dependency injection
- Add caching where appropriate
- Implement proper error handling
- Use transactions for data operations
- Add comprehensive logging

## 🚨 Error Handling Rules

### Exception Management
- **Custom Exceptions**: Create module-specific exception classes
- **Exception Hierarchy**: Use proper exception inheritance
- **Context Logging**: Include context in error logs
- **User-Friendly Messages**: Provide meaningful error messages
- **HTTP Status Codes**: Use appropriate HTTP status codes

### Logging Strategy
- **Structured Logging**: Use structured logging with context
- **Log Levels**: Use appropriate log levels (debug, info, warning, error)
- **Sensitive Data**: Never log passwords or sensitive information
- **Performance**: Log performance metrics for critical operations

## 🔄 Development Workflow Rules

### Git Workflow
- **Branch Naming**: Use descriptive branch names
- **Commit Messages**: Use conventional commit format
- **Code Review**: All code must be reviewed before merging
- **Testing**: All tests must pass before merging

### Code Quality
- **Laravel Pint**: Use Laravel Pint for code formatting
- **Static Analysis**: Use PHPStan for static analysis
- **Code Standards**: Follow established coding standards
- **Refactoring**: Refactor code regularly to maintain quality

## 🎨 API Design Rules

### RESTful API
- **HTTP Methods**: Use proper HTTP methods (GET, POST, PUT, DELETE)
- **Status Codes**: Use appropriate HTTP status codes
- **Response Format**: Use consistent response format
- **Error Handling**: Implement proper error responses
- **Pagination**: Implement pagination for list endpoints
- **Filtering**: Implement filtering and sorting

### API Documentation
- **Swagger**: Use Swagger/OpenAPI for API documentation
- **Examples**: Provide examples for all endpoints
- **Schemas**: Define proper request/response schemas
- **Authentication**: Document authentication requirements

## 📈 Performance Optimization Rules

### Database Performance
- **Query Optimization**: Optimize database queries
- **Indexing**: Add proper indexes for performance
- **Eager Loading**: Use eager loading to prevent N+1 queries
- **Connection Pooling**: Use connection pooling
- **Query Caching**: Implement query caching

### Application Performance
- **Caching**: Implement strategic caching
- **Background Jobs**: Use background jobs for heavy operations
- **Memory Management**: Optimize memory usage
- **CPU Optimization**: Optimize CPU usage
- **Network Optimization**: Optimize network requests

## 🎯 Best Practices Summary

1. **Always use Clean Architecture**
2. **Implement proper error handling**
3. **Use dependency injection**
4. **Add comprehensive logging**
5. **Implement proper caching**
6. **Use database transactions**
7. **Validate all inputs**
8. **Follow security best practices**
9. **Write comprehensive tests**
10. **Document everything properly**

## 🚀 Quick Commands

### Development
- `composer dev` - Start development environment
- `php artisan test` - Run tests
- `php artisan pint` - Format code
- `php artisan queue:work` - Process queues

### Docker
- `docker-compose up -d` - Start services
- `docker-compose down` - Stop services
- `docker-compose logs -f` - View logs

### Module Management
- `php artisan module:make {ModuleName}` - Create new module
- `php artisan module:list` - List modules
- `php artisan module:enable {ModuleName}` - Enable module
- `php artisan module:disable {ModuleName}` - Disable module

## 🎯 Implementation Guidelines

- **Research First**: Always research latest documentation before coding
- **Test Thoroughly**: Test all new features before production
- **Document Changes**: Document all changes and updates
- **Version Control**: Use proper version control practices
- **Code Review**: Always review code for latest standards
- **Continuous Learning**: Stay updated with latest trends
- **Community Engagement**: Engage with developer communities
- **Best Practices**: Follow industry best practices

## 📚 Additional Rule Files

This main rule file references additional specialized rule files:
- `architecture.md` - Clean Architecture implementation details
- `security.md` - Security best practices and implementation
- `performance.md` - Performance optimization strategies
- `testing.md` - Testing standards and patterns
- `documentation.md` - Documentation standards and examples

Remember: This is a Laravel 12 modular system with Clean Architecture. Always follow established patterns and use the latest Laravel 12 features and best practices.
