### 1. Обновленный `README.md` (с информацией о группировке)

# Laravel API Documentation Generator

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![API Docs](https://img.shields.io/badge/Docs-100%25%20Automated-brightgreen?style=for-the-badge)

Этот пакет автоматически анализирует контроллеры и маршруты Laravel, извлекая информацию о группах, именах, middleware, кодах ответов и формируя удобную документацию в формате JSON.

## 📦 Установка

```bash
composer require wfgm5k2d/php-light-doc
```
## 📦 Опубликуйте все файлы

```bash
php artisan vendor:publish --provider='Wfgm5k2d\PhpLightDoc\Providers\PhpLightDocServiceProvider'
```

## Настройте переменные окружения
```dotenv
# Ваш путь до файла с документацией
PATH_TO_FILE_DOCUMENTATION='/your_full_path_to_file/api_documentation.json'
# Перечислите папки для сканирования через запятую если их много
PATH_TO_DIR_SCAN='app/Domain, app/Http/Controllers'
# Исключите папки из сканирования через запятую если их много
PATH_TO_DIR_EXCLUDE='app/Http/Controllers/Admin, CustomBackupController'
```
## 🚀 Использование
После установки в Laravel появится команда для генерации документации:

```bash
php artisan doc:generate
```
Документация будет сохранена в файле, указанном в `.env`, и доступна в браузере по маршруту `/doc`.

## 📝 Аннотации и атрибуты
Вы можете дополнительно аннотировать контроллеры и методы для более точного описания API.

### Группировка контроллеров
Группы помогают структурировать документацию по тематическим разделам.

#### Простая группировка
Вы можете дать название группе контроллера. Все роуты этого контроллера попадут в указанную группу.

**Через комментарий:**

```php
// group Пользователи
final class UserController extends Controller
```
**Или атрибут:**

```php
#[DocGName('Пользователи')]
final class UserController extends Controller
```

---

#### **🆕 Расширенная группировка (вложенные группы)**
Вы также можете создавать группы верхнего уровня для объединения нескольких контроллеров. Для этого в атрибуте `DocGName` нужно передать второй параметр — название основной группы.

**Через атрибут:**

```php
// Этот контроллер попадет в подгруппу "Пользователи API" внутри основной группы "Пользователи"
#[DocGName('Пользователи API', 'Пользователи')]
final class UserController extends Controller {
    // ...
}

// Этот контроллер попадет в подгруппу "Аутентификация" внутри той же основной группы "Пользователи"
#[DocGName('Аутентификация', 'Пользователи')]
final class AuthController extends Controller {
    // ...
}
```
Это позволяет создавать более сложную и удобную структуру документации.

---

## Описание маршрутов
Вы можете задать понятное название маршруту:

**Через комментарий:**

```php
// name Создать заявку на компенсацию
public function createApplication(Request $request): JsonResponse
```
**Или атрибут:**

```php
#[DocRName('Создать заявку на компенсацию')]
public function createApplication(Request $request): JsonResponse
```

## Требования middleware
Если метод требует авторизации или специальных заголовков, укажите это явно.

**Через комментарий:**

```php
// middleware-name Требуется аутентификация
// middleware-value Authorization: Bearer {token}
final class SecureController extends Controller
```
**Или атрибут:**

```php
#[DocMiddleware('Требуется аутентификация', 'Authorization: Bearer {token}')]
final class SecureController extends Controller
```

По умолчанию проверяется такой набор middleware: `auth`, `throttle`, `can`, `requires`.

## Коды ответов
Пакет ищет коды ответов в конструкциях `response()->json()`, `new JsonResponse()`, включая константы `Response::HTTP_*`. Вы также можете указать их вручную.

**Через комментарий:**

```php
// response-codes 200 404 500
public function getUserData(Request $request): JsonResponse```

**Или атрибут:**

```php
#[DocResponseCodes()]
public function getUserData(Request $request): JsonResponse
```

## 🔄 Генерация и просмотр документации
Запустите команду генерации:

```bash
php artisan doc:generate
```

Откройте `/doc` в браузере для просмотра.
