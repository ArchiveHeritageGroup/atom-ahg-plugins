<nav id="settings-menu" class="list-group mb-3 sticky-top">
  @foreach ($nodes as $node)
    <a href="{{ url_for(['module' => $node['module'] ?? 'ahgSettings', 'action' => $node['action']]) }}"
       class="list-group-item list-group-item-action{{ $node['active'] ? ' active' : '' }}">
      {{ $node['label'] }}
    </a>
  @endforeach
</nav>
