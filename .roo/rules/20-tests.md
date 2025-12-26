# Tests

- Use **PestPHP** with clear, human-readable test names.
- Follow **Arrange/Act/Assert** pattern; avoid hidden globals.
- Provide at least one negative test and one edge-case test for each feature.
- Use **factories** for test data setup.
- Include unit tests for business logic, services, and helper classes.
- Maintain high code coverage but focus on meaningful tests over 100% coverage obsession.
- Use beforeEach for test setup where applicable.
- Feature tests must be created for controller class methods.
- Unit tests must be created for service class methods.
- Tests should try to be grouped by the method name in a describe function, with related tests placed in the same describe function.
