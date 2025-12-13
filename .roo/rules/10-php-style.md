# PHP Style

## ✅ General Coding Standards

- Follow **PSR-12** coding style and structure.
- Don't do declare(strict_types=1);.
- Prefer short, expressive, and readable code.
- Use **meaningful, descriptive variable, function, class, and file names**.
- Apply proper PHPDoc blocks for classes, methods, and complex logic.
- Organize code into small, reusable functions or classes with single responsibility.
- Avoid magic numbers or hard-coded strings; use constants or config files.

## ✅ PHP 8.2/8.4 Best Practices

- Use **readonly properties** to enforce immutability where applicable.
- Use **Enums** instead of string or integer constants.
- Utilize **First-class callable syntax** for callbacks.
- Leverage **Constructor Property Promotion**.
- Use **Union Types**, **Intersection Types**, and **true/false return types** for strict typing.
- Apply **Static Return Type** where needed.
- Use the **Nullsafe Operator (?->)** for optional chaining.
- Adopt **final classes** where extension is not intended.
- Use **Named Arguments** for improved clarity when calling functions with multiple parameters.

## ✅ Software Quality & Maintainability

- Aim for **CUPID Principles** for joyful, habitable code (as an alternative/complementary lens to SOLID):
    - **Composable**: Small surface area with narrow, opinionated APIs; intention-revealing; minimal dependencies.
    - **Unix philosophy**: Do one thing well (single, comprehensive purpose from an outside-in perspective).
    - **Predictable**: Behaves as expected; deterministic (robust, reliable, resilient); observable (instrumentation, telemetry, monitoring).
    - **Idiomatic**: Follows language idioms, ecosystem conventions, and team style for reduced cognitive load.
    - **Domain-based**: Mirrors problem domain in language, types, structure, and boundaries.

- Keep the **SOLID Principles** in mind but CUPID Principles take priority. THe SOLID principles are:
    - Single Responsibility Principle (SRP)
    - Open/Closed Principle (OCP)
    - Liskov Substitution Principle (LSP)
    - Interface Segregation Principle (ISP)
    - Dependency Inversion Principle (DIP)

- Follow **DRY** (Don't Repeat Yourself) and **KISS** (Keep It Simple, Stupid) principles.
- Apply **YAGNI** (You Aren't Gonna Need It) to avoid overengineering.
- Document complex logic with PHPDoc and inline comments.
- Comments should explain "why" in addition to "what" the code does.
