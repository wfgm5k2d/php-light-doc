# Laravel API Documentation Generator

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)![API Docs](https://img.shields.io/badge/Docs-100%25%20Automated-brightgreen?style=for-the-badge)

This package automatically analyzes Laravel controllers and routes, extracting information about groups, names, middleware, response codes, and generating user-friendly documentation in JSON format.

## 📦 Installation

```bash
composer require wfgm5k2d/php-light-doc
```
## 📦 Publish all files

```bash
php artisan vendor:publish --provider='Wfgm5k2d\PhpLightDoc\Providers\PhpLightDocServiceProvider'
```

## Configure environment variables
```dotenv
# Your path to the documentation file
PATH_TO_FILE_DOCUMENTATION='/your_full_path_to_file/api_documentation.json'
# List folders to scan, separated by commas if there are many
PATH_TO_DIR_SCAN='app/Domain, app/Http/Controllers'
# Exclude folders from scanning, separated by commas if there are many
PATH_TO_DIR_EXCLUDE='app/Http/Controllers/Admin, CustomBackupController'
```
## 🚀 Usage
After installation, a command to generate documentation will appear in Laravel:

```bash
php artisan doc:generate
```
The documentation will be saved in the file specified in `.env` and will be available in the browser at the `/doc` route.

## 📝 Annotations and attributes
You can additionally annotate controllers and methods for a more accurate API description.

### Controller Grouping
Groups help structure the documentation into thematic sections.

#### Simple grouping
You can give a name to a controller group. All routes of this controller will fall into the specified group.

**Via comment:**

```php
// group Users
final class UserController extends Controller
```
**Or attribute:**

```php
#[DocGName('Users')]
final class UserController extends Controller
```

---

#### **🆕 Advanced grouping (nested groups)**
You can also create top-level groups to combine multiple controllers. To do this, you need to pass a second parameter to the `DocGName` attribute — the name of the main group.

**Via attribute:**

```php
// This controller will be placed in the "Users API" subgroup within the main "Users" group
#[DocGName('Users API', 'Users')]
final class UserController extends Controller {
    // ...
}

// This controller will be placed in the "Authentication" subgroup within the same main "Users" group
#[DocGName('Authentication', 'Users')]
final class AuthController extends Controller {
    // ...
}
```
This allows you to create a more complex and convenient documentation structure.

---

## Route Description
You can set a clear name for the route:

**Via comment:**

```php
// name Create a compensation request
public function createApplication(Request $request): JsonResponse```
**Or attribute:**

```php
#[DocRName('Create a compensation request')]
public function createApplication(Request $request): JsonResponse
```

## Middleware Requirements
If a method requires authorization or special headers, specify it explicitly.

**Via comment:**

```php
// middleware-name Authentication required
// middleware-value Authorization: Bearer {token}
final class SecureController extends Controller
```
**Or attribute:**

```php
#[DocMiddleware('Authentication required', 'Authorization: Bearer {token}')]
final class SecureController extends Controller
```

By default, the following set of middleware is checked: `auth`, `throttle`, `can`, `requires`.

## Response Codes
The package searches for response codes in these constructs:
```php
return response()->json()
return new JsonResponse([])
return new JsonResponse([], Response::HTTP_...)
return new JsonResponse([], 200)
```
By default, the response code will be 200 if no others are specified.

You can manually specify the available response codes if they are not detected

**Via comment:**

```php
// response-codes 200 404 500
public function getUserData(Request $request): JsonResponse```

**Or attribute:**

```php
#[DocResponseCodes()]
public function getUserData(Request $request): JsonResponse
```

## 🔄 Generating and viewing documentation
Run the generation command:

```bash
php artisan doc:generate
```

Open `/doc` in your browser to view.

## How to develop the package locally?
- Deploy a Laravel project locally. The last package build was on Laravel 12
```bash
laravel new php-light-doc
```
- Inside the project, create a `packages` folder
```bash
cd php-light-doc
mkdir "packages"
```
- This is your packages directory. Add the `wfgm5k2d` namespace here
```bash
mkdir "wfgm5k2d"
```
- Clone the package code from the repository into it
- In the providers, connect the package provider

#### Laravel <= 10v.
- In the `config/app.php` file, add the package provider to the `providers` array
```php
'providers' => [
    ...
    \Wfgm5k2d\PhpLightDoc\Providers\PhpLightDocServiceProvider::class,
]
```

#### Laravel >= 11v.
- In the `bootstrap/providers.php` file, add the package provider to the array
```php
return [
    ...
    \Wfgm5k2d\PhpLightDoc\Providers\PhpLightDocServiceProvider::class,
]
```

- In `composer.json`, connect the package in the autoload section
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Wfgm5k2d\\PhpLightDoc\\": "packages/wfgm5k2d/php-lite-doc/src",
            ...
        }
    }
}
```
