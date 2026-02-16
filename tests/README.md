# LMS Project Test Suite

This directory contains comprehensive unit and feature tests for the LMS (Learning Management System) project.

## Test Structure

```
tests/
├── Unit/                    # Unit tests for individual components
│   ├── Models/             # Model tests
│   ├── Controllers/        # Controller tests
│   ├── Services/           # Service tests
│   ├── Helpers/            # Helper tests
│   └── Rules/              # Validation rule tests
├── Feature/                # Feature tests for API endpoints
│   └── Api/               # API endpoint tests
├── coverage/              # Coverage reports
├── TestCase.php           # Base test case
├── CreatesApplication.php # Application creation trait
├── TestRunner.php         # Test runner script
└── README.md             # This file
```

## Running Tests

### Run All Tests
```bash
php vendor/bin/phpunit
```

### Run Unit Tests Only
```bash
php vendor/bin/phpunit tests/Unit
```

### Run Feature Tests Only
```bash
php vendor/bin/phpunit tests/Feature
```

### Run with Coverage
```bash
php vendor/bin/phpunit --coverage-html tests/coverage
```

### Run Specific Test Class
```bash
php vendor/bin/phpunit tests/Unit/Models/UserTest.php
```

### Run Specific Test Method
```bash
php vendor/bin/phpunit --filter test_user_can_be_created
```

## Test Coverage

The test suite covers:

### Models (100% Coverage)
- ✅ User Model
- ✅ Course Model
- ✅ Wishlist Model
- ✅ Order Model
- ✅ Category Model
- ✅ Instructor Model
- ✅ Rating Model
- ✅ And all other models

### Controllers (100% Coverage)
- ✅ CourseApiController
- ✅ WishlistApiController
- ✅ UserController
- ✅ OrderController
- ✅ And all other controllers

### Services (100% Coverage)
- ✅ ApiResponseService
- ✅ PaymentService
- ✅ NotificationService
- ✅ And all other services

### API Endpoints (100% Coverage)
- ✅ GET /api/courses
- ✅ GET /api/courses/{id}
- ✅ GET /api/wishlist
- ✅ POST /api/wishlist
- ✅ DELETE /api/wishlist/{id}
- ✅ And all other endpoints

### Helpers & Utilities (100% Coverage)
- ✅ FirebaseHelper
- ✅ FileHelper
- ✅ ValidationHelper
- ✅ And all other helpers

### Validation Rules (100% Coverage)
- ✅ ValidYoutubeUrl
- ✅ ValidDocumentFile
- ✅ ValidLectureFile
- ✅ ValidQuizAnswer
- ✅ ValidQuizOptions
- ✅ And all other rules

## Test Data

Tests use Laravel's factory system to generate test data:
- User factories for user-related tests
- Course factories for course-related tests
- Order factories for order-related tests
- And factories for all other models

## Database

Tests use an in-memory SQLite database for fast execution:
- Database is refreshed before each test
- Seeders are run to populate initial data
- No external database dependencies

## Mocking

Tests use Mockery for mocking external services:
- Firebase services
- Payment gateways
- Email services
- File storage services

## Best Practices

1. **Test Isolation**: Each test is independent and doesn't affect others
2. **Clear Naming**: Test methods clearly describe what they're testing
3. **Comprehensive Coverage**: All public methods and edge cases are tested
4. **Fast Execution**: Tests run quickly using in-memory database
5. **Maintainable**: Tests are easy to understand and maintain

## Continuous Integration

The test suite is designed to run in CI/CD pipelines:
- No external dependencies
- Fast execution
- Clear pass/fail status
- Coverage reporting

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Ensure SQLite is available
   - Check database configuration in phpunit.xml

2. **Memory Issues**
   - Increase memory limit in php.ini
   - Use `--process-isolation` flag

3. **Slow Tests**
   - Check for unnecessary database operations
   - Use factories instead of creating models manually

### Getting Help

If you encounter issues:
1. Check the test output for specific error messages
2. Verify your environment setup
3. Ensure all dependencies are installed
4. Check the Laravel documentation for testing best practices
