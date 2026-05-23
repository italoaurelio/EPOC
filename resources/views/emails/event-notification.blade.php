<h2>{{ $payload['system_name'] }}</h2>
<p><strong>Grupo:</strong> {{ $payload['group_name'] }}</p>
<p><strong>Evento:</strong> {{ $payload['event_name'] }}</p>
<p><strong>Data:</strong> {{ $payload['event_date'] }}</p>
<p><strong>Horário:</strong> {{ $payload['event_time'] }}</p>
<p><strong>Local:</strong> {{ $payload['location'] }}</p>
<p><strong>Função:</strong> {{ $payload['function'] ?? 'A definir' }}</p>
<p><strong>Tipo:</strong> {{ $payload['type'] }}</p>
<p><a href="{{ $payload['app_url'] }}">Abrir o sistema</a></p>
