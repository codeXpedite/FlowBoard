@component('mail::message')
# Görev Atandı

Merhaba {{ $assignedUser->name }},

Size "**{{ $task->title }}**" görevi atandı.

**Proje:** {{ $task->project->name }}
**Atayan:** {{ $assignedBy->name }}
**Öncelik:** {{ ucfirst($task->priority) }}
**Durum:** {{ $task->taskStatus->name }}

@if($task->description)
**Açıklama:**
{{ $task->description }}
@endif

@if($task->due_date)
**Bitiş Tarihi:** {{ $task->due_date->format('d.m.Y H:i') }}
@endif

@component('mail::button', ['url' => $projectUrl])
Görevi Görüntüle
@endcomponent

Bu görev hakkında daha fazla bilgi almak için proje sayfasını ziyaret edebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent