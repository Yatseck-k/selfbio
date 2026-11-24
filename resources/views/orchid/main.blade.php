@php($user = auth()->user())
@php($can = fn (string $permission) => $user?->hasAccess($permission) ?? false)

@php($cards = [
    [
        'title' => 'Сайт-визитка',
        'stat' => $stats['posts'] === null ? null : $stats['posts'].' '.trans_choice('{1} запись|[2,4] записи|[5,*] записей', $stats['posts']).' в блоге',
        'links' => [
            ['Блог', 'platform.selfbio.blog', 'platform.selfbio.blog.view'],
            ['Контакты', 'platform.selfbio.contacts', 'platform.selfbio.contacts.view'],
            ['Тексты страниц', 'platform.selfbio.card', 'platform.selfbio.settings.view'],
        ],
    ],
    [
        'title' => 'Личный кабинет',
        'stat' => $stats['account'] === null ? null : $stats['account'].' '.trans_choice('{1} пользователь|[2,4] пользователя|[5,*] пользователей', $stats['account']),
        'links' => [
            ['Пользователи', 'platform.account.users', 'platform.selfbio.account.view'],
        ],
    ],
    [
        'title' => 'Сайт',
        'stat' => 'Название: '.config('app.name'),
        'links' => [
            ['Настройки сайта', 'platform.selfbio.settings', 'platform.selfbio.settings.view'],
        ],
    ],
])

<div class="row g-3 mb-3">
    @foreach ($cards as $card)
        @php($links = array_values(array_filter($card['links'], fn (array $link): bool => $can($link[2]))))
        @if ($links !== [])
            <div class="col-md-6 col-xl-4">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h5 class="mb-1">{{ $card['title'] }}</h5>
                    @if ($card['stat'])
                        <p class="text-muted small mb-3">{{ $card['stat'] }}</p>
                    @endif
                    <ul class="list-unstyled mb-0">
                        @foreach ($links as $link)
                            <li class="mb-1"><a href="{{ route($link[1]) }}">{{ $link[0] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    @endforeach
</div>
