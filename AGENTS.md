# Repository Guidelines

## Project Structure & Module Organization
Source code lives in `src/` and follows PSR-4 under the `MemoryPack\\` namespace. Core wire-format logic is in `src/Core/`, schema and attribute mapping in `src/Mapping/`, and serializers/formatters in `src/Formatters/`. Tests live in `tests/`, with reusable fixtures under `tests/Fixtures/`. The C# interop check is a single-file script at `tests/CSharpInterop.cs`.

## Build, Test, and Development Commands
- `composer install` installs PHP 8.4 dependencies.
- `composer test` runs the Pest test suite.
- `dotnet run tests/CSharpInterop.cs -- write` runs the C# interop fixture and prints a payload for PHP to read.
- `dotnet run tests/CSharpInterop.cs -- read <base64>` verifies PHP-generated payloads in C#.

## Coding Style & Naming Conventions
Use `declare(strict_types=1);` in PHP files. Keep code ASCII unless a fixture or payload needs non-ASCII data. Follow the existing style: 4-space indentation, short methods, and explicit type declarations. Use PascalCase for classes and attributes, camelCase for methods and properties, and `tests/Fixtures/*` for sample models. Prefer small, focused formatters instead of adding logic to core readers/writers.

## Testing Guidelines
Pest is the only test framework. Name tests with behavior-focused `it('...')` descriptions and keep interop tests in `tests/MemoryPackSerializerTest.php`. Add coverage for both PHP round trips and C# interop when touching wire format, schema inference, or formatters. Run `composer test` before committing.

## Commit & Pull Request Guidelines
Commit messages are short, imperative, and scoped to the change, for example: `Refine MemoryPack wire format and interop tests`. Keep PRs focused, describe the behavior change, and mention any PHP/C# protocol impact. Link related issues when available and include test results for serializer or interop changes.

## Agent Notes
Do not overwrite existing contributor guidance files. Keep changes surgical and avoid unrelated refactors. When changing the wire format, update both PHP and C# fixtures in the same patch and verify with the test suite.
