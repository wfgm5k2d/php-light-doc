@php
    /**
     * @var array $documentation
     */

    // 1. Создаем плоский массив роутов для поиска на JavaScript
    $searchableRoutes = [];
    foreach ($documentation as $item) {
        if ($item['type'] === 'main_group') {
            foreach ($item['subgroups'] as $subGroup) {
                foreach ($subGroup['data'] as $data) {
                    foreach ($data['uri'] as $uri) {
                        $searchableRoutes[] = [
                            'group' => $item['name'],
                            'subgroup' => $subGroup['name'],
                            'name' => $uri['uri']['name'] ?? $uri['method'],
                            'method' => implode(',', $data['method']),
                            'uri' => $uri['uri']['uri'],
                            'href' => '#' . \Illuminate\Support\Str::slug($subGroup['controller'] . '-' . ($uri['uri']['name'] ?? $uri['method'])),
                        ];
                    }
                }
            }
        } else { // 'single_group'
            foreach ($item['data'] as $data) {
                foreach ($data['uri'] as $uri) {
                    $searchableRoutes[] = [
                        'group' => $item['group'],
                        'subgroup' => null,
                        'name' => $uri['uri']['name'] ?? $uri['method'],
                        'method' => implode(',', $data['method']),
                        'uri' => $uri['uri']['uri'],
                        'href' => '#' . \Illuminate\Support\Str::slug($item['controller'] . '-' . ($uri['uri']['name'] ?? $uri['method'])),
                    ];
                }
            }
        }
    }
@endphp
        <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Документация</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html {
            scroll-behavior: smooth;
        }
        .sidebar-link-active {
            background-color: #ebf8ff;
            color: #2b6cb0;
            font-weight: 600;
            border-left-color: #4299e1 !important;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside id="sidebar-nav" class="w-1/4 bg-white p-4 shadow-md overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Навигация</h2>

        {{-- 2. HTML для поиска --}}
        <div class="mb-4">
            <input type="search" id="api-search-input" placeholder="Поиск по названию роута, маршруту, названию группы..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
        </div>

        {{-- Контейнер для результатов поиска (изначально скрыт) --}}
        <div id="search-results" class="flex-grow overflow-y-auto" hidden></div>

        <nav id="main-navigation" class="flex-grow overflow-y-auto">
            @foreach($documentation as $item)
                @if($item['type'] === 'main_group')
                    {{-- ЗАГОЛОВОК ГРУППЫ С ИКОНКОЙ --}}
                    <h2 class="text-lg font-bold mt-4 text-gray-800 flex items-center gap-2">
                        <span>{{ $item['name'] }}</span>
                    </h2>
                    <div class="ml-2">
                        @foreach($item['subgroups'] as $subGroup)
                            <h3 class="text-md font-semibold mt-2 ml-2 text-blue-600 cursor-pointer sidebar-toggler flex items-center gap-2" data-target-id="sub-group-{{ $loop->parent->index }}-{{ $loop->index }}">
                                <svg class="toggler-icon w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                <span>{{ $subGroup['name'] }}</span>
                            </h3>
                            <div id="sub-group-{{ $loop->parent->index }}-{{ $loop->index }}" class="ml-4 collapsible-content" hidden>
                                @foreach($subGroup['data'] as $data)
                                    @foreach($data['uri'] as $uri)
                                        <a href="#{{ \Illuminate\Support\Str::slug($subGroup['controller'] . '-' . ($uri['uri']['name'] ?? $uri['method'])) }}"
                                           class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 text-[12px] border-l-gray-200 border-l-2">
                                            <div class="{{ \Piratecode\PhpLightDoc\Helper\ColorMethod::getColorMethod(implode(',', $data['method'])) }}">
                                                {{ implode(',', $data['method']) }}
                                            </div> {{ $uri['uri']['name'] ?? $uri['uri']['uri'] }}
                                        </a>
                                    @endforeach
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- ЗАГОЛОВОК ГРУППЫ С ИКОНКОЙ --}}
                    <h3 class="text-lg font-semibold mt-4 text-blue-600 cursor-pointer sidebar-toggler flex items-center gap-2" data-target-id="single-group-{{ $loop->index }}">
                        <svg class="toggler-icon w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span>{{ $item['group'] }}</span>
                    </h3>
                    <div id="single-group-{{ $loop->index }}" class="ml-2 collapsible-content" hidden>
                        @foreach($item['data'] as $data)
                            @foreach($data['uri'] as $uri)
                                <a href="#{{ \Illuminate\Support\Str::slug($item['controller'] . '-' . ($uri['uri']['name'] ?? $uri['method'])) }}"
                                   class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 text-[12px] border-l-gray-200 border-l-2">
                                    <div class="{{ \Piratecode\PhpLightDoc\Helper\ColorMethod::getColorMethod(implode(',', $data['method'])) }}">
                                        {{ implode(',', $data['method']) }}
                                    </div> {{ $uri['uri']['name'] ?? $uri['uri']['uri'] }}
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 bg-gray-50 overflow-y-auto">
        {{-- ... (остальной код без изменений) ... --}}
        @foreach($documentation as $item)
            @if($item['type'] === 'main_group')
                @foreach($item['subgroups'] as $subGroup)
                    @foreach($subGroup['data'] as $data)
                        @foreach($data['uri'] as $uri)
                            @include('php-light-doc::route-card', ['mainGroup' => $item, 'item' => $subGroup, 'data' => $data, 'uri' => $uri])
                        @endforeach
                    @endforeach
                @endforeach
            @else
                @foreach($item['data'] as $data)
                    @foreach($data['uri'] as $uri)
                        @include('php-light-doc::route-card', ['mainGroup' => null, 'item' => $item, 'data' => $data, 'uri' => $uri])
                    @endforeach
                @endforeach
            @endif
        @endforeach
    </main>
