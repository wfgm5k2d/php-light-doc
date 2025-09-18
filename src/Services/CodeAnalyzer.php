<?php

declare(strict_types=1);

namespace Wfgm5k2d\PhpLightDoc\Services;

use Symfony\Component\HttpFoundation\Response;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\Parser;
use ReflectionClass;
use ReflectionMethod;

final class CodeAnalyzer
{
    private Parser $parser;
    private array $visitedMethods = [];

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForHostVersion();
    }

    /**
     * Основной метод для анализа, который запускает рекурсивный поиск.
     */
    public function analyze(string $controllerClass, string $methodName): array
    {
        $this->visitedMethods = []; // Сбрасываем состояние для каждого нового анализа
        return $this->analyzeMethodForResponses($controllerClass, $methodName);
    }

    /**
     * Рекурсивно анализирует метод на предмет возможных кодов ответа.
     */
    private function analyzeMethodForResponses(string $class, string $method, array $methodParameters = []): array
    {
        $signature = "{$class}::{$method}";
        if (isset($this->visitedMethods[$signature])) {
            return []; // Предотвращение бесконечной рекурсии
        }

        try {
            $reflectionClass = new ReflectionClass($class);
            if (str_contains($reflectionClass->getFileName(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                return []; // Не анализируем код поставщиков
            }

            if (!$reflectionClass->hasMethod($method)) {
                return [];
            }

            $reflectionMethod = $reflectionClass->getMethod($method);
        } catch (\ReflectionException $e) {
            return []; // Класс или метод не найден
        }

        $this->visitedMethods[$signature] = true;
        $fileContents = file_get_contents($reflectionMethod->getFileName());
        $ast = $this->parser->parse($fileContents);

        $nodeFinder = new NodeFinder;
        $methodNode = $nodeFinder->findFirst($ast, function (Node $node) use ($method) {
            return $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === $method;
        });

        if (!$methodNode || $methodNode->stmts === null) {
            return [];
        }

        $responseCodes = [];

        // Собираем типы параметров из текущего метода для анализа вызовов
        $currentMethodParameters = [];
        foreach ($reflectionMethod->getParameters() as $param) {
            if ($param->getType() && !$param->getType()->isBuiltin()) {
                $currentMethodParameters[$param->getName()] = $param->getType()->getName();
            }
        }

        // Объединяем с параметрами из вызывающего метода
        $methodParameters = array_merge($currentMethodParameters, $methodParameters);

        foreach ($methodNode->stmts as $statement) {
            $nodes = $nodeFinder->find($statement, function (Node $node) {
                return $node instanceof Throw_ || $node instanceof Return_ || $node instanceof MethodCall;
            });

            foreach ($nodes as $node) {
                if ($node instanceof Throw_) {
                    $responseCodes[] = $this->getCodeFromException($node);
                }

                if ($node instanceof Return_) {
                    $responseCodes[] = $this->getCodeFromReturn($node);
                }

                if ($node instanceof MethodCall) {
                    $varName = $node->var->name ?? null;

                    // Если это вызов $this->someMethod()
                    if ($varName === 'this') {
                        $methodToCall = $node->name->toString();
                        $responseCodes = array_merge($responseCodes, $this->analyzeMethodForResponses($class, $methodToCall, $methodParameters));
                    }
                    // Если это вызов переменной, которая была передана как параметр
                    elseif (is_string($varName) && isset($methodParameters[$varName])) {
                        $classToAnalyze = $methodParameters[$varName];
                        $methodToCall = $node->name->toString();
                        $responseCodes = array_merge($responseCodes, $this->analyzeMethodForResponses($classToAnalyze, $methodToCall));
                    }
                }
            }
        }

        return array_values(array_filter(array_unique($responseCodes)));
    }

    private function getCodeFromException(Throw_ $node): ?int
    {
        if (!$node->expr instanceof New_) {
            return 500; // Неизвестное исключение
        }

        /** @var Name $exceptionClass */
        $exceptionClass = $node->expr->class;
        $fqcn = $exceptionClass->toString();

        // Аргументы конструктора исключения
        $args = $node->expr->args;

        if (count($args) >= 2) {
            $codeNode = $args[1]->value; // Второй аргумент - код
            return $this->resolveCodeFromNode($codeNode) ?? 500;
        }

        // Если код не передан, пытаемся получить его из самого класса исключения
        try {
            $reflectionException = new ReflectionClass($fqcn);
            if ($reflectionException->hasProperty('code')) {
                $props = $reflectionException->getDefaultProperties();
                if (isset($props['code']) && is_int($props['code'])) {
                    return $props['code'];
                }
            }
        } catch (\ReflectionException $e) {
            // Класс не найден, ничего не делаем
        }

        return 500; // По умолчанию для исключений
    }

    private function getCodeFromReturn(Return_ $node): ?int
    {
        // return new JsonResponse(..., $code)
        if ($node->expr instanceof New_ && $node->expr->class instanceof Name) {
            $className = $node->expr->class->toString();
            if (in_array($className, ['JsonResponse', '\Illuminate\Http\JsonResponse'])) {
                if (isset($node->expr->args[1])) {
                    return $this->resolveCodeFromNode($node->expr->args[1]->value);
                }
                return 200; // По умолчанию для JsonResponse
            }
        }

        // return response()->json(..., $code)
        if ($node->expr instanceof MethodCall && $node->expr->name->toString() === 'json') {
            if (isset($node->expr->args[1])) {
                return $this->resolveCodeFromNode($node->expr->args[1]->value);
            }
            return 200; // По умолчанию для response()->json()
        }

        return null;
    }

    private function resolveCodeFromNode(Node $node): ?int
    {
        // Код указан как число: 400
        if ($node instanceof LNumber) {
            return $node->value;
        }

        // Код указан как константа класса: Response::HTTP_BAD_REQUEST
        if ($node instanceof ClassConstFetch && $node->class instanceof Name) {
            $className = $node->class->toString();
            $constName = $node->name->toString();
            // Простой резолвер для стандартных констант ответа
            if ($className === 'Response') {
                $fqcn = Response::class;
                if (defined("{$fqcn}::{$constName}")) {
                    return constant("{$fqcn}::{$constName}");
                }
            }
        }

        return null;
    }
}
