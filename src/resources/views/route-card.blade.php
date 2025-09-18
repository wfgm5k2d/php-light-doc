@php
    /**
     * @var ?array $mainGroup
     * @var array $item
     * @var array $data
     * @var array $uri
     */
@endphp

<div id="{{ \Illuminate\Support\Str::slug($item['controller'] . '-' . ($uri['uri']['name'] ?? $uri['method'])) }}"
     class="bg-white p-6 rounded-lg shadow-md mb-4 scroll-mt-4">

    {{-- Заголовок и URI --}}
    <h1 class="text-2xl font-bold mb-2">{{ $uri['uri']['name'] ?? $uri['method'] }}</h1>
    <div class="text-gray-700 p-2 bg-gray-100 rounded mt-2 mb-2 w-max flex flex-row items-center">
        <div class="{{ \Piratecode\PhpLightDoc\Helper\ColorMethod::getColorMethod(implode(',', $data['method'])) }}">
            {{ implode(',', $data['method']) }}
        </div>
        <span class="text-blue-600 ml-3 font-mono">{{ $uri['uri']['uri'] }}</span>
    </div>

    {{-- Требования запроса (Middleware, Auth, etc.) --}}
    @if($item['request'])
        <h2 class="mt-4 text-xl font-bold">Требования запроса</h2>
        <ul class="mt-2 space-y-2">
            @if(isset($item['request']['custom_middleware_name']))
                <div class="p-2 bg-gray-100 rounded">
                    <p>{{ $item['request']['custom_middleware_name'] }} <span
                                class="text-gray-600 bg-gray-200 text-[12px] px-2 rounded">{{ $item['request']['custom_middleware_value'] }}</span>
                    </p>
                </div>
            @endif
            @if(isset($item['request']['auth_required']))
                <div class="p-2 bg-gray-100 rounded">
                    <p>Требуется аутентификация <span
                                class="text-gray-600 bg-gray-200 text-[12px] px-2 rounded">{{ $item['request']['authorization_header'] }}</span>
                    </p>
                </div>
            @endif
            @if(isset($uri['request']['rate_limit']))
                <li class="p-2 bg-yellow-100 rounded">
                    Ограничение: {{ $uri['request']['rate_limit']['max_requests'] }} запросов
                    за {{ $uri['request']['rate_limit']['per_minutes'] }} минут
                </li>
            @endif
            @if(isset($uri['request']['permissions_required']))
                <li class="p-2 bg-blue-100 rounded">Необходимы
                    права: {{ $uri['request']['permissions_required'] }}</li>
            @endif
        </ul>
    @endif

    {{-- Query Параметры --}}
    @if($uri['query_params'])
        <h2 class="mt-4 text-xl font-bold">Query параметры</h2>
        <ul class="mt-2 space-y-2">
            @foreach($uri['query_params'] as $queryParameter)
                <li class="p-2 bg-gray-100 rounded font-mono">
                    <strong>{{ $queryParameter }}</strong>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Body Параметры --}}
    @if($uri['body_params'])
        <h2 class="mt-4 text-xl font-bold">Body параметры</h2>
        <ul class="mt-2 space-y-2">
            @foreach($uri['body_params'] as $parameter)
                <li class="p-2 bg-gray-100 rounded">
                    <div class="font-mono">
                        <strong>{{ $parameter['parameter'] }}</strong>:
                        @if($parameter['validation']['required'])
                            <span class="text-red-500 text-xs font-bold ml-2">required</span>
                        @endif
                    </div>
                    <ul class="ml-4 mt-1 list-disc list-inside">
                        @foreach($parameter['validation'] as $rule => $value)
                            @if($value !== false && $rule != 'required')
                                <li class="text-sm font-medium
                                    @if(in_array($rule, ['nullable', 'prohibited'])) text-rose-500
                                    @elseif($rule === 'string') text-blue-500
                                    @elseif(in_array($rule, ['integer', 'numeric'])) text-green-500
                                    @elseif(in_array($rule, ['regex', 'min', 'max', 'boolean'])) text-purple-500
                                    @elseif(in_array($rule, ['array', 'in'])) text-orange-500
                                    @else text-gray-600 @endif">
                                    <strong>{{ $rule }}</strong>@if(!is_bool($value))
                                        : <code class="bg-gray-200 px-1 rounded">{{ is_array($value) ? implode(', ', $value) : $value }}</code>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Ответы --}}
    @if($uri['responses'])
        <h2 class="mt-4 text-xl font-bold">Ответы</h2>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach($uri['responses'] as $responseCode)
                <span class="px-3 py-1 text-sm font-semibold rounded-lg text-white
                    @if($responseCode >= 200 && $responseCode < 300) bg-green-500
                    @elseif($responseCode >= 300 && $responseCode < 400) bg-blue-500
                    @elseif($responseCode >= 400 && $responseCode < 500) bg-yellow-600
                    @elseif($responseCode >= 500) bg-red-500
                    @else bg-gray-500 @endif">
                    {{ $responseCode }}
                </span>
            @endforeach
        </div>
    @endif
</div>