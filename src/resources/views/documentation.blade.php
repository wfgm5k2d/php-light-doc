@php
    /**
     * @var \Illuminate\Support\Collection[] $documentation
     */
@endphp
        <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Документация</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-1/8 bg-white p-4 shadow-md overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">API Роуты</h2>
        @foreach($documentation as $item)
            <nav id="sidebar">
                <h3 class="text-lg font-semibold mt-4 text-blue-600">{{ $item['group'] }}</h3>
                @foreach($item['data'] as $data)
                    @foreach($data['uri'] as $uri)
                        <a href="#{{ \Illuminate\Support\Str::slug($uri['uri']['name'] ?? $uri['method']) }}"
                           class="block w-full text-left px-4 py-2 text-gray-700 hover:text-gray-900 text-[12px] border-l-gray-200 border-l-2">
                            <div class="{{ \Wfgm5k2d\PhpLightDoc\Helper\ColorMethod::getColorMethod(implode(',', $data['method'])) }}">
                                {{ implode(',', $data['method']) }}
                            </div> {{ $uri['uri']['name'] ?? $uri['uri']['uri'] }}
                        </a>
                    @endforeach
                @endforeach
            </nav>
        @endforeach
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 bg-gray-50 overflow-y-auto">
        @foreach($documentation as $item)
            @foreach($item['data'] as $data)
                @foreach($data['uri'] as $uri)
                    <div id="{{ \Illuminate\Support\Str::slug($uri['uri']['name'] ?? $uri['method']) }}"
                         class="bg-white p-6 rounded-lg shadow-md mb-4">
                        <h1 class="text-2xl font-bold mb-2">{{ $uri['uri']['name'] ?? $uri['method'] }}</h1>
                        <div class="text-gray-700 p-2 bg-gray-100 rounded mt-2 mb-2 w-max flex flex-row">
                            <div
                                    class="{{ \Wfgm5k2d\PhpLightDoc\Helper\ColorMethod::getColorMethod(implode(',', $data['method'])) }}">{{ implode(',', $data['method']) }}</div>
                            <span class="text-blue-600">
                                &nbsp;&nbsp;&nbsp;{{ $uri['uri']['uri'] }}
                            </span>
                        </div>

                        @if($item['request'])
                            <h2 class="mt-4 text-xl font-bold">Требования запроса</h2>
                            <ul class="mt-2">
                                @if(isset($item['request']['custom_middleware_name']))
                                    <div class="p-2 bg-gray-100 rounded mt-2">
                                        <p>{{ $item['request']['custom_middleware_name'] }} <span
                                                    class="text-gray-600 bg-gray-200 text-[12px] px-2 rounded">{{ $item['request']['custom_middleware_value'] }}</span>
                                        </p>
                                    </div>
                                @endif
                                @if(isset($item['request']['auth_required']))
                                    <div class="p-2 bg-gray-100 rounded mt-2">
                                        <p>Требуется аутентификация <span
                                                    class="text-gray-600 bg-gray-200 text-[12px] px-2 rounded">{{ $item['request']['authorization_header'] }}</span>
                                        </p>
                                    </div>
                                @endif
                                @if(isset($item['request']['rate_limit']))
                                    <li class="p-2 bg-yellow-100 rounded mt-2">
                                        Ограничение: {{ $uri['request']['rate_limit']['max_requests'] }} запросов
                                        за {{ $uri['request']['rate_limit']['per_minutes'] }} минут
                                    </li>
                                @endif
                                @if(isset($item['request']['permissions_required']))
                                    <li class="p-2 bg-blue-100 rounded mt-2">Необходимы
                                        права: {{ $uri['request']['permissions_required'] }}</li>
                                @endif
                            </ul>
                        @endif

                        @if($uri['query_params'])
                            <h2 class="mt-4 text-xl font-bold">Query параметры</h2>
                            <ul class="mt-2">

                                @foreach($uri['query_params'] as $queryParameter)
                                    <li class="p-2 bg-gray-100 rounded mt-2">
                                        <strong>{{ $queryParameter }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($uri['body_params'])
                            <h2 class="mt-4 text-xl font-bold">Body параметры</h2>
                            <ul class="mt-2">

                                @foreach($uri['body_params'] as $parameter)
                                    <li class="p-2 bg-gray-100 rounded mt-2">
                                        <div>
                                            <strong>{{ $parameter['parameter'] }}</strong>: @if($parameter['validation']['required'])
                                                <span class="text-red-500 text-[12px]">required</span>
                                            @endif</div>
                                        <ul class="ml-4 mt-1">
                                            @foreach($parameter['validation'] as $rule => $value)
                                                @if($value !== false && $rule != 'required')
                                                    <li class="text-sm font-medium
                                @if(in_array($rule, ['nullable', 'prohibited'])) text-rose-500
                                @elseif($rule === 'string') text-blue-500
                                @elseif(in_array($rule, ['integer', 'numeric'])) text-green-500
                                @elseif(in_array($rule, ['regex', 'min', 'max', 'boolean'])) text-purple-500
                                @elseif(in_array($rule, ['array', 'in'])) text-orange-500
                                @endif">
                                                        <strong>{{ $rule }}</strong>@if(!is_bool($value))
                                                            : <code>{{ $value }}</code>
                                                        @endif
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach

                            </ul>
                        @endif

                        @if($uri['responses'])
                            <h2 class="mt-4 text-xl font-bold">Ответы</h2>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($uri['responses'] as $responseCode)
                                    <span class="px-3 py-1 text-sm font-semibold rounded-lg text-white
                                        @if($responseCode >= 200 && $responseCode < 300) bg-green-500
                                        @elseif($responseCode >= 300 && $responseCode < 400) bg-blue-500
                                        @elseif($responseCode >= 400 && $responseCode < 500) bg-yellow-500
                                        @elseif($responseCode >= 500) bg-red-500
                                        @else bg-gray-500 @endif">
                                        {{ $responseCode }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        @endforeach
    </main>
</div>
</body>
</html>