</div>

{{-- ОБНОВЛЕННЫЙ JAVASCRIPT --}}
<script>
    // Передаем данные из PHP в JavaScript
    const searchableRoutes = @json($searchableRoutes);

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar-nav');
        const searchInput = document.getElementById('api-search-input');
        const searchResultsContainer = document.getElementById('search-results');
        const mainNav = document.getElementById('main-navigation');

        // --- Логика 1: Поиск ---
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            if (!query) {
                // Если поле поиска пустое, показываем основную навигацию и скрываем результаты
                mainNav.hidden = false;
                searchResultsContainer.hidden = true;
                searchResultsContainer.innerHTML = '';
                return;
            }

            // Фильтруем роуты
            const matches = searchableRoutes.filter(route => {
                const groupMatch = route.group ? route.group.toLowerCase().includes(query) : false;
                const subgroupMatch = route.subgroup ? route.subgroup.toLowerCase().includes(query) : false;
                const nameMatch = route.name ? route.name.toLowerCase().includes(query) : false;
                const uriMatch = route.uri ? route.uri.toLowerCase().includes(query) : false;
                return groupMatch || subgroupMatch || nameMatch || uriMatch;
            });

            // Генерируем HTML для результатов
            const resultsHtml = matches.map(route => {
                return `
                    <a href="${route.href}" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm border-l-gray-200 border-l-2">
                        <div class="font-bold">${route.name}</div>
                        <div class="text-xs text-gray-500">${route.uri}</div>
                        <div class="text-xs text-blue-500">${route.group} ${route.subgroup ? `&rarr; ${route.subgroup}` : ''}</div>
                    </a>
                `;
            }).join('');

            searchResultsContainer.innerHTML = resultsHtml || `<div class="p-4 text-sm text-gray-500">Ничего не найдено</div>`;
            mainNav.hidden = true;
            searchResultsContainer.hidden = false;
        });

        // --- Логика 2: Клик по результату поиска ---
        searchResultsContainer.addEventListener('click', function(event) {
            // Проверяем, что кликнули именно по ссылке
            const link = event.target.closest('a');
            if (!link) return;

            // Очищаем поиск и возвращаем вид к основной навигации
            searchInput.value = '';
            // Искусственно вызываем событие 'input', чтобы сработала логика скрытия результатов
            searchInput.dispatchEvent(new Event('input'));
        });


        // --- Логика 3: Сворачивание / разворачивание категорий по клику (без изменений) ---
        mainNav.querySelectorAll('.sidebar-toggler').forEach(toggler => {
            toggler.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target-id');
                const targetElement = document.getElementById(targetId);
                const icon = this.querySelector('.toggler-icon');

                if (targetElement) {
                    targetElement.toggleAttribute('hidden');
                    if (icon) {
                        icon.classList.toggle('rotate-90', !targetElement.hasAttribute('hidden'));
                    }
                }
            });
        });

        // --- Логика 4: Активация роута при загрузке или смене хэша ---
        function activateLinkFromUrl() {
            // Убираем старую подсветку
            document.querySelectorAll('.sidebar-link-active').forEach(link => {
                link.classList.remove('sidebar-link-active');
            });

            const hash = window.location.hash;
            if (!hash) return;

            const activeLink = mainNav.querySelector(`a[href="${hash}"]`);
            if (!activeLink) return;

            activeLink.classList.add('sidebar-link-active');

            let parent = activeLink.parentElement;
            while (parent && parent !== sidebar) {
                if (parent.classList.contains('collapsible-content')) {
                    parent.removeAttribute('hidden');
                    const toggler = document.querySelector(`[data-target-id="${parent.id}"]`);
                    if (toggler) {
                        const icon = toggler.querySelector('.toggler-icon');
                        if (icon) {
                            icon.classList.add('rotate-90');
                        }
                    }
                }
                parent = parent.parentElement;
            }

            setTimeout(() => {
                activeLink.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 100);
        }

        activateLinkFromUrl();
        window.addEventListener('hashchange', activateLinkFromUrl);
    });
</script>

</body>
</html>
