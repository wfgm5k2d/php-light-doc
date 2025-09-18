<?php

namespace Wfgm5k2d\PhpLightDoc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;
use Wfgm5k2d\PhpLightDoc\Attributes\DocGName;
use Wfgm5k2d\PhpLightDoc\Attributes\DocMiddleware;
use Wfgm5k2d\PhpLightDoc\Attributes\DocResponseCodes;
use Wfgm5k2d\PhpLightDoc\Attributes\DocRName;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DocumentationGenerator extends Command
{
    protected $signature = 'doc:generate';
    protected $description = 'Создает документацию';

    protected array $controllers = [];
    protected array $paths;
    protected array $excludePaths;

    public function handle(): int
    {
        $this->paths = explode(',', config('php-light-doc.dirs.scan'));
        $this->excludePaths = explode(',', config('php-light-doc.dirs.exclude'));
        $result = $this->generate();

        file_put_contents(config('php-light-doc.path.file-documentation'), json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

        $this->info('Документация успешно сгенерирована');

        return 0;
    }

    public function generate(): array
    {
        $this->findControllers();

        return $this->parseRoutes();
    }

    protected function findControllers(): void
    {
        foreach ($this->paths as $path) {
            $fullPath = base_path($path);
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath);
            }
        }
    }

    protected function scanDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getFilename() === '.') {
                continue;
            }

            $realPath = realpath($file->getPathname());
            // Пропускаем файлы и папки, если они в исключениях
            if ($this->isExcluded($realPath)) {
                continue;
            }

            if ($file->isFile() && preg_match('/Controller\.php$/', $file->getFilename())) {
                $className = $this->getClassNameFromFile($file->getPathname());
                if ($className) {
                    $this->controllers[$className] = $className;
                }
            }
        }
    }

    protected function getClassNameFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
            if (preg_match('/class\s+(\w+)\s+/', $contents, $matches)) {
                return $namespace . '\\' . $matches[1];
            }
        }
        return null;
    }

    protected function parseRoutes(): array
    {
        $routes = Route::getRoutes();
        $result = [];

        foreach ($routes as $route) {
            if (!($controllerData = $this->parseRoute($route))) {
                continue;
            }

            $controller = $controllerData['controller'];
            $httpMethods = $controllerData['uri']['methods'];
            unset($controllerData['controller']);

            $groupInfo = $this->extractControllerGroup($controller);
            $mainGroupName = $groupInfo['group'];
            $subGroupName = $groupInfo['name'];

            // === Сценарий 1: Используется двухуровневая группировка ===
            if ($mainGroupName !== null) {
                // Создаем основную группу, если ее нет
                if (!isset($result[$mainGroupName])) {
                    $result[$mainGroupName] = [
                        'type' => 'main_group', // Маркер для шаблона
                        'name' => $mainGroupName,
                        'subgroups' => [],
                    ];
                }

                // Создаем подгруппу по FQCN контроллера, если ее нет
                if (!isset($result[$mainGroupName]['subgroups'][$controller])) {
                    $middlewareInfo = $this->extractMiddlewareInfo($controller);
                    $result[$mainGroupName]['subgroups'][$controller] = [
                        'name' => $subGroupName,
                        'controller' => $controller,
                        'request' => $middlewareInfo ?: $this->extractMiddlewareInfoFromRoute($route),
                        'data' => [],
                    ];
                }

                $methodKey = implode(', ', $httpMethods);
                if (!isset($result[$mainGroupName]['subgroups'][$controller]['data'][$methodKey])) {
                    $result[$mainGroupName]['subgroups'][$controller]['data'][$methodKey] = [
                        'method' => $httpMethods,
                        'uri' => [],
                    ];
                }

                $result[$mainGroupName]['subgroups'][$controller]['data'][$methodKey]['uri'][] = $controllerData;
            }
            // === Сценарий 2: Используется старая, одноуровневая группировка ===
            else {
                $groupName = $subGroupName; // В этом случае 'name' - это и есть имя группы

                // Создаем группу по FQCN контроллера, если ее нет
                if (!isset($result[$controller])) {
                    $middlewareInfo = $this->extractMiddlewareInfo($controller);
                    $result[$controller] = [
                        'type' => 'single_group', // Маркер для шаблона
                        'group' => $groupName,
                        'controller' => $controller,
                        'request' => $middlewareInfo ?: $this->extractMiddlewareInfoFromRoute($route),
                        'data' => [],
                    ];
                }

                $methodKey = implode(', ', $httpMethods);
                if (!isset($result[$controller]['data'][$methodKey])) {
                    $result[$controller]['data'][$methodKey] = [
                        'method' => $httpMethods,
                        'uri' => [],
                    ];
                }

                $result[$controller]['data'][$methodKey]['uri'][] = $controllerData;
            }
        }

        // Преобразуем ассоциативные массивы в индексные для JSON
        foreach ($result as &$group) {
            if ($group['type'] === 'main_group') {
                $group['subgroups'] = array_values($group['subgroups']);
                foreach ($group['subgroups'] as &$subgroup) {
                    $subgroup['data'] = array_values($subgroup['data']);
                }
            } else { // 'single_group'
                $group['data'] = array_values($group['data']);
            }
        }

        return array_values($result);
    }

    protected function parseRoute(RouteInstance $route): ?array
    {
        $action = $route->getAction();
        if (!isset($action['controller'])) {
            return null;
        }

        $controller = $action['controller'];
        $method = '__invoke';

        if (str_contains($controller, '@')) {
            [$controller, $method] = explode('@', $controller);
        }

        if (!in_array($controller, $this->controllers, true)) {
            return null;
        }

        // Проверяем, существует ли метод в контроллере
        $isDeprecated = false;
        if (!method_exists($controller, $method)) {
            $isDeprecated = true;
        }

        $queryParams = [];
        $bodyParams = [];
        $responses = [];
        $routeName = '';

        if (!$isDeprecated) {
            try {
                // Получаем параметры метода
                $reflectionMethod = new \ReflectionMethod($controller, $method);

                foreach ($reflectionMethod->getParameters() as $param) {
                    $type = $param->getType();

                    if ($type && !$type->isBuiltin()) {
                        $className = $type->getName();

                        if ($className === \Illuminate\Http\Request::class) {
                            continue;
                        }

                        if (is_subclass_of($className, FormRequest::class)) {
                            $bodyParams = $this->extractRulesFromRequest($className);
                        }
                    } elseif ($type && $type->getName() === 'string') {
                        $queryParams[] = $param->getName();
                    }
                }

                $routeName = $this->extractRouteName($controller, $method);
                $responses = $this->extractResponseCodes($controller, $method);
            } catch (\Exception $e) {
                $isDeprecated = true;
            }
        }

        return [
            'controller' => $controller,
            'method' => $method,
            'responses' => $responses,
            'uri' => [
                'name' => $routeName,
                'uri' => $route->uri(),
                'methods' => $route->methods(),
            ],
            'query_params' => $queryParams,
            'body_params' => $bodyParams,
            'isDeprecated' => $isDeprecated,
        ];
    }

    // Можно юзать как group параметр в комментариях
    // Так и аттрибут #[DocGName('Тут мой текст')]
    protected function extractControllerGroup(string $controller): array
    {
        $reflection = new \ReflectionClass($controller);

        // Проверяем атрибуты (PHP 8+)
        foreach ($reflection->getAttributes(DocGName::class) as $attribute) {
            $instance = $attribute->newInstance();
            return [
                'name'  => $instance->name,  // Имя подгруппы или единственное имя
                'group' => $instance->group, // Имя основной группы, будет NULL, если не указано
            ];
        }

        // Проверяем комментарии в файле
        $file = new \SplFileObject($reflection->getFileName());
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (preg_match('/^\/\/\s*group\s+(.+)/', $line, $matches)) {
                $groupName = trim($matches[1]);
                return [
                    'name'  => $groupName,
                    'group' => null, // Комментарии поддерживают только один уровень
                ];
            }
        }

        return [
            'name'  => class_basename($controller), // По умолчанию - имя класса
            'group' => null,
        ];
    }

    // Можно юзать как name параметр в комментариях
    // Так и аттрибут #[DocRName('Тут мой текст')]
    protected function extractRouteName(string $controller, string $method): ?string
    {
        $reflection = new \ReflectionMethod($controller, $method);

        // Проверяем атрибуты (PHP 8+)
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() === DocRName::class) {
                return $attribute->newInstance()->docRName;
            }
        }

        // Проверяем комментарии в файле
        $file = new \SplFileObject($reflection->getFileName());
        $file->seek($reflection->getStartLine() - 2); // Читаем строку перед методом

        while ($file->key() < $reflection->getStartLine()) {
            $line = trim($file->current());
            if (preg_match('/^\/\/\s*name\s+(.+)/', $line, $matches)) {
                return trim($matches[1]);
            }
            $file->next();
        }

        return null;
    }

    // В коде ищется $this->max и $this->min для max и min значений
    protected function extractRulesFromRequest(string $requestClass): array
    {
        try {
            if (!class_exists($requestClass)) {
                return [];
            }

            $reflection = new \ReflectionClass($requestClass);

            if (!$reflection->hasMethod('rules')) {
                return [];
            }

            $method = $reflection->getMethod('rules');

            if (!$method->isPublic() || $method->isStatic()) {
                return [];
            }

            $fileName = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if (!$fileName || !file_exists($fileName)) {
                return [];
            }

            $fileContents = file($fileName);
            $methodCode = implode('', array_slice($fileContents, $startLine - 1, $endLine - $startLine + 1));

            if (!preg_match('/return\s+\[(.*?)\];/s', $methodCode, $matches)) {
                return [];
            }

            $rulesArray = trim($matches[1]);
            preg_match_all('/[\'"]([\w.*]+)[\'"]\s*=>\s*(\[.*?\]|".*?"|\'.*?\')/s', $rulesArray, $matches, PREG_SET_ORDER);

            $bodyParams = [];

            foreach ($matches as $match) {
                $paramName = $match[1];
                $rules = trim($match[2]);

                if (str_starts_with($rules, '[')) {
                    preg_match_all('/[\'"]?([\w:.|\\\\]+)[\'"]?/', $rules, $ruleMatches);
                    $ruleList = $ruleMatches[1];
                } else {
                    $ruleList = explode('|', trim($rules, '\'"'));
                }

                $validation = [
                    'required' => false,
                    'nullable' => false,
                    'string' => false,
                    'min' => false,
                    'max' => false,
                    'regex' => false,
                    'array' => false,
                    'in' => false,
                    'boolean' => false,
                    'image' => false,
                    'files' => false,
                    'prohibited' => false,
                    'mimes' => false
                ];

                foreach ($ruleList as $rule) {
                    $rule = trim($rule);

                    if ($rule === 'nullable') {
                        $validation['nullable'] = true;
                    } elseif ($rule === 'string') {
                        $validation['string'] = true;
                    } elseif ($rule === 'numeric') {
                        $validation['numeric'] = true;
                    } elseif ($rule === 'integer') {
                        $validation['integer'] = true;
                    } elseif ($rule === 'prohibited') {
                        $validation['prohibited'] = true;
                    } elseif ($rule === 'required') {
                        $validation['required'] = true;
                    } elseif ($rule === 'boolean') {
                        $validation['boolean'] = true;
                    } elseif ($rule === 'array') {
                        $validation['array'] = true;
                    } elseif ($rule === 'image') {
                        $validation['image'] = true;
                    } elseif ($rule === 'file') {
                        $validation['files'] = true;
                    } elseif (preg_match('/mimes:(\$\w+|\w+)/', $rule, $mimesMatch)) {
                        $mimesValue = $this->extractVariableValue($fileContents, $mimesMatch[1]);
                        $validation['mimes'] = $mimesValue ?: false;
                    } elseif (preg_match('/min:(\$\w+|\d+)/', $rule, $minMatch)) {
                        $minValue = $this->extractVariableValue($fileContents, $minMatch[1]);
                        $validation['min'] = $minValue ?: false;
                    } elseif (preg_match('/max:(\$\w+|\d+)/', $rule, $maxMatch)) {
                        $maxValue = $this->extractVariableValue($fileContents, $maxMatch[1]);
                        $validation['max'] = $maxValue ?: false;
                    } elseif (preg_match('/regex:(\/.*?\/[a-z]*)/', $rule, $regexMatch)) {
                        $validation['regex'] = $regexMatch[1];
                    }
                }

                $bodyParams[] = [
                    'parameter' => $paramName,
                    'validation' => $validation,
                ];
            }

            return $bodyParams;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function extractVariableValue(array $fileContents, string $variableName): array|false|int
    {
        if (is_numeric($variableName)) {
            return (int)$variableName;
        }

        foreach ($fileContents as $line) {
            if (preg_match('/' . preg_quote($variableName, '/') . '\s*=\s*([0-9]+)/', $line, $matches)) {
                return (int)$matches[1];
            } elseif (preg_match('/' . preg_quote($variableName, '/') . '\s*=\s*\[(.*?)\]/', $line, $arrayMatch)) {
                return explode(',', str_replace(["'", '"'], '', $arrayMatch[1]));
            }
        }
        return false;
    }

    protected function isExcluded(string $path): bool
    {
        foreach ($this->excludePaths as $exclude) {
            if (str_contains($path, $exclude)) {
                return true;
            }
        }
        return false;
    }

    // Можно юзать как middleware-name, middleware-value параметр в комментариях
    // Так и аттрибут #[DocMiddlewareName('Требуется аутентификация', 'Authorization: Bearer {token}')]
    protected function extractMiddlewareInfo(string $controller): array
    {
        $reflection = new \ReflectionClass($controller);

        // Проверяем атрибут (PHP 8+)
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() === DocMiddleware::class) {
                $instance = $attribute->newInstance();
                return [
                    'custom_middleware_name' => $instance->name,
                    'custom_middleware_value' => $instance->value
                ];
            }
        }

        // Проверяем комментарии
        $docComment = $reflection->getDocComment();
        if ($docComment) {
            if (preg_match('/middleware-name\s+(.+)/', $docComment, $nameMatch) &&
                preg_match('/middleware-value\s+(.+)/', $docComment, $valueMatch)) {
                return [
                    'custom_middleware_name' => trim($nameMatch[1]),
                    'custom_middleware_value' => trim($valueMatch[1])
                ];
            }
        }

        return [];
    }

    // Можно юзать как response-codes 200 404 500 параметр в комментариях
    // Так и аттрибут #[DocResponseCodes([200,404,500])]
    protected function extractResponseCodesInfo(string $controller, string $method): array
    {
        $reflection = new \ReflectionMethod($controller, $method);

        // Проверяем атрибуты (PHP 8+)
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() === DocResponseCodes::class) {
                return $attribute->newInstance()->codes;
            }
        }

        // Проверяем комментарии в файле
        $file = new \SplFileObject($reflection->getFileName());
        $file->seek($reflection->getStartLine() - 2); // Читаем строку перед методом

        while ($file->key() < $reflection->getStartLine()) {
            $line = trim($file->current());
            if (preg_match('/^\/\/\s*response-codes\s+(.+)/', $line, $matches)) {
                $codes = explode(' ', $matches[1]);
                return array_map(fn($code) => (int) trim($code), $codes);
            }

            $file->next();
        }

        return [];
    }

    protected function extractMiddlewareInfoFromRoute(RouteInstance $route): array
    {
        $middlewares = $route->middleware();
        $requestInfo = [];

        foreach ($middlewares as $middleware) {
            if (str_starts_with($middleware, 'auth')) {
                $requestInfo['auth_required'] = 'Bearer auth';
                $requestInfo['authorization_header'] = 'Authorization: Bearer {token}';
            } elseif (preg_match('/throttle:(\d+),(\d+)/', $middleware, $matches)) {
                $requestInfo['rate_limit'] = ['max_requests' => $matches[1], 'per_minutes' => $matches[2]];
            } elseif (preg_match('/can:(\w+)/', $middleware, $matches)) {
                $requestInfo['permissions_required'] = $matches[1];
            } elseif (preg_match('/requires:(\w+)/', $middleware, $matches)) {
                // Если middleware требует какой-то параметр (например, password), фиксируем его
                $requestInfo['required_params'][] = $matches[1];
            }
        }

        return $requestInfo;
    }

    protected function extractResponseCodes(string $controller, string $method): array
    {
        try {
            $reflection = new \ReflectionClass($controller);
            if (!$reflection->hasMethod($method)) {
                return [200]; // По умолчанию успешный ответ, если метод не найден
            }

            $methodReflection = $reflection->getMethod($method);
            $fileName = $methodReflection->getFileName();
            $startLine = $methodReflection->getStartLine();
            $endLine = $methodReflection->getEndLine();

            if (!$fileName || !file_exists($fileName)) {
                return [200];
            }

            // Проверка атрибута DocResponse
            $docResponseAttribute = $this->extractResponseCodesInfo($controller, $method);
            if (!empty($docResponseAttribute)) {
                return $docResponseAttribute;
            }

            $fileContents = file($fileName);
            $methodCode = implode('', array_slice($fileContents, $startLine - 1, $endLine - $startLine + 1));

            $responses = [];

            // Проверяем коды ответов в response()->json([...], <code>)
            if (preg_match_all('/return\s+response\(\)->json\([^,]+,\s*(\d+)\)/', $methodCode, $matches)) {
                $responses = array_merge($responses, array_map('intval', $matches[1]));
            }

            // Проверяем коды в new JsonResponse([...], <code>)
            if (preg_match_all('/return\s+new\s+JsonResponse\([^,]+,\s*(\d+)\)/', $methodCode, $matches)) {
                $responses = array_merge($responses, array_map('intval', $matches[1]));
            }

            // Проверяем константы типа Response::HTTP_BAD_REQUEST
            if (preg_match_all('/ return\s+new\s+JsonResponse\( (?:[^,]|\[.*?\])+ , \s* Response::(HTTP_[A-Z_]+) \s* \)/sx', $methodCode, $matches)) {
                foreach ($matches[1] as $constant) {
                    if (defined("Symfony\Component\HttpFoundation\Response::$constant")) {
                        $responses[] = constant("Symfony\Component\HttpFoundation\Response::$constant");
                    }
                }
            }

            // Если коды явно указаны, 200 не добавляем
            if (!empty($responses)) {
                return array_unique($responses);
            }

            // Если return new JsonResponse([...]); или response()->json([...]); без кода — значит 200
            if (preg_match('/return\s+new\s+JsonResponse\(/', $methodCode) ||
                preg_match('/return\s+response\(\)->json\(/', $methodCode)) {
                return [200];
            }

            return [200];
        } catch (\Throwable $e) {
            return [200];
        }
    }
}
